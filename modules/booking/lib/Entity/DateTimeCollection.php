<?php

namespace Bitrix\Booking\Entity;

use DateTimeImmutable;
use ArrayIterator;
use IteratorAggregate;
use Countable;

class DateTimeCollection implements IteratorAggregate, Countable
{
	/** @var $items DateTimeImmutable[] */
	protected array $items = [];

	public function getItems(): array
	{
		return $this->items;
	}

	/**
	 * @return DateTimeImmutable[]
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->items);
	}

	public function count(): int
	{
		return count($this->items);
	}

	public function isEmpty(): bool
	{
		return empty($this->items);
	}

	public function isEqual(DateTimeCollection $dateTimeCollection): bool
	{
		$normalize = static function (DateTimeImmutable $item)
		{
			return $item->format('Y-m-d H:i:s') . ' ' . $item->getTimezone()->getName();
		};

		return array_map($normalize, $this->items) === array_map($normalize, $dateTimeCollection->getItems());
	}

	public function add(DateTimeImmutable ...$dates): void
	{
		foreach ($dates as $date)
		{
			$this->items[] = $date;
		}
	}

	public function diff(DateTimeCollection $dateTimeCollection): self
	{
		$result = new DateTimeCollection();

		foreach ($this->items as $dateTime1)
		{
			$found = false;
			foreach ($dateTimeCollection as $dateTime2)
			{
				if ($dateTime2->getTimestamp() === $dateTime1->getTimestamp())
				{
					$found = true;

					break;
				}
			}

			if (!$found)
			{
				$result->add($dateTime1);
			}
		}

		foreach ($dateTimeCollection as $dateTime2)
		{
			$found = false;
			foreach ($this->items as $dateTime1)
			{
				if ($dateTime2->getTimestamp() === $dateTime1->getTimestamp())
				{
					$found = true;

					break;
				}
			}

			if (!$found)
			{
				$result->add($dateTime2);
			}
		}

		return $result;
	}
}
