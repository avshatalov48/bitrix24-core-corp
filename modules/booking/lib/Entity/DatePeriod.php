<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity;

use Bitrix\Booking\Internals\Exception\InvalidArgumentException;
use Bitrix\Booking\Internals\Service\Rrule;
use Bitrix\Booking\Internals\Service\Time;
use DateTimeImmutable;
use DateInterval;
use DateTimeZone;

class DatePeriod implements EventInterface
{
	use EventTrait;

	private DateTimeImmutable $dateFrom;
	private DateTimeImmutable $dateTo;

	/**
	 * @param DateTimeImmutable $dateFrom
	 * @param DateTimeImmutable $dateTo
	 * @throws InvalidArgumentException
	 */
	public function __construct(DateTimeImmutable $dateFrom, DateTimeImmutable $dateTo)
	{
		if ($dateTo <= $dateFrom)
		{
			throw new InvalidArgumentException('DateTo must be greater than DateFrom');
		}

		$this->dateFrom = $dateFrom;
		$this->dateTo = $dateTo;
	}

	public function setTimezone(string $timezone): self
	{
		return new self(
			$this->dateFrom->setTimezone(new DateTimeZone($timezone)),
			$this->dateTo->setTimezone(new DateTimeZone($timezone))
		);
	}

	public function contains(DatePeriod $datePeriod): bool
	{
		return (
			$datePeriod->getDateFrom()->getTimestamp() >= $this->getDateFrom()->getTimestamp()
			&& $datePeriod->getDateFrom()->getTimestamp() < $this->getDateTo()->getTimestamp()
			&& $datePeriod->getDateTo()->getTimestamp() > $this->getDateFrom()->getTimestamp()
			&& $datePeriod->getDateTo()->getTimestamp() <= $this->getDateTo()->getTimestamp()
		);
	}

	public function intersects(DatePeriod $datePeriod): bool
	{
		return (
			(
				$this->getDateFrom()->getTimestamp() >= $datePeriod->getDateFrom()->getTimestamp()
				&& $this->getDateFrom()->getTimestamp() < $datePeriod->getDateTo()->getTimestamp()
			)
			|| (
				$datePeriod->getDateFrom()->getTimestamp() >= $this->getDateFrom()->getTimestamp()
				&& $datePeriod->getDateFrom()->getTimestamp() < $this->getDateTo()->getTimestamp()
			)
		);
	}

	public function addMinutes(int $minutes): self
	{
		return new self(
			$this->getDateFrom()->add(new DateInterval('PT' . $minutes . 'M')),
			$this->getDateTo()->add(new DateInterval('PT' . $minutes . 'M'))
		);
	}

	public function getDateFrom(): DateTimeImmutable
	{
		return $this->dateFrom;
	}

	public function getDateTo(): DateTimeImmutable
	{
		return $this->dateTo;
	}

	public function isOverMidnight(): bool
	{
		return (int)$this->dateFrom->format('j') !== (int)$this->dateTo->format('j');
	}

	public function getDateTimeCollection(): DateTimeCollection
	{
		$result = new DateTimeCollection();

		$dates = iterator_to_array(
			new \DatePeriod(
				$this->getDateFrom(),
				new DateInterval('P1D'),
				$this->getDateTo()
			)
		);
		foreach ($dates as $date)
		{
			$result->add(DateTimeImmutable::createFromInterface($date));
		}

		return $result;
	}

	public function isMultipleOf(int $minutes): bool
	{
		$diffInSeconds = $this->dateTo->getTimestamp() - $this->dateFrom->getTimestamp();

		return $diffInSeconds % ($minutes * Time::SECONDS_IN_MINUTE) === 0;
	}

	public function isGreaterThanDay(): bool
	{
		return $this->dateTo->getTimestamp() - $this->dateFrom->getTimestamp() > Time::SECONDS_IN_DAY;
	}

	public function diffMinutes(): int
	{
		$diffInSeconds = $this->dateTo->getTimestamp() - $this->dateFrom->getTimestamp();

		if ($diffInSeconds < Time::SECONDS_IN_MINUTE)
		{
			return 1;
		}

		return (int)($diffInSeconds / Time::SECONDS_IN_MINUTE);
	}

	public function toArray(): array
	{
		return [
			'dateFrom' => $this->dateFrom->getTimestamp(),
			'dateTo' => $this->dateTo->getTimestamp(),
		];
	}

	public function isEventRecurring(): bool
	{
		return false;
	}

	public function getEventDatePeriod(): DatePeriod
	{
		return $this;
	}

	public function getEventRrule(): ?Rrule
	{
		return null;
	}
}
