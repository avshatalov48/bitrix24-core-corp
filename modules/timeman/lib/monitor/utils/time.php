<?php
namespace Bitrix\Timeman\Monitor\Utils;
use Bitrix\Main\Localization\Loc;

class Time
{
	public static function format(int $seconds): string
	{
		if ($seconds < 60)
		{
			return Loc::getMessage('TIMEMAN_MONITOR_LESS_THAN_MINUTE');
		}

		$time = self::secondsToHourMinutes($seconds);
		if ($time['hours'] > 0)
		{
			$hours = $time['hours'].' '.Loc::getMessage('TIMEMAN_MONITOR_HOUR_SHORT');
			$minutes = $time['minutes'] > 0
				? ' ' . $time['minutes'] .' '. Loc::getMessage('TIMEMAN_MONITOR_MINUTES_SHORT')
				: ''
			;

			return $hours.$minutes;
		}

		return $time['minutes'].' '.Loc::getMessage('TIMEMAN_MONITOR_MINUTES_SHORT');
	}

	protected static function secondsToHourMinutes(int $seconds)
	{
		if ($seconds < 1)
		{
			return false;
		}

		$hours = floor($seconds / 3600);
		$minutes = ($seconds / 60 % 60);

		return [
			'hours' => $hours,
			'minutes' => $minutes
		];
	}

	public static function msToSec(int $milliseconds)
	{
		return $milliseconds / 1000;
	}
}