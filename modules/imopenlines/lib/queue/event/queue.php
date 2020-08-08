<?php
namespace Bitrix\ImOpenLines\Queue\Event;

use \Bitrix\Main\Localization\Loc;

use \Bitrix\ImOpenLines,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\Tools\Lock,
	\Bitrix\ImOpenLines\Model\SessionTable,
	\Bitrix\ImOpenLines\Model\SessionCheckTable;
use \Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class Queue
 * @package Bitrix\ImOpenLines\Queue\Event
 */
abstract class Queue
{
	protected $configLine = [];

	/**
	 * Queue constructor.
	 * @param $configLine
	 */
	function __construct($configLine)
	{
		$this->configLine = $configLine;
	}

	/**
	 * @param $chatId
	 * @return string
	 */
	private static function getKeyLock($chatId)
	{
		return ImOpenLines\Queue\Queue::PREFIX_KEY_LOCK . $chatId;
	}

	/**
	 * @param $chatId
	 * @return bool
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	protected function startLock($chatId)
	{
		return Lock::getInstance()->set(static::getKeyLock($chatId));
	}

	/**
	 * @param $chatId
	 * @return bool
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	protected function stopLock($chatId)
	{
		return Lock::getInstance()->delete(static::getKeyLock($chatId));
	}

	/**
	 * Basic check that the operator is active.
	 *
	 * @param $userId
	 * @param bool $ignorePause
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isOperatorActive($userId, bool $ignorePause = false)
	{
		return ImOpenLines\Queue::isOperatorActive($userId, $this->configLine['CHECK_AVAILABLE'], $ignorePause);
	}

	/**
	 * Are there any available operators in the line.
	 *
	 * @param bool $ignorePause
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isOperatorsActiveLine(bool $ignorePause = false): bool
	{
		return ImOpenLines\Queue::isOperatorsActiveLine($this->configLine['ID'], $this->configLine['CHECK_AVAILABLE'], $ignorePause);
	}

	/**
	 * Returns the number of sessions an open line can accept.
	 *
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getCountFreeSlots()
	{
		$result = 0;

		$select = [
			'ID',
			'USER_ID'
		];

		$filter = ['=CONFIG_ID' => $this->configLine['ID']];

		$res = ImOpenLines\Queue::getList([
			'select' => $select,
			'filter' => $filter
		]);

		while($queueUser = $res->fetch())
		{
			if($this->isOperatorActive($queueUser['USER_ID']))
			{
				$result = $result + ImOpenLines\Queue::getCountFreeSlotOperator($queueUser['USER_ID'], $this->configLine['ID'], $this->configLine['MAX_CHAT'], $this->configLine['TYPE_MAX_CHAT']);
			}
		}

		return $result;
	}

	/**
	 * Send recent messages to operator in current queue when he return to work.
	 *
	 * @param $userIds
	 */
	abstract public function returnUserToQueue(array $userIds);

	/**
	 * Return to the queue of not accepted or missed sessions.
	 *
	 * @param $userId
	 * @param string $reasonReturn
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function returnNotAcceptedSessionsToQueue($userId, $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT)
	{
		$sessionList = [];

		$sessionListManager = SessionTable::getList(
			[
				'select' => [
					'ID'
				],
				'filter' => [
					'CONFIG_ID' => $this->configLine['ID'],
					'=OPERATOR_ID' => $userId,
					'<STATUS' => Session::STATUS_OPERATOR,
					'!=PAUSE' => 'Y'
				]
			]
		);

		while ($sessionId = $sessionListManager->fetch()['ID'])
		{
			$sessionList[$sessionId] = $sessionId;
		}

		$countSession = count($sessionList);

		if($countSession > 0)
		{
			if($countSession > $this->getCountFreeSlots())
			{
				$sessionListManager = SessionTable::getList(
					[
						'select' => [
							'ID'
						],
						'filter' => [
							'CONFIG_ID' => $this->configLine['ID'],
							'<STATUS' => Session::STATUS_ANSWER,
							'!=OPERATOR_ID' => $userId,
							'!=OPERATOR_FROM_CRM' => 'Y'
						]
					]
				);

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
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function returnNotDistributedSessionsToQueue($reasonReturn = ImOpenLines\Queue::REASON_DEFAULT)
	{
		$sessionListManager = SessionCheckTable::getList(
			[
				'select' => [
					'SESSION_ID'
				],
				'filter' => [
					'SESSION.OPERATOR_ID' => 0,
					'SESSION.CONFIG_ID' => $this->configLine['ID'],
					'UNDISTRIBUTED' => 'Y'
				]
			]
		);

		while ($sessionId = $sessionListManager->fetch()['SESSION_ID'])
		{
			ImOpenLines\Queue::returnSessionToQueue($sessionId, $reasonReturn);
		}
	}

	/**
	 * Returns all operator sessions.
	 *
	 * @param array $userIds
	 * @param string $reasonReturn
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function returnSessionsUsersToQueue(array $userIds, $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT)
	{
		if(!empty($userIds))
		{
			$sessionNotAccepted = [];
			$sessionAccepted = [];
			$sessionWaitClient = [];

			$managerSessionCheck = SessionCheckTable::getList(
				[
					'select' => [
						'ID' => 'SESSION_ID',
						'STATUS' => 'SESSION.STATUS',
						'DATE_CLOSE'
					],
					'filter' => [
						'SESSION.CONFIG_ID' => $this->configLine['ID'],
						'SESSION.OPERATOR_ID' => $userIds,
					]
				]
			);

			while ($session = $managerSessionCheck->fetch())
			{
				$status = $session['STATUS'];
				unset($session['STATUS']);
				if($status == Session::STATUS_WAIT_CLIENT)
				{
					$sessionWaitClient[$session['ID']] = $session;
				}
				elseif($status < Session::STATUS_ANSWER)
				{
					$sessionNotAccepted[$session['ID']] = $session;
				}
				else
				{
					$sessionAccepted[$session['ID']] = $session;
				}
			}

			if(!empty($sessionWaitClient))
			{
				$this->returnSessionsWaitClientUsersToQueue($sessionWaitClient, $reasonReturn);
			}

			if(!empty($sessionNotAccepted) || !empty($sessionAccepted))
			{
				$freeSlotsCount = $this->getCountFreeSlots();
				$sessionsCount = count($sessionNotAccepted) + count($sessionAccepted);

				if(!empty($sessionAccepted))
				{
					$this->returnSessionsAcceptedUsersToQueue(array_keys($sessionAccepted), $reasonReturn);
				}

				if ($sessionsCount > $freeSlotsCount)
				{
					$managerSessionCheck = SessionCheckTable::getList(
						[
							'select' => [
								'ID' => 'SESSION_ID',
							],
							'filter' => [
								'SESSION.CONFIG_ID' => $this->configLine['ID'],
								'<SESSION.STATUS' => Session::STATUS_ANSWER,
								'!=SESSION.OPERATOR_FROM_CRM' => 'Y'
							]
						]
					);

					while($sessionId = $managerSessionCheck->fetch()['ID'])
					{
						$sessionNotAccepted[$sessionId] = $sessionId;
					}
				}

				if(!empty($sessionNotAccepted))
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
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected function returnSessionsWaitClientUsersToQueue(array $sessionList, $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT)
	{
		foreach ($sessionList as $session)
		{
			if(!empty($session['DATE_CLOSE']) && $session['DATE_CLOSE'] instanceof DateTime)
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
	 * @param array $sessionIds
	 * @param string $reasonReturn
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected function returnSessionsAcceptedUsersToQueue(array $sessionIds, $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT)
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
	 *
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected function returnSessionsNotAcceptedUsersToQueue(array $sessionIds, $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT)
	{
		foreach ($sessionIds as $id)
		{
			ImOpenLines\Queue::returnSessionToQueue($id, $reasonReturn);
		}
	}

	/**
	 * Return to the session queue the user who went on vacation.
	 *
	 * @param $userId
	 * @param $durationAbsenceDay
	 * @param string $reasonReturn
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function returnSessionsUsersToQueueIsStartAbsence($userId, $durationAbsenceDay, $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT)
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
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function checkFreeSlotOnChatFinish()
	{
		$this->returnNotDistributedSessionsToQueue();

		ImOpenLines\Queue::transferToNextSession(false, ImOpenLines\Queue\Event::COUNT_SESSIONS_DEFAULT, $this->configLine['ID']);
	}

	/**
	 * OnImopenlineMessageSend event handler for filling free slots
	 *
	 * @param $messageData
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function checkFreeSlotOnMessageSend($messageData)
	{
		if ($this->configLine['MAX_CHAT'] > 0)
		{
			$session = new Session();
			$resultLoad = $session->load(
				[
					'USER_CODE' => $messageData['CHAT_ENTITY_ID'],
					'SKIP_CREATE' => 'Y'
				]
			);

			if($resultLoad)
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