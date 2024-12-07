<?php

namespace Bitrix\Sign\Item\DateTime;

use ArrayIterator;
use Bitrix\Sign\Contract;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, DateInterval>
 */
class DateIntervalCollection implements Contract\ItemCollection, IteratorAggregate
{
	private array $items;

	public function __construct(DateInterval ...$items)
	{
		$this->items = $items;
	}

	public function add(DateInterval $item): static
	{
		$this->items[] = $item;

		return $this;
	}

	public function getWithMinimalStartDate(): ?DateInterval
	{
		$minimalStartDate = null;
		foreach ($this as $item)
		{
			if ($minimalStartDate === null || $item->start < $minimalStartDate->start)
			{
				$minimalStartDate = $item;
			}
		}

		return $minimalStartDate;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->items);
	}
}