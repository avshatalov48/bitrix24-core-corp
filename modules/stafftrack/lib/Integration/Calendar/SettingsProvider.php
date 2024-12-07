<?php

namespace Bitrix\Stafftrack\Integration\Calendar;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\StaffTrack\Trait\Singleton;

class SettingsProvider
{
	use Singleton;

	/**
	 * @return array|int[]
	 * @throws LoaderException
	 */
	public function getSettings(): array
	{
		if (!$this->isAvailable())
		{
			return [];
		}

		$calendarSettings = \CCalendar::GetSettings();
		$weekStart = $calendarSettings['week_start'] ?? '';

		return [
			'firstWeekday' => \CCalendar::IndByWeekDay($weekStart) + 1,
		];
	}

	/**
	 * @return array
	 * @throws LoaderException
	 */
	public function getWeekHolidays(): array
	{
		if (!$this->isAvailable())
		{
			return [];
		}

		$calendarSettings = \CCalendar::GetSettings();
		$weekHolidays = [];
		foreach ($calendarSettings['week_holidays'] as $weekHoliday)
		{
			$weekHolidays[] = \CCalendar::IndByWeekDay($weekHoliday);
		}

		return $weekHolidays;
	}

	/**
	 * @return array
	 * @throws LoaderException
	 */
	public function getYearHolidays(): array
	{
		if (!$this->isAvailable())
		{
			return [];
		}

		$calendarSettings = \CCalendar::GetSettings();

		return explode(',', $calendarSettings['year_holidays']);
	}

	/**
	 * @return bool
	 * @throws Main\LoaderException
	 */
	private function isAvailable(): bool
	{
		return Loader::includeModule('calendar');
	}
}
