<?php

namespace Bitrix\Crm\Counter\Lighter;

/**
 * Stores and manages bindings between owner types and their IDs in an array.
 */
final class GroupedBindings implements \Traversable, \Iterator
{
	/**
	 * @var array<int, array<int>> The array of owner type IDs and their corresponding arrays of owner IDs.
	 */
	private array $bindings = [];

	/**
	 * Adds a new binding to the array with the given owner type ID and owner ID.
	 * If the owner type is not yet present in the array, it will be created.
	 * If the owner ID already exists for the given owner type, it will not be added again.
	 *
	 * @param int $ownerTypeId The ID of the owner type.
	 * @param int $ownerId The ID of the owner.
	 * @return void
	 */
	public function add(int $ownerTypeId, int $ownerId): void
	{
		if (!array_key_exists($ownerTypeId, $this->bindings))
		{
			$this->bindings[$ownerTypeId] = [];
		}
		$current = $this->bindings[$ownerTypeId];
		if (in_array($ownerId, $current, true))
		{
			return;
		}
		$this->bindings[$ownerTypeId][] = $ownerId;
	}

	/**
	 * Sets the given array of owner IDs as the new value for the given owner type ID.
	 * Any duplicates will be removed.
	 *
	 * @param int $ownerTypeId The ID of the owner type.
	 * @param int[] $ownerIds The array of owner IDs.
	 * @return void
	 */
	public function set(int $ownerTypeId, array $ownerIds): void
	{
		$this->bindings[$ownerTypeId] = array_unique($ownerIds);
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

	public function current()
	{
		return current($this->bindings);
	}

	public function next()
	{
		next($this->bindings);
	}

	public function key()
	{
		return key($this->bindings);
	}

	public function valid(): bool
	{
		return null !== key($this->bindings);
	}

	public function rewind()
	{
		reset($this->bindings);
	}

}
