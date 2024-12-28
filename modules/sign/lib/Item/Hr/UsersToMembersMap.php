<?php

namespace Bitrix\Sign\Item\Hr;

use Bitrix\Sign\Contract\ItemCollection;

final class UsersToMembersMap implements ItemCollection
{
	/** @var array<int, int> */
	private array $data;

	public function __construct()
	{
		$this->data = [];
	}

	public function add(MemberUser $item): self
	{
		$this->data[$item->userId] = $item->memberId;

		return $this;
	}

	public function addCollection(MemberUserCollection $collection): self
	{
		/** @var MemberUser $item */
		foreach ($collection as $item)
		{
			$this->add($item);
		}

		return $this;
	}

	/**
	 * @return array<int, int>
	 */
	public function toArray(): array
	{
		return $this->data;
	}

	public function getMemberId(int $userId): ?int
	{
		return $this->data[$userId] ?? null;
	}
}
