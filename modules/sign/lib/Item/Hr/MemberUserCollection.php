<?php

namespace Bitrix\Sign\Item\Hr;

use Bitrix\Sign\Contract\ItemCollection;

final class MemberUserCollection implements ItemCollection, \IteratorAggregate, \Countable
{
	/** @var \ArrayIterator<MemberUser> */
	private \ArrayIterator $iterator;

	public function __construct(MemberUser ...$items)
	{
		$this->iterator = new \ArrayIterator($items);
	}

	public function add(MemberUser $item): MemberUserCollection
	{
		$this->iterator->append($item);

		return $this;
	}

	public function getByMemberId(int $id): ?MemberUser
	{
		/** @var MemberUser $company */
		foreach ($this->iterator as $item)
		{
			if ($item->id === $id)
			{
				return $item;
			}
		}

		return null;
	}

	public function getByUserId(int $id): ?MemberUser
	{
		/** @var MemberUser $item */
		foreach ($this->iterator as $item)
		{
			if ($item->id === $id)
			{
				return $item;
			}
		}

		return null;
	}

	/**
	 * @return array|int[]
	 */
	public function getUserIds(): array
	{
		$ids = [];
		/** @var MemberUser $item */
		foreach ($this->iterator as $item)
		{
			$ids[] = $item->userId;
		}

		return $ids;
	}

	/**
	 * @return array|int[]
	 */
	public function getMemberIds(): array
	{
		$ids = [];
		/** @var MemberUser $item */
		foreach ($this->iterator as $item)
		{
			$ids[] = $item->memberId;
		}

		return $ids;
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
}
