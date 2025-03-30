<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Slot;

use Bitrix\Booking\Entity\BaseEntityCollection;
use Bitrix\Booking\Internals\Service\Time;
use DateTimeImmutable;

/**
 * @method \Bitrix\Booking\Entity\Slot\Range|null getFirstCollectionItem()
 * @method Range[] getIterator()
 */
class RangeCollection extends BaseEntityCollection
{
	public function __construct(Range ...$ranges)
	{
		foreach ($ranges as $range)
		{
			$this->collectionItems[] = $range;
		}
	}

	public static function mapFromArray(array $props): self
	{
		$ranges = [];
		foreach ($props as $range)
		{
			$ranges[] = Range::mapFromArray($range);
		}

		return new RangeCollection(...$ranges);
	}

	public function diff(RangeCollection $collectionToCompare): RangeCollection
	{
		if ($collectionToCompare->isEmpty())
		{
			return $this;
		}

		if ($this->isEmpty())
		{
			return $collectionToCompare;
		}

		return new RangeCollection(...$this->baseDiff($collectionToCompare));
	}

	public function merge(
		RangeCollection $rangeCollection,
		DateTimeImmutable $date
	): RangeCollection
	{
		$result = new RangeCollection(...[]);

		/** @var Range $range1 */
		foreach ($this->collectionItems as $range1)
		{
			/** @var Range $range2 */
			foreach ($rangeCollection as $range2)
			{
				$range1TimezoneOffset = (new \DateTimeZone($range1->getTimezone()))->getOffset($date);
				$range2TimezoneOffset = (new \DateTimeZone($range2->getTimezone()))->getOffset($date);

				$rangesTimezoneDiff = $range1TimezoneOffset - $range2TimezoneOffset;

				$range = (new Range())
					->setTimezone($range1->getTimezone());

				$dayWeeks = array_values(
					array_intersect(
						$range1->getWeekDays(),
						$range2->getWeekDays()
					)
				);
				if (empty($dayWeeks))
				{
					continue;
				}
				$range->setWeekDays($dayWeeks);

				$range1SlotSize = $range1->getSlotSize();
				$range2SlotSize = $range2->getSlotSize();

				$range->setSlotSize(
					(
						$range1SlotSize % $range2SlotSize === 0
						|| $range2SlotSize % $range1SlotSize === 0
					)
						? max($range1SlotSize, $range2SlotSize)
						: $range1SlotSize * $range2SlotSize
				);

				$range1From = $range1->getFromInSeconds();
				$range1To = $range1->getToInSeconds();
				if ($range1To < $range1From)
				{
					$range1To = $range1To + Time::SECONDS_IN_DAY;
				}

				$range2From = $range2->getFromInSeconds() + $rangesTimezoneDiff;
				if ($range2From < 0)
				{
					$range2From += Time::SECONDS_IN_DAY;
				}

				$range2To = $range2->getToInSeconds() + $rangesTimezoneDiff;
				if ($range2To < 0)
				{
					$range2To += Time::SECONDS_IN_DAY;
				}

				if ($range2To < $range2From)
				{
					$range2To = $range2To + Time::SECONDS_IN_DAY;
				}

				$leftMargin = max($range1From, $range2From);
				$rightMargin = min($range1To, $range2To);
				if ($leftMargin >= $rightMargin)
				{
					continue;
				}

				if ($rightMargin > Time::SECONDS_IN_DAY)
				{
					$rightMargin -= Time::SECONDS_IN_DAY;
				}

				$range->setTo($rightMargin / Time::SECONDS_IN_MINUTE);
				$range->setFrom($leftMargin / Time::SECONDS_IN_MINUTE);

				$result->add($range);
			}
		}

		return $result;
	}
}
