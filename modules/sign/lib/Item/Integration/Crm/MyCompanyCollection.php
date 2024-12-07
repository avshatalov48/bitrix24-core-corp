<?php

namespace Bitrix\Sign\Item\Integration\Crm;

use Bitrix\Sign\Contract\ItemCollection;
use Bitrix\Sign\Helper\IterationHelper;

/**
 * @implements \IteratorAggregate<int, MyCompany>
 */
final class MyCompanyCollection implements ItemCollection, \Countable, \IteratorAggregate
{
	private array $items;

	public function __construct(MyCompany ...$items)
	{
		$this->items = $items;
	}

	public function add(MyCompany $item): static
	{
		$this->items[] = $item;

		return $this;
	}

	public function clear(): static
	{
		$this->items = [];

		return $this;
	}

	/**
	 * @return \Iterator<int, MyCompany>
	 */
	public function getIterator(): \Iterator
	{
		return new \ArrayIterator($this->toArray());
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function count(): int
	{
		return count($this->toArray());
	}

	final public function findFirst(\Closure $rule): MyCompany
	{
		return IterationHelper::findFirstByRule($this, $rule);
	}

	/**
	 * @return array<string>
	 */
	public function listTaxIds(): array
	{
		$result = [];
		foreach ($this as $item)
		{
			if ($item->taxId !== null)
			{
				$result[] = $item->taxId;
			}
		}

		return $result;
	}

	/**
	 * @return array<?int>
	 */
	public function getIds(): array
	{
		$result = [];
		foreach ($this as $item)
		{
			$result[] = $item->id;
		}

		return $result;
	}

	public function findById(int $id): ?MyCompany
	{
		foreach ($this as $item)
		{
			if ($item->id === $id)
			{
				return $item;
			}
		}

		return null;
	}
}