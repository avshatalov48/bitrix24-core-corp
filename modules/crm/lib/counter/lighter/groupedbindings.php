<?php

namespace Bitrix\Crm\Counter\Lighter;

/**
 * Stores and manages bindings between owner types and their IDs in an array.
 */
class GroupedBindings implements \Traversable, \Iterator
{
	/**
	 * @var array<int, array> The array of owner type IDs and their corresponding arrays of owner ID and its Activity ID.
	 */
	private array $bindings = [];

	/**
	 * Adds a new binding to the array with the given owner type ID and owner ID.
	 *
	 * @param int $ownerTypeId The ID of the owner type.
	 * @param int $ownerId The ID of the owner.
	 * @param int $activityId
	 * @return void
	 */
	public function add(int $ownerTypeId, int $ownerId, int $activityId): void
	{
		if (!isset($this->bindings[$ownerTypeId]))
		{
			$this->bindings[$ownerTypeId] = [];
		}

		if (!isset($this->bindings[$ownerTypeId][$ownerId]))
		{
			$this->bindings[$ownerTypeId][$ownerId] = [];
			$this->bindings[$ownerTypeId][$ownerId]['OWNER_ID'] = $ownerId;
			$this->bindings[$ownerTypeId][$ownerId]['ACTIVITY_IDS'] = [];
		}

		$this->bindings[$ownerTypeId][$ownerId]['ACTIVITY_IDS'][] = $activityId;
	}

	/**
	 * Returns an array of owner IDs for the given owner type ID.
	 *
	 * @param int $ownerTypeId The ID of the owner type.
	 * @return array<int> The array of owner IDs.
	 */
	public function get(int $ownerTypeId): array
	{
		return $this->bindings[$ownerTypeId];
	}

	public function current(): array|false
	{
		return current($this->bindings);
	}

	public function next(): void
	{
		next($this->bindings);
	}

	public function key(): ?int
	{
		return key($this->bindings);
	}

	public function valid(): bool
	{
		return null !== key($this->bindings);
	}

	public function rewind(): void
	{
		reset($this->bindings);
	}

}
