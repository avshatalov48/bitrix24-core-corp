<?php

namespace Bitrix\Tasks\Member\Type;

use ArrayIterator;
use Bitrix\Main\Type\Contract\Arrayable;
use IteratorAggregate;

class MemberCollection implements IteratorAggregate, Arrayable
{
	private array $items = [];

	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->items);
	}

	public function get(string $role): static
	{
		$members = new static();
		if (!isset($this->items[$role]))
		{
			return $members;
		}

		array_map(function (Member $member) use ($members): void {
			$members->add($member);
		}, $this->items[$role]);

		return $members;
	}

	public function add(Member $member): static
	{
		$this->items[] = $member;
		return $this;
	}

	public function set(string $role, Member $member): static
	{
		if (isset($this->items[$role]))
		{
			$this->items[$role][] = $member;
		}
		else
		{
			$this->items[$role] = [$member];
		}

		return $this;
	}

	public function merge(self $collection): static
	{
		foreach ($collection as $item)
		{
			$this->add($item);
		}

		return $this;
	}

	public function pop(): ?Member
	{
		return array_pop($this->items);
	}

	public function isEmpty(string $role = ''): bool
	{
		return empty($role)
			? empty($this->items)
			: empty($this->items[$role] ?? []);
	}

	public function getUserIds(bool $unique = false): array
	{
		if ($this->isEmpty())
		{
			return [];
		}

		$userIds = array_map(
			static fn(Member $member): int => $member->getUserId(),
			$this->toArray());

		return $unique ? array_unique($userIds) : $userIds;
	}

	public function clear(): static
	{
		$this->items = [];
		return $this;
	}

	public function toArray(): array
	{
		return iterator_to_array($this);
	}
}
