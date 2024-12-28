<?php

namespace Bitrix\Tasks\Flow\Time;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Util\UI;
use CTimeZone;

class DatePresenter implements Arrayable
{
	private const DAYS_IN_YEAR = 365;
	private const MONTHS_IN_YEAR = 12;
	private const DAYS_IN_MONTH = 30;
	private const HOURS_IN_DAY = 24;
	private const MINUTES_IN_HOUR = 60;
	private const SECONDS_IN_MINUTE = 60;

	private int $years;
	private int $months;
	private int $days;
	private int $hours;
	private int $minutes;
	private int $seconds;
	private int $secondTotal;
	private string $formatted;

	private self $raw;

	public static function beautify(DateTime $date, int $userId = 0): string
	{
		$timezoneOffset = $userId > 0 ? CTimeZone::GetOffset($userId) : 0;

		$beautifierCallbacks = [
			function (DateTime $date) use ($timezoneOffset): ?string
			{
				$today = new DateTime();
				if ($date->format('Y-m-d') === $today->format('Y-m-d'))
				{
					$timestamp = $date->getTimestamp() + $timezoneOffset;
					$format = UI::getHumanTimeFormat($timestamp);

					return Loc::getMessage('TASKS_DATE_PRESENTER_TODAY', [
						'#TIME#' => UI::formatDateTime($timestamp, $format)
					]);
				}

				return null;
			},

			function (DateTime $date) use ($timezoneOffset): ?string
			{
				$yesterday = (new DateTime())->add('-1 day');
				if ($date->format('Y-m-d') === $yesterday->format('Y-m-d'))
				{
					$timestamp = $date->getTimestamp() + $timezoneOffset;
					$format = UI::getHumanTimeFormat($timestamp);

					return Loc::getMessage('TASKS_DATE_PRESENTER_YESTERDAY', [
						'#TIME#' => UI::formatDateTime($timestamp, $format)
					]);
				}

				return null;
			},
		];

		foreach ($beautifierCallbacks as $callback)
		{
			$beautifiedDate = $callback($date);
			if ($beautifiedDate !== null)
			{
				return $beautifiedDate;
			}
		}

		$timestamp = $date->getTimestamp() + $timezoneOffset;
		$format = UI::getHumanDateTimeFormat($timestamp);

		return UI::formatDateTime($timestamp, $format);
	}

	public static function get(DateTime $a, DateTime $b): static
	{
		$diff = $a->getDiff($b);

		return new static (
			years: $diff->y,
			months: $diff->m,
			days: $diff->d,
			hours: $diff->h,
			minutes: $diff->i,
			seconds: $diff->s
		);
	}

	public static function createFromSeconds(int $seconds): static
	{
		if ($seconds < 0)
		{
			return new static();
		}

		if ($seconds < static::SECONDS_IN_MINUTE)
		{
			return new static(seconds: $seconds);
		}

		$minutes = (int)floor($seconds / static::SECONDS_IN_MINUTE);
		$seconds %= static::SECONDS_IN_MINUTE;

		if ($minutes < static::MINUTES_IN_HOUR)
		{
			return new static(minutes: $minutes, seconds: $seconds);
		}

		$hours = (int)floor($minutes / static::MINUTES_IN_HOUR);
		$minutes %= static::MINUTES_IN_HOUR;

		if ($hours < static::HOURS_IN_DAY)
		{
			return new static(hours: $hours, minutes: $minutes, seconds: $seconds);
		}

		$days = (int)floor($hours / static::HOURS_IN_DAY);
		$hours %= static::HOURS_IN_DAY;

		if ($days < static::DAYS_IN_MONTH)
		{
			return new static(days: $days, hours: $hours, minutes: $minutes, seconds: $seconds);
		}

		$months = (int)floor($days / static::DAYS_IN_MONTH);
		$days %= static::DAYS_IN_MONTH;

		if ($months < static::MONTHS_IN_YEAR)
		{
			return new static(months: $months, days: $days, hours: $hours, minutes: $minutes, seconds: $seconds);
		}

		$years = (int)floor($months / static::MONTHS_IN_YEAR);
		$months %= static::MONTHS_IN_YEAR;

		return new static($years, $months, $days, $hours, $minutes, $seconds);
	}

	public function __construct(int $years = 0, int $months = 0, int $days = 0, int $hours = 0, int $minutes = 0, int $seconds = 0)
	{
		$this->years = $years;
		$this->months = $months;
		$this->days = $days;
		$this->hours = $hours;
		$this->minutes = $minutes;
		$this->seconds = $seconds;
		$this->buildFormatted();
		$this->buildSecondsTotal();

		$this->raw = clone $this;

		$this->round();
	}

	public function round(): static
	{
		if ($this->years >= 1)
		{
			$this->years = $this->getRoundedValue($this->years, $this->months, static::MONTHS_IN_YEAR);

			return $this->reset(years: false);
		}

		if ($this->months >= 1)
		{
			$this->months = $this->getRoundedValue($this->months, $this->days, static::DAYS_IN_MONTH);

			if ($this->months === static::MONTHS_IN_YEAR)
			{
				$this->years = 1;

				return $this->reset(years: false);
			}

			return $this->reset(months: false);
		}

		if ($this->days >= 1)
		{
			$this->days = $this->getRoundedValue($this->days, $this->hours, static::HOURS_IN_DAY);

			if ($this->days === static::DAYS_IN_MONTH)
			{
				$this->months = 1;

				return $this->reset(months: false);
			}

			return $this->reset(days: false);
		}

		if ($this->hours >= 1)
		{
			$this->hours = $this->getRoundedValue($this->hours, $this->minutes, static::MINUTES_IN_HOUR);

			if ($this->hours === static::HOURS_IN_DAY)
			{
				$this->days = 1;

				return $this->reset(days: false);
			}

			return $this->reset(hours: false);
		}

		return $this->reset(minutes: false);
	}

	public function getFormatted(): string
	{
		return $this->formatted;
	}

	public function getSecondTotal(): int
	{
		return $this->secondTotal;
	}

	public function getRaw(): static
	{
		return $this->raw;
	}

	public function toArray(): array
	{
		return [
			'years' => $this->years,
			'months' => $this->months,
			'days' => $this->days,
			'hours' => $this->hours,
			'minutes' => $this->minutes,
			'seconds' => $this->seconds,
			'formatted' => $this->formatted,
		];
	}

	private function reset(bool $years = true, bool $months = true, bool $days = true, bool $hours = true, bool $minutes = true): static
	{
		if ($years)
		{
			$this->years = 0;
		}

		if ($months)
		{
			$this->months = 0;
		}

		if ($days)
		{
			$this->days = 0;
		}

		if ($hours)
		{
			$this->hours = 0;
		}

		if ($minutes)
		{
			$this->minutes = 0;
		}

		$this->buildFormatted();
		$this->buildSecondsTotal();

		return $this;
	}

	private function buildFormatted(): void
	{
		$parts = [];

		if ($this->years > 0)
		{
			$parts[] = Loc::getMessagePlural(
				'TASKS_DATE_DIFFERENCE_YEARS',
				$this->years,
				['{value}' => $this->years]
			);
		}

		if ($this->months > 0)
		{
			$parts[] = Loc::getMessagePlural(
				'TASKS_DATE_DIFFERENCE_MONTHS',
				$this->months,
				['{value}' => $this->months]
			);
		}

		if ($this->days > 0)
		{
			$parts[] = Loc::getMessagePlural(
				'TASKS_DATE_DIFFERENCE_DAYS',
				$this->days,
				['{value}' => $this->days]
			);
		}

		if ($this->hours > 0 && empty($parts))
		{
			$parts[] = Loc::getMessagePlural(
				'TASKS_DATE_DIFFERENCE_HOURS',
				$this->hours,
				['{value}' => $this->hours]
			);
		}

		if ($this->minutes > 0 && empty($parts))
		{
			$parts[] = Loc::getMessagePlural(
				'TASKS_DATE_DIFFERENCE_MINUTES',
				$this->minutes,
				['{value}' => $this->minutes]
			);
		}

		if (empty($parts))
		{
			$parts[] = Loc::getMessage('TASKS_DATE_DIFFERENCE_LESS_THAN_MINUTE');
		}

		$this->formatted = implode(' ', $parts);
	}

	private function buildSecondsTotal(): void
	{
		$inYears = $this->years * static::DAYS_IN_YEAR * static::HOURS_IN_DAY * static::MINUTES_IN_HOUR * static::SECONDS_IN_MINUTE;
		$inMonth = $this->months * static::DAYS_IN_MONTH * static::HOURS_IN_DAY * static::MINUTES_IN_HOUR * static::SECONDS_IN_MINUTE;
		$inDays = $this->days * static::HOURS_IN_DAY * static::MINUTES_IN_HOUR * static::SECONDS_IN_MINUTE;
		$inHours = $this->hours * static::MINUTES_IN_HOUR * static::SECONDS_IN_MINUTE;
		$inMinutes = $this->minutes * static::SECONDS_IN_MINUTE;

		$this->secondTotal = $inYears + $inMonth + $inDays + $inHours + $inMinutes + $this->seconds;
	}

	private function getRoundedValue(int $value, int $remainder, int $divisor): int
	{
		if ($remainder >= $divisor / 2)
		{
			++$value;
		}

		return $value;
	}
}