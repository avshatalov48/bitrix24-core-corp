<?php
namespace Bitrix\ImOpenLines\Queue\Event;

use \Bitrix\ImOpenLines,
	\Bitrix\ImOpenLines\Model\SessionCheckTable;

/**
 * Class Strictly
 * @package Bitrix\ImOpenLines\Queue\Event
 */
class Strictly extends Queue
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
			array(
				'select' => array('SESSION_ID'),
				'filter' => array(
					'SESSION.CONFIG_ID' => $this->configLine['ID'],
					'UNDISTRIBUTED' => 'Y'
				)
			)
		)->fetchAll();

		if (count($sessionList) > 0)
		{
			foreach ($sessionList as $session)
			{
				ImOpenLines\Queue::returnSessionToQueue($session['SESSION_ID']);
			}

			ImOpenLines\Queue::transferToNextSession(false, ImOpenLines\Queue\Event::COUNT_SESSIONS_REALTIME, $this->configLine['ID']);
		}
	}
}