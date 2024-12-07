<?php

namespace Bitrix\Sign\Item\Integration\SignMobile;

use Bitrix\Sign\Contract\ItemCollection;
use Bitrix\Sign\Helper\IterationHelper;

/**
 * @implements \IteratorAggregate<int, MemberDocument>
 */
final class MemberDocumentCollection implements ItemCollection, \Countable, \IteratorAggregate
{
	private array $items;

	public function __construct(MemberDocument ...$items)
	{
		$this->items = $items;
	}

	public function add(MemberDocument $item): static
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
	 * @return \Iterator<int, MemberDocument>
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

	final public function findFirst(\Closure $rule): MemberDocument
	{
		return IterationHelper::findFirstByRule($this, $rule);
	}

	/**
	 * @return list<?int>
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

	public function findById(int $id): ?MemberDocument
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