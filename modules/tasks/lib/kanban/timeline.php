<?php
namespace Bitrix\Tasks\Kanban;

use \Bitrix\Main\Type\DateTime;
use \Bitrix\Tasks\Util\Calendar;

class TimeLineTable// *Table for unity structure
{
	/**
	 * Local storage of client's date.
	 * @var string
	 */
	protected static $dateClient = '';

	/**
	 * Local storage of client's date and time.
	 * @var string
	 */
	protected static $timeClient = '';

	/**
	 * Sets client's date.
	 * @param string $dateClient Client's date.
	 * @return void
	 */
	public static function setDateClient($dateClient)
	{
		if (is_string($dateClient))
		{
			self::$dateClient = $dateClient;
		}
	}

	/**
	 * Returns client's date.
	 * @return string
	 */
	public static function getDateClient()
	{
		if (self::$dateClient)
		{
			return self::$dateClient;
		}
		return date(self::getDatePhpFormat(FORMAT_DATE), (time() + \CTimeZone::GetOffset()));
	}

	/**
	 * Sets client's date and time.
	 * @param string $timeClient Client's date.
	 * @return void
	 */
	public static function setDateTimeClient($timeClient)
	{
		if (is_string($timeClient))
		{
			self::$timeClient = $timeClient;
		}
	}

	/**
	 * Returns client's date and time.
	 * @return string
	 */
	public static function getDateTimeClient()
	{
		if (self::$timeClient)
		{
			return self::$timeClient;
		}
		return date(self::getDatePhpFormat(FORMAT_DATETIME), (time() + \CTimeZone::GetOffset()));
	}

	/**
	 * Returns format for date php function.
	 * @param string $formatTpl Format template.
	 * @return string
	 */
	protected static function getDatePhpFormat($formatTpl = FORMAT_DATETIME)
	{
		return \Bitrix\Main\Type\Date::convertFormatToPhp($formatTpl);
	}

	/**
	 * Gets stages for timeline.
	 * @return array
	 */
	public static function getStages()
	{
		static $timeLineStages = [];

		if ($timeLineStages)
		{
			return $timeLineStages;
		}

		$timeClient = self::getDateTimeClient();
		$dateClient1 = self::getDateClient();
		$dateClient2 = $dateClient1 . ' 23:59:59';

		$format = self::getDatePhpFormat();
		$timeClientTS = \MakeTimeStamp($timeClient);
		$dateClient31TS = \MakeTimeStamp($dateClient1);
		$dateClient2TS = \MakeTimeStamp($dateClient2);
		$currentWeekDay = date('N', $timeClientTS);

		$timeLineStages = [
			// overdue
			'PERIOD1' => [
				'COLOR' => 'FF5752',
				'FILTER' => [
					'<=DEADLINE' => date($format, $timeClientTS)
				],
				'UPDATE' => [],
				'UPDATE_ACCESS' => false
			],
			// today
			'PERIOD2' => [
				'COLOR' => '9DCF00',
				'FILTER' => [
					'>DEADLINE' => date($format, $timeClientTS),
					'<=DEADLINE' => date($format, $dateClient2TS)
				],
				'UPDATE' => [
					'DEADLINE' => self::getClosestWorkHour($dateClient2TS)
				],
				'UPDATE_ACCESS' => \CTaskItem::ACTION_CHANGE_DEADLINE
			],
			// on this week
			'PERIOD3' => [
				'COLOR' => '2FC6F6',
				'FILTER' => [
					'>DEADLINE' => date($format, $dateClient2TS),
					'<=DEADLINE' => date($format, ($endTimeWeek = $dateClient2TS + (7 - $currentWeekDay) * 86400))
				],
				'UPDATE' => [
					'DEADLINE' => self::getClosestWorkHour($endTimeWeek)
				],
				'UPDATE_ACCESS' => \CTaskItem::ACTION_CHANGE_DEADLINE
			],
			// on next week
			'PERIOD4' => [
				'COLOR' => '55D0E0',
				'FILTER' => [
					'>DEADLINE' => date($format, $endTimeWeek),
					'<=DEADLINE' => date($format, ($endTimeNextWeek = $endTimeWeek + 7 * 86400))
				],
				'UPDATE' => [
					'DEADLINE' => self::getClosestWorkHour($endTimeNextWeek)
				],
				'UPDATE_ACCESS' => \CTaskItem::ACTION_CHANGE_DEADLINE
			],
			// without deadline
			'PERIOD5' => [
				'COLOR' => 'A8ADB4',
				'FILTER' => [
					'DEADLINE' => false
				],
				'UPDATE' => [
					'DEADLINE' => false
				],
				'UPDATE_ACCESS' => \CTaskItem::ACTION_CHANGE_DEADLINE
			],
			// over next week
			'PERIOD6' => [
				'COLOR' => '468EE5',
				'FILTER' => [
					'>DEADLINE' => date($format, $endTimeNextWeek),
				],
				'UPDATE' => [
					'DEADLINE' => self::getClosestWorkHour($endTimeNextWeek + 7 * 86400)
				],
				'UPDATE_ACCESS' => \CTaskItem::ACTION_CHANGE_DEADLINE
			],
		];

		return $timeLineStages;
	}

	/**
	 * Gets first work day in the past.
	 * @param int $timeStamp Timestamp.
	 * @return string|bool
	 */
	public static function getClosestWorkHour($timeStamp)
	{
		// compatibility
		if ($timeStamp instanceof DateTime)
		{
			$timeStamp = $timeStamp->getTimestamp();
		}
		if (!is_int($timeStamp))
		{
			return false;
		}

		$calendar = new Calendar();
		$now = \Bitrix\Tasks\Util\Type\DateTime::createFromTimestamp(time());
		$dateTime = \Bitrix\Tasks\Util\Type\DateTime::createFromTimestamp($timeStamp);

		// get in the past, first work day
		while ($dateTime->checkGT($now))
		{
			if ($calendar->isWeekend($dateTime) || $calendar->isHoliday($dateTime))
			{
				$dateTime->addDay(-1);
			}
			else
			{
				break;
			}
		}

		$calendarSettings = Calendar::getSettings();
		if (isset($calendarSettings['HOURS']['END']['H'], $calendarSettings['HOURS']['END']['M']))
		{
			$dateTime->setTime($calendarSettings['HOURS']['END']['H'], $calendarSettings['HOURS']['END']['M']);
		}

		$result = date(self::getDatePhpFormat(), $dateTime->getTimestamp());
		while (
			$dateTime->checkLT($now)
			|| !$calendar->isWorkTime(\Bitrix\Tasks\Util\Type\DateTime::createFromUserTimeGmt($result))
		)
		{
			$dateTime->addDay(1);
			$result = date(self::getDatePhpFormat(), $dateTime->getTimestamp());
		}

		return $result;
	}
}