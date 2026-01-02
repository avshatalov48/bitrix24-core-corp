<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Type;

use Bitrix\HumanResources\Contract\Type\Collection;

abstract class BaseCollection implements Collection
{
	protected array $items = [];

	public function add(mixed $item): static
	{
		if (!$this->isValid($item))
		{
			throw new \InvalidArgumentException();
		}

		$this->items[] = $item;

		return $this;
	}

	public function remove(int $item): static
	{
		foreach ($this->items as $key => $value)
		{
			if ($value === $item)
			{
				unset($this->items[$key]);
			}
		}

		return $this;
	}

	public function has(mixed $item): bool
	{
		if (in_array($item, $this->items, true))
		{
			return true;
		}

		return false;
	}

	public function getItems(): array
	{
		return $this->items;
	}

	public function count(): int
	{
		return count($this->items);
	}

	public function first(): mixed
	{
		return reset($this->items);
	}

	public function last(): mixed
	{
		return end($this->items);
	}

	final public function empty(): bool
	{
		return empty($this->items);
	}

	abstract protected function isValid(mixed $item): bool;
}