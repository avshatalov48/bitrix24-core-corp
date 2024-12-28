<?php

namespace Bitrix\ImOpenLines\Queue\Event;

use Bitrix\ImOpenLines,
	Bitrix\ImOpenLines\Session,
	Bitrix\ImOpenLines\Model\SessionTable,
	Bitrix\ImOpenLines\Model\SessionCheckTable;

/**
 * Class All
 * @package Bitrix\ImOpenLines\Queue\Event
 */
class All extends Queue
{
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
			'filter' => ['=CONFIG_ID' => $this->configLine['ID']],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			]
		]);

		while ($queueUser = $res->fetch())
		{
			if ($this->isOperatorActive($queueUser['USER_ID']) === true)
			{
				$result += ImOpenLines\Queue::getCountFreeSlotOperator($queueUser['USER_ID'], $this->configLine['ID']);
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
	public function returnUserToQueue(array $userIds): void
	{
		$config = ImOpenLines\Model\ConfigTable::getRow([
			'select' => ['WELCOME_BOT_ENABLE', 'WELCOME_BOT_ID'],
			'filter' => [
				'=ID' => $this->configLine['ID']
			],
		]);

		$sessionList = SessionCheckTable::getList([
			'select' => ['SESSION_ID', 'OPERATOR_ID' => 'SESSION.OPERATOR_ID'],
			'filter' => [
				'=SESSION.CONFIG_ID' => $this->configLine['ID'],
				'<SESSION.STATUS' => Session::STATUS_ANSWER,
				'!=SESSION.OPERATOR_FROM_CRM' => 'Y'
			]
		]);

		while ($session = $sessionList->fetch())
		{
			if (
				$config['WELCOME_BOT_ENABLE'] === 'Y'
				&& $config['WELCOME_BOT_ID'] == $session['OPERATOR_ID']
			)
			{
				continue;
			}

			ImOpenLines\Queue::returnSessionToQueue($session['SESSION_ID']);
		}

		ImOpenLines\Queue::transferToNextSession(false, ImOpenLines\Queue\Event::COUNT_SESSIONS_REALTIME, $this->configLine['ID']);
	}

	/**
	 * Return to the queue of not accepted sessions.
	 *
	 * @param int $userId
	 * @param string $reasonReturn
	 * @return void
	 */
	public function returnNotAcceptedSessionsToQueue($userId = 0, string $reasonReturn = ImOpenLines\Queue::REASON_DEFAULT): void
	{
		$sessionListManager = SessionTable::getList([
			'select' => [
				'ID',
				'OPERATOR_ID'
			],
			'filter' => [
				'LOGIC' => 'OR',
				[
					// Remove unanswered, but accepted by the operator dialogs.
					'=CONFIG_ID' => $this->configLine['ID'],
					'=OPERATOR_ID' => $userId,
					'<STATUS' => Session::STATUS_OPERATOR,
					'!=PAUSE' => 'Y'
				],
				[
					// Rebuilding the list of missed conversations for operators.
					'=CONFIG_ID' => $this->configLine['ID'],
					'<STATUS' => Session::STATUS_ANSWER,
					'!=OPERATOR_FROM_CRM' => 'Y'
				]
			]
		]);

		while ($session = $sessionListManager->fetch())
		{
			if (!empty($session['OPERATOR_ID']) && $session['OPERATOR_ID'] == $userId)
			{
				ImOpenLines\Queue::returnSessionToQueue($session['ID'], $reasonReturn);
			}
			else
			{
				if ($reasonReturn === ImOpenLines\Queue::REASON_OPERATOR_DAY_END)
				{
					ImOpenLines\Queue::returnSessionToQueue($session['ID'], ImOpenLines\Queue::REASON_OPERATOR_DAY_END_SILENT);
				}
				else
				{
					ImOpenLines\Queue::returnSessionToQueue($session['ID']);
				}
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
				'=SESSION.CONFIG_ID' => $this->configLine['ID']
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
					'OPERATOR_ID' => 'SESSION.OPERATOR_ID',
					'DATE_CLOSE'
				],
				'filter' => [
					'LOGIC' => 'OR',
					[
						'=SESSION.CONFIG_ID' => $this->configLine['ID'],
						'=SESSION.OPERATOR_ID' => $userIds
					],
					[
						'=SESSION.CONFIG_ID' => $this->configLine['ID'],
						'<SESSION.STATUS' => Session::STATUS_ANSWER,
						'!=SESSION.OPERATOR_FROM_CRM' => 'Y'
					]
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
				if (!empty($sessionAccepted))
				{
					$this->returnSessionsAcceptedUsersToQueue(array_keys($sessionAccepted), $reasonReturn);
				}

				if (!empty($sessionNotAccepted))
				{
					$config = ImOpenLines\Model\ConfigTable::getRow([
						'select' => ['WELCOME_BOT_ENABLE', 'WELCOME_BOT_ID'],
						'filter' => [
							'=ID' => $this->configLine['ID']
						],
					]);

					if ($config['WELCOME_BOT_ENABLE'] === 'Y')
					{
						foreach ($sessionNotAccepted as $sessionId => $session)
						{
							if ($config['WELCOME_BOT_ID'] == $session['OPERATOR_ID'])
							{
								unset($sessionNotAccepted[$sessionId]);
							}
						}
					}

					$this->returnSessionsNotAcceptedUsersToQueue(array_keys($sessionNotAccepted), $reasonReturn);
				}
			}
		}
	}

	/**
	 * OnChatAnswer event handler for filling free slots
	 * @return void
	 */
	public function checkFreeSlotOnChatAnswer(): void
	{
		$this->returnNotDistributedSessionsToQueue();
	}

	/**
	 * OnChatSkip/OnChatMarkSpam/OnChatFinish/OnOperatorTransfer event handler for filling free slots
	 * @return void
	 */
	public function checkFreeSlotOnChatFinish(): void
	{
		$this->returnNotDistributedSessionsToQueue();
	}

	/**
	 * OnImopenlineMessageSend event handler for filling free slots
	 *
	 * @param array $messageData
	 */
	public function checkFreeSlotOnMessageSend($messageData): void
	{}
}