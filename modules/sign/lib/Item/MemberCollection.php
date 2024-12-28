<?php

namespace Bitrix\Sign\Item;

use ArrayIterator;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Helper\IterationHelper;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;
use Closure;
use Countable;
use Iterator;

class MemberCollection implements Contract\Item, Contract\ItemCollection, Iterator, Countable
{
	protected ?int $queryTotal = null;
	private array $items;
	/** @var ArrayIterator<Member> */
	private ArrayIterator $iterator;

	public function __construct(Member ...$items)
	{
		$this->items = $items;
		$this->iterator = new ArrayIterator($this->items);
	}

	public function add(Member $item): static
	{
		$this->items[] = $item;

		return $this;
	}

	public function clear(): static
	{
		$this->items = [];

		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function current(): ?Member
	{
		return $this->iterator->current();
	}

	public function next(): void
	{
		$this->iterator->next();
	}

	public function key(): int
	{
		return $this->iterator->key();
	}

	public function valid(): bool
	{
		return $this->iterator->valid();
	}

	public function rewind(): void
	{
		$this->iterator = new ArrayIterator($this->items);
	}

	public function count(): int
	{
		return count($this->items);
	}

	public function isEmpty(): bool
	{
		return empty($this->items);
	}

	public function getFirst(): ?Member
	{
		return $this->items[0] ?? null;
	}

	/**
	 * @param Closure(Member): bool $rule
	 * @return static
	 */
	public function filter(Closure $rule): static
	{
		$result = new static();
		foreach ($this as $item)
		{
			if ($rule($item))
			{
				$result->add($item);
			}
		}

		return $result;
	}

	public function filterByParty(int $party): static
	{
		return $this->filter(static fn(Member $member) => $member->party === $party);
	}

	/**
	 * @param array<string> $statusList
	 * @return $this
	 */
	public function filterByStatus(array $statusList): static
	{
		return $this->filter(static fn(Member $member) => in_array($member->status, $statusList));
	}

	/**
	 * @param Closure(Member): bool $rule
	 * @return Member|null
	 */
	final public function findFirst(Closure $rule): ?Member
	{
		foreach ($this as $item)
		{
			if ($rule($item))
			{
				return $item;
			}
		}

		return null;
	}

	/**
	 * @param array<MemberStatus::*> $statuses
	 *
	 * @return Member|null
	 */
	final public function findFirstByStatuses(array $statuses): ?Member
	{
		return $this->findFirst(static fn(Member $member): bool => in_array($member->status, $statuses, true));
	}

	final public function filterByStatuses(array $statuses): static
	{
		return $this->filter(static fn(Member $member): bool => in_array($member->status, $statuses, true));
	}

	final public function findFirstByParty(int $party): ?Member
	{
		foreach ($this as $item)
		{
			if ($item->party === $party)
			{
				return $item;
			}
		}

		return null;
	}

	final public function findFirstByRole(?string $role): ?Member
	{
		foreach ($this as $item)
		{
			if ($item->role === $role)
			{
				return $item;
			}
		}

		return null;
	}

	/**
	 * @return list<?int>
	 */
	final public function getIds(): array
	{
		$result = [];
		foreach ($this as $member)
		{
			$result[] = $member->id;
		}

		return $result;
	}

	final public function sort(Closure $rule): static
	{
		$result = $this->items;
		usort($result, $rule);

		return new static(...$result);
	}

	final public function all(Closure $rule): bool
	{
		return IterationHelper::all($this, $rule);
	}

	public function filterByRole(string $role): static
	{
		return $this->filter(static fn(Member $member) => $member->role === $role);
	}

	/**
	 * @param Role::* ...$roles
	 *
	 * @return $this
	 */
	public function filterByRoles(string... $roles): static
	{
		return $this->filter(static fn(Member $member): bool => in_array($member->role, $roles, true));
	}

	public function filterExcludeRoles(string... $roles): static
	{
		return $this->filter(static fn(Member $member): bool => !in_array($member->role, $roles, true));
	}

	public function setQueryTotal(int $total): static
	{
		$this->queryTotal = $total;

		return $this;
	}

	public function getQueryTotal(): ?int
	{
		return $this->queryTotal;
	}
}
