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

	/**
	 * @param \Bitrix\HumanResources\Contract\Item $item
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
	 * @return ArrayIterator<int|string, V>
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->itemMap);
	}

	public function empty(): bool
	{
		try
		{
			return $this->getIterator()->count() === 0;
		}
		catch (\Exception)
		{
			return true;
		}
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
	 * @param Closure(V): bool $rule
	 *
	 * @return static
	 * @throws WrongStructureItemException
	 */
	public function filter(\Closure $rule): static
	{
		$collection = new static();

		foreach ($this->itemMap as $id => $item)
		{
			if ($rule($item))
			{
				$collection->add($item);
			}
		}

		return $collection;
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
}