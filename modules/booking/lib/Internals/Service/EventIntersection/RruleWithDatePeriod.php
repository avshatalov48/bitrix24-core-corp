<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\EventIntersection;

use Bitrix\Booking\Entity\DatePeriod;
use Bitrix\Booking\Internals\Service\Rrule;
use Bitrix\Booking\Internals\Service\Time;
use DateTimeZone;
use DateTime;

class RruleWithDatePeriod
{
	private int $iterationsRequired = 0;

	public function doIntersect(
		Rrule $rrule,
		DatePeriod $datePeriod
	): bool
	{
		$this->iterationsRequired = 0;

		if ($rrule->getRrule()->getStartDate() >= $datePeriod->getDateTo())
		{
			return false;
		}

		if (
			$rrule->getUntil() !== null
			&& $rrule->getUntil() <= $datePeriod->getDateFrom()
		)
		{
			return false;
		}

		if (self::isDatePeriodInsideRruleExDate($rrule, $datePeriod))
		{
			return false;
		}

		if (
			!$datePeriod->isGreaterThanDay()
			&& !$rrule->getDatePeriod()->isGreaterThanDay()
		)
		{
			/**
			 * Different days of week
			 */
			$byDay = $rrule->getRrule()->getByDay();

			if (
				!empty($byDay)
				&& !in_array(
					Time::getDayCode(
						$datePeriod->getDateFrom()->setTimezone(
							new DateTimeZone(
								$rrule->getDatePeriod()->getDateFrom()->getTimezone()->getName()
							)
						)
					),
					$byDay,
					true
				)
			)
			{
				return false;
			}

			/**
			 * Different months
			 */
			$byMonth = $rrule->getRrule()->getByMonth();
			if (
				!empty($byMonth)
				&& !in_array(
					(int)$datePeriod->getDateFrom()->setTimezone(
						new DateTimeZone(
							$rrule->getDatePeriod()->getDateFrom()->getTimezone()->getName()
						)
					)->format('n'),
					array_map('intval', $rrule->getRrule()->getByMonth()),
					true
				)
			)
			{
				return false;
			}

			/**
			 * Different week numbers
			 */
			$byWeekNumber = $rrule->getRrule()->getByWeekNumber();
			if (
				!empty($byWeekNumber)
				&& !in_array(
					(int)$datePeriod->getDateFrom()->setTimezone(
						new DateTimeZone(
							$rrule->getDatePeriod()->getDateFrom()->getTimezone()->getName()
						)
					)->format('W'),
					array_map('intval', $byWeekNumber),
					true
				)
			)
			{
				return false;
			}
		}

		$doIntersectBasedOnTime = self::doIntersectBasedOnTime($rrule->getDatePeriod(), $datePeriod);
		if ($doIntersectBasedOnTime === false)
		{
			return false;
		}

		if (
			$rrule->getRrule()->getInterval() === 1
			&& $doIntersectBasedOnTime === true
			&& $rrule->getUntil() !== null
			&& $rrule->getUntil() > $datePeriod->getDateFrom()
		)
		{
			return true;
		}

		return $this->doIntersectViaFullLoop($rrule, $datePeriod);
	}

	public function getIterationsRequired(): int
	{
		return $this->iterationsRequired;
	}

	private function doIntersectViaFullLoop(
		Rrule $rrule,
		DatePeriod $datePeriod
	): bool
	{
		$datePeriod1s = $rrule->getDatePeriodsSequence();
		foreach ($datePeriod1s as $datePeriod1)
		{
			$this->iterationsRequired++;

			$rruleDateFrom = $datePeriod1->getDateFrom();
			$rruleDateTo = $datePeriod1->getDateTo();

			$datePeriodDateFrom = $datePeriod->getDateFrom();
			$datePeriodDateTo = $datePeriod->getDateTo();

			if (
				(
					$rruleDateFrom >= $datePeriodDateFrom
					&& $rruleDateFrom < $datePeriodDateTo
				)
				|| (
					$datePeriodDateFrom >= $rruleDateFrom
					&& $datePeriodDateFrom < $rruleDateTo
				)
			)
			{
				return true;
			}

			if ($rruleDateFrom >= $datePeriodDateTo)
			{
				return false;
			}
		}

		return false;
	}

	private static function isDatePeriodInsideRruleExDate(Rrule $rrule, DatePeriod $datePeriod): bool
	{
		$exDates = $rrule->getRrule()->getExDates();
		foreach ($exDates as $exDate)
		{
			$exDateFrom = DateTime::createFromInterface($exDate->date)->setTime(0, 0);
			$exDateTo = DateTime::createFromInterface($exDate->date)->setTime(24, 0, 0);

			if (
				$datePeriod->getDateFrom()->getTimestamp() >= $exDateFrom->getTimestamp()
				&& $datePeriod->getDateFrom()->getTimestamp() <= $exDateTo->getTimestamp()
				&& $datePeriod->getDateTo()->getTimestamp() >= $exDateFrom->getTimestamp()
				&& $datePeriod->getDateTo()->getTimestamp() <= $exDateTo->getTimestamp()
			)
			{
				return true;
			}
		}

		return false;
	}

	public static function doIntersectBasedOnTime(DatePeriod $datePeriod1, DatePeriod $datePeriod2): ?bool
	{
		$datePeriod1 = $datePeriod1->setTimezone('UTC');
		$datePeriod2 = $datePeriod2->setTimezone('UTC');

		if (
			$datePeriod1->isGreaterThanDay()
			|| $datePeriod2->isGreaterThanDay()
		)
		{
			return true;
		}

		$datePeriod1Length = $datePeriod1->getDateTo()->getTimestamp() - $datePeriod1->getDateFrom()->getTimestamp();
		$datePeriod2Length = $datePeriod2->getDateTo()->getTimestamp() - $datePeriod2->getDateFrom()->getTimestamp();

		if (
			(
				$datePeriod2->isOverMidnight()
				&& $datePeriod1->isOverMidnight()
			)
			|| (
				!$datePeriod2->isOverMidnight()
				&& !$datePeriod1->isOverMidnight()
			)
		)
		{
			return self::doIntersectBasedOnTimeInternal(
				$datePeriod1->getDateFrom(),
				$datePeriod2->getDateFrom(),
				$datePeriod1Length,
				$datePeriod2Length,
				false,
				false
			);
		}

		if ($datePeriod1->isOverMidnight())
		{
			return (
				self::doIntersectBasedOnTimeInternal(
					$datePeriod1->getDateFrom(),
					$datePeriod2->getDateFrom(),
					$datePeriod1Length,
					$datePeriod2Length,
					false,
					false
				)
				|| self::doIntersectBasedOnTimeInternal(
					$datePeriod1->getDateFrom(),
					$datePeriod2->getDateFrom(),
					$datePeriod1Length,
					$datePeriod2Length,
					false,
					true
				)
			);
		}

		return (
			self::doIntersectBasedOnTimeInternal(
				$datePeriod1->getDateFrom(),
				$datePeriod2->getDateFrom(),
				$datePeriod1Length,
				$datePeriod2Length,
				false,
				false
			)
			||
			self::doIntersectBasedOnTimeInternal(
				$datePeriod1->getDateFrom(),
				$datePeriod2->getDateFrom(),
				$datePeriod1Length,
				$datePeriod2Length,
				true,
				false
			)
		);
	}

	private static function doIntersectBasedOnTimeInternal(
		\DateTimeImmutable $datePeriod1From,
		\DateTimeImmutable $datePeriod2From,
		int $datePeriod1Length,
		int $datePeriod2Length,
		bool $dayPeriod1NextDay,
		bool $dayPeriod2NextDay
	): bool
	{
		$year = (int)(new \DateTimeImmutable())->format('Y');
		$month = 1;
		$day = 1;

		$datePeriod1From = $datePeriod1From->setDate($year, $month, $day + ($dayPeriod1NextDay ? 1 : 0));
		$datePeriod2From = $datePeriod2From->setDate($year, $month, $day + ($dayPeriod2NextDay ? 1 : 0));

		return (new DatePeriodWithDatePeriod())->doIntersect(
			new DatePeriod(
				$datePeriod1From,
				$datePeriod1From->add(
					new \DateInterval('PT' . $datePeriod1Length . 'S')
				)
			),
			new DatePeriod(
				$datePeriod2From,
				$datePeriod2From->add(
					new \DateInterval('PT' . $datePeriod2Length . 'S')
				)
			),
		);
	}
}
