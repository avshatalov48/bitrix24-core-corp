<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity;

use Bitrix\Main\Type\Contract\Arrayable;

class DatePeriodCollection implements \IteratorAggregate, Arrayable, \Countable
{
	/** @var $items DatePeriod[] */
	protected array $items = [];

	public function getitems(): array
	{
		return $this->items;
	}

	public function toArray(): array
	{
		return array_map(static fn ($collectionItem): array => $collectionItem->toArray(), $this->items);
	}

	/**
	 * @return DatePeriod[]
	 */
	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->items);
	}

	public function count(): int
	{
		return count($this->items);
	}

	public function isEmpty(): bool
	{
		return empty($this->items);
	}

	public function add(DatePeriod $datePeriod): void
	{
		$this->items[] = $datePeriod;
	}
}
