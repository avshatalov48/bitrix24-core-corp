<?php
namespace Bitrix\ImOpenLines\Queue\Event;

use Bitrix\ImOpenLines,
	Bitrix\ImOpenLines\Model\SessionCheckTable;

/**
 * Class Strictly
 * @package Bitrix\ImOpenLines\Queue\Event
 */
class Strictly extends Queue
{
	/**
	 * Send recent messages to operator in current queue when he return to work.
	 *
	 * @param int[] $userIds
	 * @return void
	 */
	public function returnUserToQueue(array $userIds): void
	{
		$sessionList = SessionCheckTable::getList([
			'select' => ['SESSION_ID'],
			'filter' => [
				'=SESSION.CONFIG_ID' => $this->configLine['ID'],
				'=UNDISTRIBUTED' => 'Y'
			]
		])->fetchAll();

		if (count($sessionList) > 0)
		{
			foreach ($sessionList as $session)
			{
				ImOpenLines\Queue::returnSessionToQueue((int)$session['SESSION_ID']);
			}

			ImOpenLines\Queue::transferToNextSession(false, ImOpenLines\Queue\Event::COUNT_SESSIONS_REALTIME, (int)$this->configLine['ID']);
		}
	}
}