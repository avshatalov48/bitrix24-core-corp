<?php
namespace Bitrix\ImOpenLines\Queue\Event;

use \Bitrix\ImOpenLines,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\Model\SessionCheckTable;

/**
 * Class Evenly
 * @package Bitrix\ImOpenLines\Queue\Event
 */
class Evenly extends Queue
{
	/**
	 * Send recent messages to operator in current queue when he return to work.
	 *
	 * @param $userIds
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function returnUserToQueue(array $userIds)
	{
		$sessionList = SessionCheckTable::getList(
			[
				'select' => ['SESSION_ID', 'UNDISTRIBUTED'],
				'filter' => [
					'SESSION.CONFIG_ID' => $this->configLine['ID'],
					'<SESSION.STATUS' => Session::STATUS_ANSWER,
					'!=SESSION.OPERATOR_FROM_CRM' => 'Y'
				]
			]
		)->fetchAll();

		$undistributedSessions = array();
		foreach ($sessionList as $session)
		{
			if ($session['UNDISTRIBUTED'] == 'Y')
				$undistributedSessions[] = $session;
		}

		$undistributedSessionsCount = count($undistributedSessions);
		if ($undistributedSessionsCount > 0)
		{
			$operatorsFreeSlotsCount = 0;

			foreach ($userIds as $userId)
			{
				$operatorsFreeSlotsCount = $operatorsFreeSlotsCount + ImOpenLines\Queue::getCountFreeSlotOperator($userId, $this->configLine['ID'], $this->configLine['MAX_CHAT'], $this->configLine['TYPE_MAX_CHAT']);
			}

			if ($undistributedSessionsCount <= $operatorsFreeSlotsCount)
			{
				foreach ($undistributedSessions as $session)
				{
					ImOpenLines\Queue::returnSessionToQueue($session['SESSION_ID']);
				}
			}
			else
			{
				foreach ($sessionList as $session)
				{
					ImOpenLines\Queue::returnSessionToQueue($session['SESSION_ID']);
				}
			}
		}
		else
		{
			foreach ($sessionList as $session)
			{
				ImOpenLines\Queue::returnSessionToQueue($session['SESSION_ID']);
			}
		}

		ImOpenLines\Queue::transferToNextSession(false, ImOpenLines\Queue\Event::COUNT_SESSIONS_REALTIME, $this->configLine['ID']);
	}
}