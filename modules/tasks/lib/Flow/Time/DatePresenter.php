<?php

namespace Bitrix\Tasks\Flow\Time;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\DateTime;
use RuntimeException;

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

	public static function get(DateTime $a, DateTime $b): static
	{
		$diff = $a->getDiff($b);
		$days = $diff->days;
		if ($days === 0)
		{
			return $diff->h > 0 ? new static(hours: $diff->h) : new static(minutes: $diff->i);
		}

		if ($days < static::DAYS_IN_MONTH)
		{
			return new static(days: $days);
		}

		if ($days < static::DAYS_IN_YEAR)
		{
			$months = (int)floor($days / static::DAYS_IN_MONTH);
			$days = (int)floor($days % static::DAYS_IN_MONTH);

			return new static(months: $months, days: $days);
		}

		$years = (int)floor($days / static::DAYS_IN_YEAR);
		$months = (int)floor(($days % static::DAYS_IN_YEAR) / static::DAYS_IN_MONTH);
		$days = (int)floor(($days % static::DAYS_IN_YEAR) % static::DAYS_IN_MONTH);

		return new static(years: $years, months: $months, days: $days);
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
	}

	public function getFormatted(): string
	{
		return $this->formatted;
	}

	public function getSecondTotal(): int
	{
		return $this->secondTotal;
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

	/**
	 * @throws RuntimeException
	 */
	public function __wakeup()
	{
		throw new RuntimeException('Cannot unserialize singleton');
	}

	private function __clone()
	{

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
			$parts[] = Loc::getMessage('TASKS_DATE_DIFFERENCE_JUST_NOW');
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
}