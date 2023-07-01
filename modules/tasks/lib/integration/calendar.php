<?php
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration;

use Bitrix\Tasks\Integration;

abstract class Calendar extends Integration
{
	const MODULE_NAME = 'calendar';

	public static function getSettings()
	{
		if (!static::includeModule())
		{
			return [];
		}

		return \CCalendar::getSettings(['getDefaultForEmpty' => false]);
	}

	public static function setSettings(array $settings): bool
	{
		if (!static::includeModule())
		{
			return false;
		}

		$settings = array_intersect_key(
			$settings,
			array_flip([
				'work_time_start',
				'work_time_end',
				'year_holidays',
				'year_workdays',
				'week_holidays',
				'week_start',
			])
		);
		if (array_key_exists('week_holidays', $settings) && is_array($settings['week_holidays']))
		{
			$settings['week_holidays'] = implode('|', $settings['week_holidays']);
		}

		\CCalendar::setSettings($settings);

		return true;
	}

	public static function getWorkSettings(): array
	{
		$workSettings = static::getDefaultWorkSettings();

		if (static::includeModule())
		{
			$calendarSettings = static::getSettings();

			if (is_array($calendarSettings['week_holidays']))
			{
				$workSettings['WEEKEND'] = $calendarSettings['week_holidays'];
			}
			if ((string)$calendarSettings['year_holidays'] !== '')
			{
				$holidays = explode(',', $calendarSettings['year_holidays']);
				if (is_array($holidays) && !empty($holidays))
				{
					foreach ($holidays as $day)
					{
						$day = trim($day);
						[$day, $month] = explode('.', $day);
						$day = (int)$day;
						$month = (int)$month;

						if ($day && $month)
						{
							$workSettings['HOLIDAYS'][] = [
								'M' => $month,
								'D' => $day,
							];
						}
					}
				}
			}

			$time = explode('.', (string)$calendarSettings['work_time_start']);
			if (!isset($time[0]))
			{
				$time[0] = 0;
			}
			if (!isset($time[1]))
			{
				$time[1] = 0;
			}
			if ((int)$time[0])
			{
				$workSettings['HOURS']['START']['H'] = (int)$time[0];
			}
			if ((int)$time[1])
			{
				$workSettings['HOURS']['START']['M'] = (int)$time[1];
			}

			$time = explode('.', (string)$calendarSettings['work_time_end']);
			if (!isset($time[0]))
			{
				$time[0] = 0;
			}
			if (!isset($time[1]))
			{
				$time[1] = 0;
			}
			if ((int)$time[0])
			{
				$workSettings['HOURS']['END']['H'] = (int)$time[0];
			}
			if ((int)$time[1])
			{
				$workSettings['HOURS']['END']['M'] = (int)$time[1];
			}
		}

		$deadlineTimeSettings = \CUserOptions::getOption(
			'tasks.bx.calendar.deadline',
			'time_visibility',
			[]
		);
		$workSettings['deadlineTimeVisibility'] = (
			(isset($deadlineTimeSettings['visibility']) && $deadlineTimeSettings['visibility'] === 'Y') ? 'Y' : 'N'
		);

		return [
			'WORK_SETTINGS' => $workSettings,
			'WORK_TIME' => $workSettings['HOURS'],
		];
	}

	private static function getDefaultWorkSettings(): array
	{
		$site = \CSite::GetByID(SITE_ID)->Fetch();
		$weekDay = (string)$site['WEEK_START'];
		$weekDaysMap = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];

		return [
			'HOURS' => [
				'START' => [
					'H' => 9,
					'M' => 0,
					'S' => 0,
				],
				'END' => [
					'H' => 19,
					'M' => 0,
					'S' => 0,
				],
			],
			'HOLIDAYS' => [],
			'WEEKEND' => ['SA', 'SU'],
			'WEEK_START' => ($weekDay !== '' && isset($weekDaysMap[$weekDay]) ? $weekDaysMap[$weekDay] : 'MO'),
		];
	}
}