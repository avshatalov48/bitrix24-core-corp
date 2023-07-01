<?php

namespace Bitrix\Crm\Counter\Lighter;

/**
 * Will be contained all entities associated with ready to notify activities
 */
final class EntitiesInfo implements \Traversable, \Iterator
{
	private array $items;

	public function __construct(array $items)
	{
		$this->items = $items;
	}

	/**
	 * Returns an array of assigned IDs by category for a given owner type.
	 *
	 * @param int $ownerTypeId
	 * @return array An associative array where each element contains a category ID and an array of assigned IDs
	 *               associated with that category.
	 */
	public function getAssignedIdsByCategory(int $ownerTypeId): array
	{
		$result = [];
		foreach ($this->items as $item)
		{
			if ($item['OWNER_TYPE_ID'] !== $ownerTypeId)
			{
				continue;
			}

			$category = $item['CATEGORY_ID'];
			$assigned = $item['ASSIGNED_ID'];

			$idx = array_search($category, array_column($result, 'CATEGORY_ID'), true);

			if ($idx === false)
			{
				$result[] = [
					'CATEGORY_ID' => $category,
					'ASSIGNED_IDS' => [$assigned]
				];
			}
			else
			{
				$result[$idx]['ASSIGNED_IDS'][] = $assigned;
			}
		}
		return $result;
	}

	public function uniqueOwnerTypeIds(): array
	{
		return array_unique(array_column($this->items, 'OWNER_TYPE_ID'));
	}

	public function current()
	{
		return current($this->items);
	}

	public function next()
	{
		next($this->items);
	}

	public function key()
	{
		return key($this->items);
	}

	public function valid(): bool
	{
		return null !== key($this->items);
	}

	public function rewind()
	{
		reset($this->items);
	}

}
