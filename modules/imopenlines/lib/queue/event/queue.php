<?php

namespace Bitrix\ImOpenLines\Queue\Event;

use Bitrix\Main\Config\Option,
	Bitrix\ImOpenLines,
	Bitrix\ImOpenLines\Session,
	Bitrix\ImOpenLines\Tools\Lock,
	Bitrix\ImOpenLines\Model\SessionTable,
	Bitrix\ImOpenLines\Model\SessionCheckTable,
	Bitrix\Main\Type\DateTime;


/**
 * Class Queue
 * @package Bitrix\ImOpenLines\Queue\Event
 */
abstract class Queue
{
	public const MAX_SESSION_RETURN = 100;

	protected $configLine = [];

	/**
	 * Queue constructor.
	 * @param array $configLine
	 */
	public function __construct($configLine)
	{
		$this->configLine = $configLine;
	}

	/**
	 * Returns maximum session number per interaction.
	 * @return int
	 */
	public static function getMaxInteractionCount(): int
	{
		return (int)Option::get('imopenlines', 'queue_interact_count', self::MAX_SESSION_RETURN);
	}

	//region Lock

	/**
	 * @param int $chatId
	 * @return string
	 */
	private static function getKeyLock(int $chatId): string
	{
		return ImOpenLines\Queue\Queue::PREFIX_KEY_LOCK . $chatId;
	}

	/**
	 * @param int $chatId
	 * @return bool
	 */
	protected function startLock(int $chatId): bool
	{
		return Lock::getInstance()->set(static::getKeyLock($chatId));
	}

	/**
	 * @param int $chatId
	 * @return bool
	 */
	protected function stopLock(int $chatId): bool
	{
		return Lock::getInstance()->delete(static::getKeyLock($chatId));
	}

	//endregion

	/**
	 * Basic check that the operator is active.
	 *
	 * @param int $userId
	 * @param bool $ignorePause
	 * @return bool|string
	 */
	public function isOperatorActive($userId, bool $ignorePause = false)
	{
		return ImOpenLines\Queue::isOperatorActive($userId, $this->configLine['CHECK_AVAILABLE'], $ignorePause);
	}

	/**
	 * Are there any available operators in the line.
	 *
	 * @param bool $ignorePause
	 * @return bool
	 */
	public function isOperatorsActiveLine(bool $ignorePause = false): bool
	{
		return ImOpenLines\Queue::isOperatorsActiveLine($this->configLine['ID'], $this->configLine['CHECK_AVAILABLE'], $ignorePause);
	}

	/**
	 * Returns the number of sessions an open line can accept.
	 *
	 * @return int
	 */
	public function getCountFreeSlots(): int
	{
		$result = 0;

		$res = ImOpenLines\Queue::getList([
			'select' => [
				'ID',
				'USER_ID'
			],
			'filter' => [
				'=CONFIG_ID' => $this->configLine['ID']
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			]
		]);

		while ($queueUser = $res->fetch())
		{
			if ($this->isOperatorActive($queueUser['USER_ID']) === true)
			{
				$result += ImOpenLines\Queue::getCountFreeSlotOperator(
					$queueUser['USER_ID'],
					$this->configLine['ID'],
					$this->configLine['MAX_CHAT'],
					$this->configLine['TYPE_MAX_CHAT']
				);
			}
		}

		return $result;
	}

	/**
	 * Send recent messages to operator in current queue when he return to work.
	 *
	 * @param int[] $userIds
	 * @return void
	 */
	abstract public function returnUserToQueue(array $userIds): void;

	/**
	 * Return to the queue of not accepted or missed sessions.
	 *
	 * @param int $userId
	 * @param string $reasonReturn
	 * @return void
	 */
	public function returnNotAcceptedSessionsToQueue($userId, string $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT): void
	{
		$sessionList = [];

		$sessionListManager = SessionTable::getList([
			'select' => [
				'ID'
			],
			'filter' => [
				'=CONFIG_ID' => $this->configLine['ID'],
				'=OPERATOR_ID' => $userId,
				'<STATUS' => Session::STATUS_OPERATOR,
				'!=PAUSE' => 'Y'
			]
		]);

		while ($sessionId = $sessionListManager->fetch()['ID'])
		{
			$sessionList[$sessionId] = $sessionId;
		}

		$countSession = count($sessionList);

		if ($countSession > 0)
		{
			if ($countSession > $this->getCountFreeSlots())
			{
				$sessionListManager = SessionTable::getList([
					'select' => [
						'ID'
					],
					'filter' => [
						'=CONFIG_ID' => $this->configLine['ID'],
						'<STATUS' => Session::STATUS_ANSWER,
						'!=OPERATOR_ID' => $userId,
						'!=OPERATOR_FROM_CRM' => 'Y'
					]
				]);

				while ($sessionId = $sessionListManager->fetch()['ID'])
				{
					ImOpenLines\Queue::returnSessionToQueue($sessionId);
				}
			}

			foreach ($sessionList as $sessionId)
			{
				ImOpenLines\Queue::returnSessionToQueue($sessionId, $reasonReturn);
			}
		}
	}

	/**
	 * Return to the queue not distributed sessions
	 *
	 * @param string $reasonReturn
	 * @return void
	 */
	public function returnNotDistributedSessionsToQueue(string $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT): void
	{
		$sessionListManager = SessionCheckTable::getList([
			'select' => [
				'SESSION_ID'
			],
			'filter' => [
				'=SESSION.OPERATOR_ID' => 0,
				'=SESSION.CONFIG_ID' => $this->configLine['ID'],
				'=UNDISTRIBUTED' => 'Y'
			],
			'order' => ['SESSION_ID' => 'ASC'],
			'limit' => self::getMaxInteractionCount(),
		]);

		while ($sessionId = $sessionListManager->fetch()['SESSION_ID'])
		{
			ImOpenLines\Queue::returnSessionToQueue($sessionId, $reasonReturn);
		}
	}

	/**
	 * Returns all operator sessions.
	 *
	 * @param int[] $userIds
	 * @param string $reasonReturn
	 * @return void
	 */
	public function returnSessionsUsersToQueue(array $userIds, string $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT): void
	{
		if (!empty($userIds))
		{
			$sessionNotAccepted = [];
			$sessionAccepted = [];
			$sessionWaitClient = [];

			$managerSessionCheck = SessionCheckTable::getList([
				'select' => [
					'ID' => 'SESSION_ID',
					'STATUS' => 'SESSION.STATUS',
					'DATE_CLOSE'
				],
				'filter' => [
					'=SESSION.CONFIG_ID' => $this->configLine['ID'],
					'=SESSION.OPERATOR_ID' => $userIds,
				]
			]);

			while ($session = $managerSessionCheck->fetch())
			{
				$status = $session['STATUS'];
				unset($session['STATUS']);
				if ($status == Session::STATUS_WAIT_CLIENT)
				{
					$sessionWaitClient[$session['ID']] = $session;
				}
				elseif ($status < Session::STATUS_ANSWER)
				{
					$sessionNotAccepted[$session['ID']] = $session;
				}
				else
				{
					$sessionAccepted[$session['ID']] = $session;
				}
			}

			if (!empty($sessionWaitClient))
			{
				$this->returnSessionsWaitClientUsersToQueue($sessionWaitClient, $reasonReturn);
			}

			if (!empty($sessionNotAccepted) || !empty($sessionAccepted))
			{
				$freeSlotsCount = $this->getCountFreeSlots();
				$sessionsCount = count($sessionNotAccepted) + count($sessionAccepted);

				if (!empty($sessionAccepted))
				{
					$this->returnSessionsAcceptedUsersToQueue(array_keys($sessionAccepted), $reasonReturn);
				}

				if ($sessionsCount > $freeSlotsCount)
				{
					$managerSessionCheck = SessionCheckTable::getList([
						'select' => [
							'ID' => 'SESSION_ID',
						],
						'filter' => [
							'=SESSION.CONFIG_ID' => $this->configLine['ID'],
							'<SESSION.STATUS' => Session::STATUS_ANSWER,
							'!=SESSION.OPERATOR_FROM_CRM' => 'Y'
						]
					]);

					while ($sessionId = $managerSessionCheck->fetch()['ID'])
					{
						$sessionNotAccepted[$sessionId] = $sessionId;
					}
				}

				if (!empty($sessionNotAccepted))
				{
					$this->returnSessionsNotAcceptedUsersToQueue(array_keys($sessionNotAccepted), $reasonReturn);
				}
			}
		}
	}

	/**
	 * Marks sessions that are in close mode so that, if they are not closed, they return to the queue.
	 *
	 * @param array $sessionList
	 * @param string $reasonReturn
	 * @return void
	 */
	protected function returnSessionsWaitClientUsersToQueue(array $sessionList, string $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT): void
	{
		foreach ($sessionList as $session)
		{
			if (!empty($session['DATE_CLOSE']) && $session['DATE_CLOSE'] instanceof DateTime)
			{
				$dateQueue = new $session['DATE_CLOSE'];
			}
			else
			{
				$dateQueue = new DateTime();
			}

			$dateQueue->add('180 SECONDS');

			ImOpenLines\Queue::returnSessionWaitClientToQueue($session['ID'], $dateQueue, $reasonReturn);
		}
	}

	/**
	 * Return accepted user session.
	 *
	 * @param int[] $sessionIds
	 * @param string $reasonReturn
	 * @return void
	 */
	protected function returnSessionsAcceptedUsersToQueue(array $sessionIds, string $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT): void
	{
		foreach ($sessionIds as $id)
		{
			ImOpenLines\Queue::returnSessionToQueue($id, $reasonReturn);
		}
	}

	/**
	 * Return not accepted user session.
	 *
	 * @param array $sessionIds
	 * @param string $reasonReturn
	 * @return void
	 */
	protected function returnSessionsNotAcceptedUsersToQueue(array $sessionIds, string $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT): void
	{
		foreach ($sessionIds as $id)
		{
			ImOpenLines\Queue::returnSessionToQueue($id, $reasonReturn);
		}
	}

	/**
	 * Return to the session queue the user who went on vacation.
	 *
	 * @param int $userId
	 * @param int $durationAbsenceDay
	 * @param string $reasonReturn
	 * @return void
	 */
	public function returnSessionsUsersToQueueIsStartAbsence($userId, $durationAbsenceDay, string $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT): void
	{
		$this->returnNotAcceptedSessionsToQueue($userId, $reasonReturn);
	}

	/**
	 * OnChatAnswer event handler for filling free slots
	 */
	public function checkFreeSlotOnChatAnswer() {}

	/**
	 * OnChatSkip/OnChatMarkSpam/OnChatFinish/OnOperatorTransfer event handler for filling free slots
	 *
	 * @return void
	 */
	public function checkFreeSlotOnChatFinish(): void
	{
		$this->returnNotDistributedSessionsToQueue();

		ImOpenLines\Queue::transferToNextSession(false, ImOpenLines\Queue\Event::COUNT_SESSIONS_DEFAULT, $this->configLine['ID']);
	}

	/**
	 * OnImopenlineMessageSend event handler for filling free slots
	 *
	 * @param array $messageData
	 * @return void
	 */
	public function checkFreeSlotOnMessageSend($messageData): void
	{
		if ($this->configLine['MAX_CHAT'] > 0)
		{
			$session = new Session();
			$resultLoad = $session->load([
				'USER_CODE' => $messageData['CHAT_ENTITY_ID'],
				'SKIP_CREATE' => 'Y'
			]);

			if ($resultLoad)
			{
				$firstMessage = $messageData['STATUS_BEFORE'] < Session::STATUS_CLIENT_AFTER_OPERATOR && $messageData['STATUS_AFTER'] == Session::STATUS_OPERATOR;

				if ($firstMessage)
				{
					$returnNotDistributed = ($this->configLine['TYPE_MAX_CHAT'] == ImOpenLines\Config::TYPE_MAX_CHAT_ANSWERED ||
						$this->configLine['TYPE_MAX_CHAT'] == ImOpenLines\Config::TYPE_MAX_CHAT_ANSWERED_NEW);
				}
				else
				{
					$returnNotDistributed = ($this->configLine['TYPE_MAX_CHAT'] == ImOpenLines\Config::TYPE_MAX_CHAT_ANSWERED);
				}

				if ($returnNotDistributed)
				{
					$this->returnNotDistributedSessionsToQueue();
					ImOpenLines\Queue::transferToNextSession(false, ImOpenLines\Queue\Event::COUNT_SESSIONS_DEFAULT, $this->configLine['ID']);
				}
			}
		}
	}
}