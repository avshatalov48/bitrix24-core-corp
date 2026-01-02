<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Service\Yandex\Dto;

use Bitrix\Main\Type\Contract\Arrayable;

abstract class Collection implements \IteratorAggregate, Arrayable
{
	/** @var $items Item[] */
	protected array $items = [];

	public function toArray(): array
	{
		return array_map(static fn ($items): array => $items->toArray(), $this->items);
	}

	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->items);
	}

	abstract public function add(Item $item): Collection;

	public function isEmpty(): bool
	{
		return empty($this->items);
	}
}
