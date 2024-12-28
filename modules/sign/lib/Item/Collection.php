<?php

namespace Bitrix\Sign\Item;

use Bitrix\Main\SystemException;
use Bitrix\Sign\Contract;
use Closure;
use Countable;
use IteratorAggregate;

/**
 * @psalm-consistent-constructor
 * @template T of Contract\Item
 * @implements IteratorAggregate<int, T>
 * @implements Contract\ItemCollection<T>
 */
abstract class Collection implements Contract\Item, Contract\ItemCollection, IteratorAggregate, Countable
{
	/** @var T[] */
	private array $items;

	/**
	 * @param T ...$items
	 */
	final public function __construct(Contract\Item ...$items)
	{
		$this->checkItemsClass($items);
		$this->items = $items;
	}

	/**
	 * @return class-string<T>
	 */
	abstract protected function getItemClassName(): string;

	/**
	 * @param T $item
	 */
	final public function add(Contract\Item $item): static
	{
		$this->checkItemClass($item);
		$this->items[] = $item;

		return $this;
	}

	/**
	 * @return T $item
	 */
	final public function getFirst(): ?Contract\Item
	{
		return $this->items[0] ?? null;
	}

	/**
	 * @param T[] $items
	 */
	final public static function fromArray(array $items): static
	{
		return new static(...$items);
	}

	/**
	 * @return T[]
	 */
	final public function toArray(): array
	{
		return $this->items;
	}

	final public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->items);
	}

	final public function count(): int
	{
		return count($this->items);
	}

	final public function isEmpty(): bool
	{
		return empty($this->items);
	}

	public static function emptyList(): static
	{
		return static::fromArray([]);
	}

	private function checkItemClass(Contract\Item $item): void
	{
		$itemClassName = $this->getItemClassName();
		if (!$item instanceof $itemClassName)
		{
			$errorMessage = sprintf('%s: Invalid item class (%s)', static::class, $itemClassName);

			throw new SystemException($errorMessage);
		}
	}

	/**
	 * @param T[] $items
	 */
	private function checkItemsClass(array $items): void
	{
		foreach ($items as $item)
		{
			$this->checkItemClass($item);
		}
	}

	/**
	 * @param Closure(T):bool $rule
	 *
	 * @return T|null
	 */
	final public function findByRule(Closure $rule): ?Contract\Item
	{
		foreach ($this->items as $item)
		{
			if ($rule($item))
			{
				return $item;
			}
		}

		return null;
	}

	public function filter(callable $condition): static
	{
		$items = array_filter(
			$this->items,
			$condition,
		);

		return self::fromArray($items);
	}

	/**
	 * @param Closure(T, T): bool $rule
	 */
	public function sortByRule(Closure $rule): static
	{
		$items = $this->items;
		usort($items, $rule);

		return self::fromArray($items);
	}
}
