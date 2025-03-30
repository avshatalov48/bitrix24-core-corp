<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Slot;

use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Internals\Exception\InvalidArgumentException;
use Bitrix\Booking\Internals\Service\Time;
use DateTimeImmutable;
use DateInterval;

class Range implements EntityInterface
{
	private int $from;
	private int $to;
	private string $timezone = 'UTC';

	private array $weekDays = self::WEEK_DAYS;
	private int $slotSize = 60; // by default slotSize is 60 minutes

	private ?int $id = null;
	private ?int $typeId = null;
	private ?int $resourceId = null;

	public const WEEK_DAY_MON = 'Mon';
	public const WEEK_DAY_TUE = 'Tue';
	public const WEEK_DAY_WED = 'Wed';
	public const WEEK_DAY_THU = 'Thu';
	public const WEEK_DAY_FRI = 'Fri';
	public const WEEK_DAY_SAT = 'Sat';
	public const WEEK_DAY_SUN = 'Sun';

	public const WEEK_DAYS = [
		self::WEEK_DAY_MON,
		self::WEEK_DAY_TUE,
		self::WEEK_DAY_WED,
		self::WEEK_DAY_THU,
		self::WEEK_DAY_FRI,
		self::WEEK_DAY_SAT,
		self::WEEK_DAY_SUN,
	];

	public const DEFAULT_WORKING_WEEK_DAYS = [
		self::WEEK_DAY_MON,
		self::WEEK_DAY_TUE,
		self::WEEK_DAY_WED,
		self::WEEK_DAY_THU,
		self::WEEK_DAY_FRI,
	];

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId(?int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getTypeId(): ?int
	{
		return $this->typeId;
	}

	public function setTypeId(?int $typeId): self
	{
		$this->typeId = $typeId;

		return $this;
	}

	public function getResourceId(): ?int
	{
		return $this->resourceId;
	}

	public function setResourceId(?int $resourceId): self
	{
		$this->resourceId = $resourceId;

		return $this;
	}

	public function getWeekDays(): array
	{
		return $this->weekDays;
	}

	public function setWeekDays(array $weekDays): self
	{
		if (empty($weekDays))
		{
			throw new InvalidArgumentException('Week days must not be empty');
		}

		foreach ($weekDays as $weekDay)
		{
			if (!in_array($weekDay, self::WEEK_DAYS, true))
			{
				throw new InvalidArgumentException('Invalid week days');
			}
		}

		$this->weekDays = $weekDays;

		return $this;
	}

	public function setWorkingWeekDays(): self
	{
		return $this->setWeekDays(self::DEFAULT_WORKING_WEEK_DAYS);
	}

	public function getFrom(): int
	{
		return $this->from;
	}

	public function getFromInSeconds(): int
	{
		return $this->getFrom() * Time::SECONDS_IN_MINUTE;
	}

	public function getFromAsHours(): int
	{
		return (int)floor($this->from / Time::MINUTES_IN_HOUR);
	}

	public function getFromAsMinutes(): int
	{
		return $this->from - ($this->getFromAsHours() * Time::MINUTES_IN_HOUR);
	}

	public function setFrom(int $from): self
	{
		$this->validateTimeInMinutes($from);

		if (isset($this->to))
		{
			if ($from >= $this->to)
			{
				throw new InvalidArgumentException('Invalid time');
			}
		}

		$this->from = $from;

		return $this;
	}

	public function getTo(): int
	{
		return $this->to;
	}

	public function getToInSeconds(): int
	{
		return $this->getTo() * Time::SECONDS_IN_MINUTE;
	}

	public function getToAsHours(): int
	{
		return (int)floor($this->to / Time::MINUTES_IN_HOUR);
	}

	public function getToAsMinutes(): int
	{
		return $this->to - ($this->getToAsHours() * Time::MINUTES_IN_HOUR);
	}

	public function setTo(int $to): self
	{
		$this->validateTimeInMinutes($to);

		if (isset($this->from))
		{
			if ($this->from >= $to)
			{
				throw new InvalidArgumentException('Invalid time');
			}
		}

		$this->to = $to;

		return $this;
	}

	public function getTimezone(): string
	{
		return $this->timezone;
	}

	public function setTimezone(string $timezone): self
	{
		$this->timezone = $timezone;

		return $this;
	}

	public function getSlotSize(): int
	{
		return $this->slotSize;
	}

	public function setSlotSize(int $slotSize): self
	{
		if ($slotSize < 0)
		{
			throw new InvalidArgumentException('Invalid slot size');
		}

		$this->slotSize = $slotSize;

		return $this;
	}

	public function getSlotsRequiredByMinutes(int $requiredMinutes): int
	{
		$slotSize = $this->getSlotSize();

		if ($requiredMinutes <= $slotSize)
		{
			return 1;
		}

		return (int)ceil($requiredMinutes / $slotSize);
	}

	public function makeDatePeriod(DateTimeImmutable $date): DatePeriod
	{
		$dateTimezoneOffset = $date->getTimezone()->getOffset($date);

		try
		{
			$dateTimezone = new \DateTimeZone($this->getTimezone());
		}
		catch (\Exception $e)
		{
			$dateTimezone = new \DateTimeZone('UTC');
		}
		$rangeTimezoneOffset = $dateTimezone->getOffset($date);

		$offsetDiff = $dateTimezoneOffset - $rangeTimezoneOffset;

		$dateFrom = $date->setTime(
			$this->getFromAsHours(),
			$this->getFromAsMinutes()
		);
		$dateTo = $date->setTime(
			$this->getToAsHours(),
			$this->getToAsMinutes()
		);

		if ($offsetDiff < 0)
		{
			$dateFrom = $dateFrom->sub(new \DateInterval('PT' . abs($offsetDiff) . 'S'));
			$dateTo = $dateTo->sub(new \DateInterval('PT' . abs($offsetDiff) . 'S'));
		}
		elseif ($offsetDiff > 0)
		{
			$dateFrom = $dateFrom->add(new \DateInterval('PT' . $offsetDiff . 'S'));
			$dateTo = $dateTo->add(new \DateInterval('PT' . $offsetDiff . 'S'));
		}

		if ($dateTo < $dateFrom)
		{
			$dateTo = $dateTo->add(new DateInterval('P1D'));
		}

		return new DatePeriod(
			$dateFrom,
			$dateTo
		);
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'from' => $this->from,
			'to' => $this->to,
			'timezone' => $this->timezone,
			'weekDays' => $this->weekDays,
			'slotSize' => $this->slotSize,
		];
	}

	public static function mapFromArray(array $props): self
	{
		$range = new Range();

		if (isset($props['id']))
		{
			$range->setId((int)$props['id']);
		}

		if (isset($props['from']))
		{
			$range->setFrom((int)$props['from']);
		}

		if (isset($props['to']))
		{
			$range->setTo((int)$props['to']);
		}

		if (isset($props['timezone']))
		{
			$range->setTimezone((string)$props['timezone']);
		}

		if (isset($props['weekDays']))
		{
			$range->setWeekDays((array)$props['weekDays']);
		}

		if (isset($props['slotSize']))
		{
			$range->setSlotSize((int)$props['slotSize']);
		}

		//@todo use link to Entity\Resource
		if (isset($props['resourceId']))
		{
			$range->setResourceId((int)$props['resourceId']);
		}

		//@todo use link to Entity\Resource
		if (isset($props['typeId']))
		{
			$range->setTypeId((int)$props['typeId']);
		}

		return $range;
	}

	/**
	 * @param int $time
	 * @return void
	 * @throws InvalidArgumentException
	 */
	private function validateTimeInMinutes(int $time): void
	{
		if ($time < 0 || $time > Time::MINUTES_IN_DAY)
		{
			throw new InvalidArgumentException('Invalid time');
		}
	}
}
