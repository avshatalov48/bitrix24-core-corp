<?php

namespace Bitrix\Sign\Item\Hr;

use Bitrix\Sign\Contract\ItemCollection;

final class MemberNodeCollection implements ItemCollection, \IteratorAggregate, \Countable
{
	/** @var \ArrayIterator<MemberNode> */
	private \ArrayIterator $iterator;

	public function __construct(MemberNode ...$items)
	{
		$this->iterator = new \ArrayIterator($items);
	}

	public function add(MemberNode $item): MemberNodeCollection
	{
		$this->iterator->append($item);

		return $this;
	}

	public function toArray(): array
	{
		return $this->iterator->getArrayCopy();
	}

	public function getIterator(): \ArrayIterator
	{
		return $this->iterator;
	}

	public function count(): int
	{
		return $this->getIterator()->count();
	}

	/**
	 * @return int[]
	 */
	public function getUserIds(): array
	{
		$userIds = [];
		/** @var MemberNode $item */
		foreach ($this->getIterator() as $item)
		{
			$userIds[] = $item->userId;
		}
		return $userIds;
	}
}
