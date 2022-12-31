<?php

namespace Bitrix\Crm\Settings;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use CCalendar;
use CCrmDateTimeHelper;

class WorkTime
{
	private const NUM_OF_ATTEMPTS_DETECT_NEAREST_DATE = 365;

	/**
	 * @var array
	 */
	private array $data = [];

	public function __construct()
	{
		$this->initWorkTimeData();
	}

	public function getData(): array
	{
		return $this->data;
	}

	public function setData(array $data): void
	{
		if (is_string($data['TIME_FROM']) && !empty($data['TIME_FROM']))
		{
			$data['TIME_FROM'] = $this->initTimeObject($data['TIME_FROM']);
		}

		if (is_string($data['TIME_TO']) && !empty($data['TIME_TO']))
		{
			$data['TIME_TO'] = $this->initTimeObject($data['TIME_TO']);
		}

		$this->data = $data;
	}

	public function isWorkDay(DateTime $date): bool
	{
		if (
			empty($this->data)
			|| $this->isHoliday($date)
			|| $this->isDayOff($date)
		)
		{
			return false;
		}

		return true;
	}

	public function isWorkTime(DateTime $time): bool
	{
		if (empty($this->data))
		{
			return false;
		}

		[$hours, $minutes] = explode(':', $time->format('G:i'));
		$hours = (int)$hours;
		$minutes = (int)$minutes;

		$isAfterFrom = ($hours > $this->data['TIME_FROM']->hours)
			|| ($hours === $this->data['TIME_FROM']->hours && $minutes >= $this->data['TIME_FROM']->minutes);

		$isBeforeTo = ($hours < $this->data['TIME_TO']->hours)
			|| ($hours === $this->data['TIME_TO']->hours && $minutes <= $this->data['TIME_TO']->minutes);

		return $isAfterFrom && $isBeforeTo;
	}

	/**
	 * @see http://docs.bx/R&D/bitrix_dev/modules/crm/utils/worktime
	 *
	 * @param int 			$days
	 * @param int 			$hours
	 * @param DateTime|null $baseDateInServerTimezone
	 *
	 * @return DateTime
	 *
	 * @throws ArgumentException
	 */
	public function detectNearestWorkDateTime(int $days = 0, int $hours = 0, DateTime $baseDateInServerTimezone = null): DateTime
	{
		$days = $days < 0 ? 0 : $days;
		if ($days > self::NUM_OF_ATTEMPTS_DETECT_NEAREST_DATE)
		{
			throw new ArgumentException('Must be less than ' . (self::NUM_OF_ATTEMPTS_DETECT_NEAREST_DATE +1), 'days');
		}

		$hours = $hours < 0 ? 0 : $hours;
		if ($hours > 22)
		{
			throw new ArgumentException('Must be less than 23 ', 'hours');
		}

		$interval = sprintf('%d hour', $hours);
		$date = $baseDateInServerTimezone ?? new DateTime(); // use current day if base date not set
		$date = (clone $date)->toUserTime();                 // use user time zone
		$date->add($interval);
		$date->setTime($date->format('H'),0);

		$attemptCount = 0;
		$workDayCount = 0;
		if ($days > 0)
		{
			while ($attemptCount < self::NUM_OF_ATTEMPTS_DETECT_NEAREST_DATE && $workDayCount < $days)
			{
				$attemptCount++;
				$date->add('1 day');
				if ($this->isWorkDay($date))
				{
					$workDayCount++;
				}
			}
		}

		// return default datetime if unable to detect the nearest work date
		if ($attemptCount === self::NUM_OF_ATTEMPTS_DETECT_NEAREST_DATE)
		{
			$currentDate = new DateTime();
			$currentDate = (clone $currentDate)->toUserTime(); // use user time zone
			$currentDate->add('1 hour');
			$currentDate->setTime($currentDate->format('H'),0);

			return CCrmDateTimeHelper::getServerTime($currentDate);
		}

		if (!$this->isWorkTime($date))
		{
			$date->setTime(
				$attemptCount > 0 ? $this->data['TIME_FROM']->hours : $this->data['TIME_TO']->hours,
				0
			);
		}

		return CCrmDateTimeHelper::getServerTime($date);
	}

	private function initWorkTimeData(): void
	{
		if (empty($this->data))
		{
			$holidays = [];
			$dayOff = [];
			$defaultWeekStart = 'MO';
			$defaultTimeFrom = $this->initTimeObject('9.00'); // 9:00
			$defaultTimeTo = $this->initTimeObject('18.00');  // 18:00

			if (Loader::includeModule('calendar'))
			{
				$calendarSettings = CCalendar::getSettings();
				$holidays = $calendarSettings['year_holidays'];
				if (!is_array($holidays))
				{
					$holidays = explode(',', $holidays);
					trimArr($holidays);
					$holidays = array_values($holidays);
				}

				$dayOff = is_array($calendarSettings['week_holidays']) ? $calendarSettings['week_holidays'] : [];
				trimArr($dayOff);

				$defaultWeekStart = $calendarSettings['week_start'];

				if (!empty($calendarSettings['work_time_start']))
				{
					$defaultTimeFrom = $this->initTimeObject((string)$calendarSettings['work_time_start']);
				}

				if (!empty($calendarSettings['work_time_end']))
				{
					$defaultTimeTo = $this->initTimeObject((string)$calendarSettings['work_time_end']);
				}
			}

			$this->data = [
				'WEEK_START' => $defaultWeekStart,
				'TIME_FROM' => $defaultTimeFrom,
				'TIME_TO' => $defaultTimeTo,
				'HOLIDAYS' => $holidays,
				'DAY_OFF' => $dayOff
			];
		}
	}

	private function isHoliday(DateTime $date): bool
	{
		$holiday = $date->format('j.m');

		return in_array($holiday, $this->data['HOLIDAYS'], true);
	}

	private function isDayOff(DateTime $date): bool
	{
		$day = mb_strtoupper(mb_substr($date->format('l'), 0, 2));

		return in_array($day, $this->data['DAY_OFF'], true);
	}

	private function initTimeObject(string $input): object
	{
		return new class($input)
		{
			public int $hours = 0;
			public int $minutes = 0;

			public function __construct(string $input)
			{
				$inputArr = preg_split("/[\s.:]/", $input);

				$this->hours = (int)$inputArr[0];
				$this->minutes = isset($inputArr[1]) ? (int)$inputArr[1] : 0;
			}

			public function toString(string $separator = ':'): string
			{
				return sprintf('%d%s%s', $this->hours, $separator, $this->minutes);
			}

			public function __toString(): string
			{
				return $this->toString();
			}
		};
	}
}
