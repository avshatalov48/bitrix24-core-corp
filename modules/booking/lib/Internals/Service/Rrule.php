<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service;

use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Internals\Exception\InvalidArgumentException;
use Bitrix\Booking\Internals\Service\Recurr\Rule;
use Bitrix\Booking\Internals\Service\Recurr\Transformer\ArrayTransformer;
use Bitrix\Booking\Internals\Service\Recurr\Transformer\ArrayTransformerConfig;
use DateTimeImmutable;
use Bitrix\Booking\Internals\Service\Recurr;

class Rrule
{
	/**
	 * Approximately 3 years for daily recurrence pattern
	 */
	private const VIRTUAL_LIMIT = 1000;

	private Recurr\Rule $rrule;

	private DatePeriod $datePeriod;
	private ?array $datePeriodSequence = null;

	public function __construct(
		string $rrule,
		DatePeriod $datePeriod
	)
	{
		$this->rrule = new Recurr\Rule(
			$rrule,
			$datePeriod->getDateFrom(),
			$datePeriod->getDateTo(),
			$datePeriod->getDateFrom()->getTimezone()->getName()
		);
		$this->datePeriod = $datePeriod;

		if (
			$this->rrule->getUntil() === null
			&& $this->rrule->getCount() === null
		)
		{
			throw new InvalidArgumentException('Either until date or count is required');
		}

		$isFrequencySupported = in_array(
			$this->rrule->getFreq(),
			[
				Rule::$freqs['DAILY'],
				Rule::$freqs['WEEKLY'],
				Rule::$freqs['MONTHLY'],
				Rule::$freqs['YEARLY']
			],
			true
		);
		if (!$isFrequencySupported)
		{
			throw new InvalidArgumentException('Specified frequency is not supported');
		}

		if (
			$this->rrule->getFreq() === Rule::$freqs['DAILY']
			&& $datePeriod->isGreaterThanDay()
		)
		{
			throw new InvalidArgumentException('Date period should be less than a day for daily events');
		}

		if (
			!empty($this->rrule->getBySecond())
			|| !empty($this->rrule->getByMinute())
			|| !empty($this->rrule->getByHour())
			|| !empty($this->rrule->getBySetPosition())
		)
		{
			throw new InvalidArgumentException('Not supported');
		}

		if (
			!empty($this->rrule->getByWeekNumber())
			|| !empty($this->rrule->getByMonthDay())
		)
		{
			//@todo we need support fast search with negative numbers in these types of rules first
			throw new InvalidArgumentException('Not supported');
		}

		$exDates = $this->rrule->getExDates();
		foreach ($exDates as $exDate)
		{
			if ($exDate->hasTime === true)
			{
				throw new InvalidArgumentException('EXDATE does not support time specification');
			}
		}
	}

	public function getRrule(): Recurr\Rule
	{
		return $this->rrule;
	}

	public function getUntil(): ?DateTimeImmutable
	{
		$until = $this->rrule->getUntil();
		if ($until)
		{
			return DateTimeImmutable::createFromInterface($until);
		}

		$datePeriods = $this->getDatePeriodsSequence();
		if (!empty($datePeriods))
		{
			return end($datePeriods)->getDateTo();
		}

		return null;
	}

	public function getDatePeriod(): DatePeriod
	{
		return $this->datePeriod;
	}

	/**
	 * @return DatePeriod[]
	 */
	public function getDatePeriodsSequence(): array
	{
		if (!is_null($this->datePeriodSequence))
		{
			return $this->datePeriodSequence;
		}

		$arrayTransformer = new ArrayTransformer(
			(new ArrayTransformerConfig())
				->setVirtualLimit(self::VIRTUAL_LIMIT)
		);

		$this->datePeriodSequence = array_map(
			static function ($recurrence) {
				return new DatePeriod(
					DateTimeImmutable::createFromInterface($recurrence->getStart()),
					DateTimeImmutable::createFromInterface($recurrence->getEnd())
				);
			},
			$arrayTransformer->transform($this->getRrule())
		);

		return $this->datePeriodSequence;
	}
}
