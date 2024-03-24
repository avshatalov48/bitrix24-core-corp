<?php

namespace Bitrix\Voximplant;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Event;
use Bitrix\Voximplant\Model\CallTable;

class CallQueue
{
	/**
	 * @param $userId
	 * @return Result|bool
	 */
	public static function dequeueFirstUserCall($userId)
	{
		$call = static::getCallToDequeue($userId);
		if(!$call)
		{
			return false;
		}

		return $call->dequeue($userId);
	}

	/**
	 * Search for the call, that could be possibly routed to the user.
	 *
	 * @param int $userId Id of the user.
	 * @return Call|false Returns the found call or false if nothing is found.
	 */
	public static function getCallToDequeue($userId)
	{
		$isDayOpen = \CVoxImplantUser::GetActiveStatusByTimeman($userId);

		$helper = Application::getConnection()->getSqlHelper();

		$query = CallTable::query()
			->setSelect(['CALL_ID'])
			->where('STATUS', CallTable::STATUS_ENQUEUED)
			->where('DATE_CREATE', '>', new SqlExpression($helper->addSecondsToDateTime(-3600)))
			->where('QUEUE.\Bitrix\Voximplant\Model\QueueUserTable:QUEUE.USER_ID', $userId)
			->setOrder(['ID' => 'asc'])
			->setLimit(1);

		if (!$isDayOpen)
		{
			$query->where('CONFIG.TIMEMAN', 'N');
		}

		$cursor = $query->exec();
		$row = $cursor->fetch();
		if (!$row)
		{
			return false;
		}

		return Call::load($row['CALL_ID']);
	}

	/**
	 * Returns call position in it's current queue.
	 * @param int $callId Id of the call
	 * @return int
	 * @throws ArgumentException
	 */
	public static function getCallPosition($callId)
	{
		$call = Call::load($callId);
		if (!$call)
		{
			throw new ArgumentException("Call " . $callId . " is not found");
		}

		$helper = Application::getConnection()->getSqlHelper();

		$cnt = Model\CallTable::getCount([
			'=STATUS' => Model\CallTable::STATUS_ENQUEUED,
			'=QUEUE_ID' => $call->getQueueId(),
			'>DATE_CREATE' => new SqlExpression($helper->addSecondsToDateTime(-3600)),
			'<ID' => $call->getId()
		]);

		return $cnt + 1;
	}

	/**
	 * OnUserSetLastActivityDate event handler.
	 * Checks for enqueued calls that could be assigned to this user.
	 * @param Event $event Event object.
	 * @return void
	 */
	public static function onUserSetLastActivityDate(Event $event)
	{
		global $USER;
		if (!isset($USER) || !($USER instanceof \CUser))
		{
			return;
		}

		$users = $event->getParameter(0);
		foreach ($users as $userId)
		{
			if ($userId == $USER->GetID())
			{
				if ($USER->isJustBecameOnline())
				{
					static::dequeueFirstUserCall($USER->GetID());
				}
			}
		}
	}

	/**
	 * OnAfterTMDayStart event handler.
	 * Checks for enqueued calls that could be assigned to this user.
	 * @param $event Event object.
	 */
	public static function onAfterTMDayStart($event)
	{
		$userId = (int)$event['USER_ID'];
		static::dequeueFirstUserCall($userId);
	}

	/**
	 * OnAfterTMDayContinue event handler.
	 * Checks for enqueued calls that could be assigned to this user.
	 * @param $event Event object.
	 */
	public static function onAfterTMDayContinue($event)
	{
		$userId = (int)$event['USER_ID'];
		static::dequeueFirstUserCall($userId);
	}
}