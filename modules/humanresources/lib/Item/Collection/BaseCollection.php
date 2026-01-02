<?php

namespace Bitrix\HumanResources\Item\Collection;

use ArrayIterator;
use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Contract\ItemCollection;
use Bitrix\HumanResources\Exception\WrongStructureItemException;

/**
 * @psalm-consistent-constructor
 * @psalm-consistent-templates
 * @implements ItemCollection<int|string, V>
 * @template V of Item
 */
abstract class BaseCollection implements ItemCollection
{
	/** @var array<int|string, V> */
	protected array $itemMap = [];
	/** @var array<class-string<self>, class-string<Item>> */
	private static array $reflectionMap = [];
	protected int $totalCount = 0;

	/**
	 * @param \Bitrix\HumanResources\Contract\Item ...$items
	 *
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 */
	public function __construct(Item ...$items)
	{
		if (!$items)
		{
			return;
		}

		foreach ($items as $item)
		{
			$this->add($item);
		}
	}

	/**
	 * @param \Bitrix\HumanResources\Contract\Item $item
	 *
	 * @return $this
	 */
	public function remove(Item $item): static
	{
		unset($this->itemMap[$item->id]);

		return $this;
	}

	final public function slice(int $offset, int $length): static
	{
		return new static(...array_slice($this->itemMap, $offset, $length));
	}

	/**
	 * @param V $item
	 *
	 * @return static
	 * @throws \Bitrix\HumanResources\Exception\WrongStructureItemException
	 */
	public function add(Item $item): static
	{
		if (!$this->validate($item))
		{
			throw new WrongStructureItemException('Item instance of: ' . get_class($item));
		}

		$this->itemMap[$item->id ?? sha1(random_bytes(10))] = $item;

		return $this;
	}

	public static function emptyList(): static
	{
		return new static();
	}

	protected function validate(Item $item): bool
	{
		$itemClass = $this->getItemClass();

		if (!($item instanceof $itemClass))
		{
			return false;
		}

		return true;
	}

	/**
	 * @template T
	 * @param callable(mixed...): T $closure
	 *
	 * @return array<int|string, T>
	 */
	public function map(callable $closure): array
	{
		return array_map($closure, $this->itemMap);
	}

	/**
	 * @param int $id
	 *
	 * @return V
	 */
	public function getItemById(int $id): mixed
	{
		if (isset($this->itemMap[$id]))
		{
			return $this->itemMap[$id];
		}

		return null;
	}

	/**
	 * @return array<V>
	 */
	public function getItemMap(): array
	{
		return $this->itemMap;
	}

	/**
	 * @return array<V>
	 */
	public function getValues(): array
	{
		return array_values($this->itemMap);
	}

	/**
	 * @return array<int|string>
	 */
	public function getKeys(): array
	{
		return array_keys($this->itemMap);
	}

	/**
	 * @return ArrayIterator<int|string, V>
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->itemMap);
	}

	public function empty(): bool
	{
		return empty($this->itemMap);
	}

	public function count(): int
	{
		return $this->getIterator()->count();
	}

	public function totalCount(): int
	{
		return $this->totalCount === 0 ? $this->count() : $this->totalCount;
	}

	public function setTotalCount(int $count): static
	{
		$this->totalCount = $count;

		return $this;
	}

	/**
	 * @param Closure(V, int|string, static): bool $rule
	 *
	 * @return static
	 * @throws WrongStructureItemException
	 */
	public function filter(\Closure $rule): static
	{
		$collection = new static();

		foreach ($this->itemMap as $id => $item)
		{
			if ($rule($item, $id, $this))
			{
				$collection->add($item);
			}
		}

		return $collection;
	}

	/**
	 * @param Closure(V): bool $rule
	 *
	 * @return bool
	 */
	final public function exists(\Closure $rule): bool
	{
		foreach ($this->itemMap as $item)
		{
			if ($rule($item))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Closure(V): bool $rule
	 *
	 * @return V|null
	 */
	public function findFirstByRule(\Closure $rule): ?Item
	{
		foreach ($this->itemMap as $item)
		{
			if ($rule($item))
			{
				return $item;
			}
		}

		return null;
	}

	private function getItemClass(): ?string
	{
		if (isset(self::$reflectionMap[static::class]))
		{
			return self::$reflectionMap[static::class];
		}

		$reflectionClass = new \ReflectionClass(static::class);
		$docComment = $reflectionClass->getDocComment();

		preg_match('/@extends BaseCollection<(.*)>/', $docComment, $matches);

		self::$reflectionMap[static::class] = '\\Bitrix\\HumanResources\\' . $matches[1];

		return self::$reflectionMap[static::class];
	}

	/**
	 * @return ?V
	 */
	public function getFirst(): ?Item
	{
		return array_values($this->itemMap)[0] ?? null;
	}

	/**
	 * @return ?V
	 */
	public function getLast(): ?Item
	{
		$count = $this->count();
		if ($count < 1)
		{
			return null;
		}

		return array_values($this->itemMap)[$count - 1] ?? null;
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return Item|null
	 */
	public function __call(string $name, array $arguments)
	{
		if (str_starts_with($name, 'getFirstBy'))
		{
			$property = lcfirst(substr($name, 10));
			$itemClass = $this->getItemClass();

			if (property_exists($itemClass, $property))
			{
				return $this->getFirstByProperty($property, $arguments[0]);
			}
		}

		throw new \BadMethodCallException("Unknown method '{$name}'");
	}

	/**
	 * @param string $property
	 * @param $value
	 *
	 * @return Item|null
	 */
	private function getFirstByProperty(string $property, $value): ?Item
	{
		if (!$value)
		{
			return null;
		}

		foreach ($this as $item)
		{
			if ($item->$property === $value)
			{
				return $item;
			}
		}

		return null;
	}
}
