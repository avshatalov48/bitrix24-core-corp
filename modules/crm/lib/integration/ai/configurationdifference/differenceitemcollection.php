<?php

namespace Bitrix\Crm\Integration\AI\ConfigurationDifference;

use Iterator;

final class DifferenceItemCollection implements Iterator
{
	/** @var array<string|int, DifferenceItem> */
	private array $items = [];

	public function __construct(array $items = [])
	{
		array_map([$this, 'push'], $items);
	}

	public function push(DifferenceItem $item): self
	{
		$this->items[$item->id()] = $item;

		return $this;
	}

	public function get(string|int $id): ?DifferenceItem
	{
		return $this->items[$id] ?? null;
	}

	public function unset(string|int $id): self
	{
		unset($this->items[$id]);

		return $this;
	}

	public function count(): int
	{
		return count($this->items);
	}

	/**
	 * @return array<int|string>
	 */
	public function ids(): array
	{
		$ids = [];
		foreach ($this->items as $item)
		{
			$ids[] = $item->id();
		}

		return $ids;
	}

	public function current(): false|DifferenceItem
	{
		return current($this->items);
	}

	public function next(): void
	{
		next($this->items);
	}

	public function key(): string|int|null
	{
		return key($this->items);
	}

	public function valid(): bool
	{
		return $this->key() !== null;
	}

	public function rewind(): void
	{
		reset($this->items);
	}
}
