<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

/**
 * This class is for internal use only, it can be changed any way without
 * notifications. Use CTaskTimerManager instead.
 * 
 * @access private
 */
final class CTaskTimerCore
{
	/**
	 * @param $userId
	 * @param $taskId
	 * @return array|bool array with keys TASK_ID, USER_ID, TIMER_STARTED_AT, TIMER_ACCUMULATOR,
	 * or false on error
	 */
	public static function start($userId, $taskId)
	{
		global $DB, $DBType;

		static $dbtype = null;

		if ($dbtype === null)
			$dbtype = strtolower($DBType);

		$userId = (int) $userId;
		$taskId = (int) $taskId;

		$ts = time();
		if ($ts < 1)
		{
			CTaskAssert::logError('[0x574ed9ab] current unix timestamp is in past, check system time');
			CTaskAssert::assert(false);
		}

		if ($taskId < 1)
		{
			CTaskAssert::logError('[0xf119fc40] invalid taskId: ' . $taskId);
			CTaskAssert::assert(false);
		}

		$DB->query(
			"UPDATE b_tasks_timer 
			SET TASK_ID = $taskId, TIMER_STARTED_AT = $ts, TIMER_ACCUMULATOR = 0
			WHERE USER_ID = $userId"
		);

		$arData = self::get($userId);
		if ($arData === false)		// there is no timer in DB?
		{
			// create it
			if ($dbtype === 'mysql')
			{
				$DB->query(
					"INSERT IGNORE INTO b_tasks_timer (USER_ID, TASK_ID, TIMER_STARTED_AT, TIMER_ACCUMULATOR) 
					VALUES ($userId, $taskId, $ts, 0)"
				);
			}
			else
			{
				$DB->query(
					"INSERT INTO b_tasks_timer (USER_ID, TASK_ID, TIMER_STARTED_AT, TIMER_ACCUMULATOR) 
					VALUES ($userId, $taskId, $ts, 0)",
					true
				);
			}

			$arData = self::get($userId);
		}

		// Some other timer can be started between our queries, so check it.
		if ((int) $arData['TASK_ID'] !== $taskId)
			return (false);

		return ($arData);
	}


//	/**
//     * @param $userId
//     * @param $taskId
//	 * @return void
//	 */
//	private static function pause($userId, $taskId)
//	{
//        global $DB;
//
//		$ts = time();
//
//		$DB->query(
//			"UPDATE b_tasks_timer
//			SET TIMER_ACCUMULATOR = TIMER_ACCUMULATOR + ($ts - TIMER_STARTED_AT),
//				TIMER_STARTED_AT = 0
//			WHERE
//				USER_ID = " . (int) $userId . "
//				AND TASK_ID = " . (int) $taskId . "
//				AND TIMER_STARTED_AT != 0
//				AND TIMER_STARTED_AT <= $ts"
//		);
//	}


	/**
	 * @param $userId
	 * @param $taskId
	 * @return array|bool array with keys TASK_ID, USER_ID, TIMER_STARTED_AT, TIMER_ACCUMULATOR,
	 * or false on error
	 */
	public static function stop($userId, $taskId = 0)
	{
		global $DB;

		$ts = time();
		if ($ts < 1)
		{
			CTaskAssert::logError('[0x03ad8b00] current unix timestamp is in past, check system time');
			CTaskAssert::assert(false);
		}

		$arData = self::get($userId, $taskId);
		if (
			($arData !== false)
			&& (
				($arData['TIMER_STARTED_AT'] != 0)
				|| ($arData['TIMER_ACCUMULATOR'] != 0)
			)
		)
		{
			$DB->query(
				"UPDATE b_tasks_timer 
				SET TIMER_ACCUMULATOR = 0, TIMER_STARTED_AT = 0
				WHERE
					USER_ID = " . (int) $userId . "
					AND TASK_ID = " . (int) $arData['TASK_ID']
			);

			if (($arData['TIMER_STARTED_AT'] > 0) && ($ts > $arData['TIMER_STARTED_AT']))
				$arData['TIMER_ACCUMULATOR'] += ($ts - $arData['TIMER_STARTED_AT']);

			return ($arData);
		}
		else
			return (false);
	}

	public static function get($userId, $taskId = 0)
	{
		global $DB;

		$rs = $DB->query(
			"SELECT TASK_ID, USER_ID, TIMER_STARTED_AT, TIMER_ACCUMULATOR 
			FROM b_tasks_timer
			WHERE USER_ID = " . (int) $userId . " AND TASK_ID ".($taskId ? ' = '.intval($taskId) : ' != 0')
		);

		if ($ar = $rs->fetch())
			return ($ar);
		else
			return (false);
	}


	public static function getByTaskId($taskId)
	{
		global $DB;

		$rs = $DB->query(
			"SELECT TASK_ID, USER_ID, TIMER_STARTED_AT, TIMER_ACCUMULATOR 
			FROM b_tasks_timer
			WHERE TASK_ID = " . (int) $taskId
		);

		$arTimers = array();

		while ($arTimer = $rs->fetch())
			$arTimers[] = $arTimer;

		return ($arTimers);
	}
}
