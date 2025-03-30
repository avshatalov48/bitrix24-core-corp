<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Calendar;

use Bitrix\Booking\Entity\Slot\Range;
use Bitrix\Booking\Internals\Service\Time;
use Bitrix\Main\Loader;

class Schedule
{
	public static array $calendarToBookingWeekDays = [
		'MO' => Range::WEEK_DAY_MON,
		'TU' => Range::WEEK_DAY_TUE,
		'WE' => Range::WEEK_DAY_WED,
		'TH' => Range::WEEK_DAY_THU,
		'FR' => Range::WEEK_DAY_FRI,
		'SA' => Range::WEEK_DAY_SAT,
		'SU' => Range::WEEK_DAY_SUN,
	];

	public static function getRange(): Range
	{
		if (!Loader::includeModule('calendar'))
		{
			return (new Range())
				->setFrom(Time::MINUTES_IN_HOUR * 9)
				->setTo(Time::MINUTES_IN_HOUR * 19)
				->setWeekDays(Range::DEFAULT_WORKING_WEEK_DAYS)
			;
		}

		$calendarSettings = \CCalendar::getSettings(['getDefaultForEmpty' => false]);

		$halfHour = Time::MINUTES_IN_HOUR / 2;
		$workTimeStart = self::round($calendarSettings['work_time_start'] * Time::MINUTES_IN_HOUR, $halfHour);
		$workTimeEnd = self::round($calendarSettings['work_time_end'] * Time::MINUTES_IN_HOUR, $halfHour);

		$weekDays = Range::WEEK_DAYS;
		if (isset($calendarSettings['week_holidays']))
		{
			$weekDays = array_diff(
				Range::WEEK_DAYS,
				array_map(fn($day) => self::$calendarToBookingWeekDays[$day], $calendarSettings['week_holidays']),
			);
		}

		if (empty($weekDays))
		{
			$weekDays = [self::getWeekStart()];
		}

		return (new Range())
			->setFrom($workTimeStart)
			->setTo($workTimeEnd)
			->setWeekDays($weekDays)
			->setTimezone(self::getTimezone())
		;
	}

	public static function getWeekStart(): string
	{
		if (!Loader::includeModule('calendar'))
		{
			return Range::WEEK_DAY_MON;
		}

		$code = \CCalendar::getWeekStart();
		if (isset(self::$calendarToBookingWeekDays[$code]))
		{
			return self::$calendarToBookingWeekDays[$code];
		}

		return Range::WEEK_DAY_MON;
	}

	// TODO: move to Math namespace or something
	private static function round(float $minutes, int $step): int
	{
		return (int)(round($minutes / $step) * $step);
	}

	/**
	 * todo This is a temporary solution. Please change it when a global solution appears.
	 */
	private static function getTimezone(): string
	{
		global $USER;

		if (!is_object($USER))
		{
			return '';
		}

		$timeZone = '';
		$autoTimeZone = $USER->GetParam('AUTO_TIME_ZONE') ?: '';

		if (\CTimeZone::IsAutoTimeZone(trim($autoTimeZone)))
		{
			if (($cookie = \CTimeZone::getTzCookie()) !== null)
			{
				// auto time zone from the cookie
				$timeZone = $cookie;
			}
		}
		else
		{
			// user set time zone manually
			$timeZone = $USER->GetParam('TIME_ZONE');
		}

		return (string)$timeZone;
	}
}
