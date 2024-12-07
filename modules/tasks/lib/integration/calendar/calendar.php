<?php

namespace Bitrix\Tasks\Integration\Calendar;

use Bitrix\Main\Config\Option;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Util\Type\DateTime;

class Calendar
{
	protected const MINUTES_IN_HOUR = 60;
	protected const MAX_ITERATIONS = 365;

	protected static string $hash = '';

	protected array $settings = [];
	protected array $holidays = [];
	protected array $weekEnds = [];
	protected array $workTime = [];

	protected static ?self $instance = null;

	public static function useCalendar(string $feature): bool
	{
		return match($feature)
		{
			'flow' => Option::get('tasks', 'tasks_flow_use_calendar', 'Y') === 'Y',
			'regular_template' => Option::get('tasks', 'tasks_regular_template_use_calendar', 'N') === 'Y',
			default => false,
		};
	}

	public static function getInstance(array $settings = []): self
	{
		if (null === self::$instance)
		{
			self::$instance = new self($settings);
		}
		elseif (static::$hash !== ($settingsHash = md5(serialize($settings))))
		{
			self::$hash = $settingsHash;
			self::$instance = new self($settings);
		}

		return self::$instance;
	}

	public function __construct(array $settings = [])
	{
		$this->setSettings([] === $settings ? $this->getPortalSettings() : $settings);
	}

	public function getClosestDate(\Bitrix\Main\Type\DateTime $date, int $offsetInSeconds, bool $matchWorkTime = false): DateTime
	{
		$start = DateTime::createFromDateTime($date);

		$deadline = $start->disableUserTime()->add(($offsetInSeconds) . ' seconds');

		if (!$matchWorkTime)
		{
			return $deadline;
		}

		$isWorkTime = $this->isWorkTime($deadline);

		if ($isWorkTime)
		{
			return $deadline;
		}

		return $this->getClosestWorkTime($deadline);
	}

	public function isWorkTime(DateTime $date): bool
	{
		$date = DateTime::createFromDateTime($date);

		if ($this->isWeekend($date) || $this->isHoliday($date))
		{
			return false;
		}

		$start = $this->getStartHour() * self::MINUTES_IN_HOUR + $this->getStartMinute();
		$end = $this->getEndHour() * self::MINUTES_IN_HOUR + $this->getEndMinute();
		$now = $date->getHour() * self::MINUTES_IN_HOUR + $date->getMinute();

		return $now >= $start && $now <= $end;
	}

	protected function getClosestWorkTime(DateTime $start): DateTime
	{
		$currentDate = DateTime::createFromTimestamp($start->getTimestamp());

		$counter = 0;

		while ($counter < self::MAX_ITERATIONS)
		{
			$intervals = $this->getWorkHours($currentDate);
			foreach ($intervals as $interval)
			{
				/** @var DateTime $intervalStart */
				$intervalStart = $interval['startDate'];

				/** @var DateTime $intervalStart */
				$intervalEnd = $interval['endDate'];

				if ($intervalEnd->checkLT($start))
				{
					continue;
				}

				return $intervalStart->checkLT($start) ? $start : $intervalStart;
			}

			$currentDate->stripTime();
			$currentDate->addDay(1);

			++$counter;
		}

		Logger::log([
			'settings' => $this->settings,
			'date' => $start,
		], 'TASKS_CALENDAR_WORKTIME_LOOP');

		return $currentDate;
	}

	public function getWorkHours(DateTime $date): array
	{
		if ($this->isWeekend($date) || $this->isHoliday($date))
		{
			return [];
		}

		$hours = [];

		$year = $date->getYear();
		$month = $date->getMonth();
		$day = $date->getDay();

		foreach ($this->workTime as $time)
		{
			$start = (new DateTime())->setDate($year, $month, $day)->setTime($time['start']['hours'], $time['start']['minutes']);

			$end = (new DateTime())->setDate($year, $month, $day)->setTime($time['end']['hours'], $time['end']['minutes']);

			$hours[] = [
				'startDate' => $start,
				'endDate' => $end,
			];
		}

		return $hours;
	}

	public function isHoliday(DateTime $date): bool
	{
		$month = $date->getMonth();
		$day = $date->getDay();

		return array_key_exists($month.'_'.$day, $this->holidays) && $this->holidays[$month.'_'.$day];
	}

	public function isWeekend(DateTime $date): bool
	{
		$day = $date->getWeekDay();

		return array_key_exists($day, $this->weekEnds) && $this->weekEnds[$day];
	}

	public function getStartHour(): int
	{
		return (int)($this->settings['HOURS']['START']['H'] ?? 0);
	}

	public function getStartMinute(): int
	{
		return (int)($this->settings['HOURS']['START']['M'] ?? 0);
	}

	public function getEndHour(): int
	{
		return (int)($this->settings['HOURS']['END']['H'] ?? 0);
	}

	public function getEndMinute(): int
	{
		return (int)($this->settings['HOURS']['END']['M'] ?? 0);
	}

	public function getSettings(): array
	{
		return $this->settings;
	}

	protected function setSettings(array $settings): void
	{
		if (is_array($settings['HOURS']) && !empty($settings['HOURS']))
		{
			$hours = $settings['HOURS'];

			$this->workTime = [
				[
					'start' => [
						'hours' => (int)$hours['START']['H'],
						'minutes' => (int)$hours['START']['M'],
						'time' => ((int)$hours['START']['H']) * self::MINUTES_IN_HOUR + ((int)$hours['START']['M']),
					],
					'end' => [
						'hours' => (int)$hours['END']['H'],
						'minutes' => (int)$hours['END']['M'],
						'time' => ((int)$hours['END']['H']) * self::MINUTES_IN_HOUR + ((int)$hours['START']['M']),
					],
				],
			];
		}

		if (is_array($settings['HOLIDAYS']))
		{
			foreach($settings['HOLIDAYS'] as $day)
			{
				$this->holidays[(int)$day['M'] . '_' . (int)$day['D']] = true;
			}
		}

		$dayMap = [
			'MO' => 1,
			'TU' => 2,
			'WE' => 3,
			'TH' => 4,
			'FR' => 5,
			'SA' => 6,
			'SU' => 0,
		];

		$this->weekEnds = [];
		if(is_array($settings['WEEKEND']))
		{
			foreach($settings['WEEKEND'] as $day)
			{
				$this->weekEnds[$dayMap[$day]] = true;
			}
		}

		if(count($this->weekEnds) === 7)
		{
			$this->weekEnds = [$dayMap['SA'] => true, $dayMap['SU'] => true];
		}

		$this->settings = $settings;
	}

	protected function getPortalSettings(): array
	{
		$defaultSettings = [
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
			'WEEK_START' => 'MO',
		];

		$calendarSettings = \Bitrix\Tasks\Integration\Calendar::getSettings();
		if(empty($calendarSettings))
		{
			return $defaultSettings;
		}

		if (is_array($calendarSettings['week_holidays']))
		{
			$defaultSettings['WEEKEND'] = $calendarSettings['week_holidays'];
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

					if ($day && $month)
					{
						$defaultSettings['HOLIDAYS'][] = ['M' => (int)$month, 'D' => (int)$day];
					}
				}
			}
		}

		$timeStart = explode('.', (string)$calendarSettings['work_time_start']);

		if (isset($timeStart[0]))
		{
			$defaultSettings['HOURS']['START']['H'] = (int)$timeStart[0];
		}

		if (isset($timeStart[1]))
		{
			$defaultSettings['HOURS']['START']['M'] = (int)$timeStart[1];
		}

		$timeEnd = explode('.', (string)$calendarSettings['work_time_end']);

		if (isset($timeEnd[0]))
		{
			$defaultSettings['HOURS']['END']['H'] = (int)$timeEnd[0];
		}

		if (isset($timeEnd[1]))
		{
			$defaultSettings['HOURS']['END']['M'] = (int)$timeEnd[1];
		}

		return $defaultSettings;
	}
}