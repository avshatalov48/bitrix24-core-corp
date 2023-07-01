<?php

namespace Bitrix\Crm\Counter\Lighter;

use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;

abstract class LighterQueries
{
	/**
	 * Must return array of `activities` identifiers to reset counters.
	 * @return int[]
	 */
	abstract public function queryActivityIdsToLightCounters(): array;

	/**
	 * Must return array of `activities` data array with filled `LIGHT_COUNTER_AT` field
	 * @param int[] $ids
	 * @return array
	 */
	abstract public function queryActivitiesByIds(array $ids): array;

	public function queryEntitiesInfo(GroupedBindings $groupedBindings): EntitiesInfo
	{
		$result = [];
		foreach ($groupedBindings as $ownerTypeId => $ownerIds)
		{
			$factory = Container::getInstance()->getFactory($ownerTypeId);
			if (!$factory)
			{
				continue;
			}
			if (!$factory->isCountersEnabled())
			{
				continue;
			}

			$categoryIdFieldName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_CATEGORY_ID);
			$assignedByFiledName = $factory->getEntityFieldNameByMap(Item::FIELD_NAME_ASSIGNED);

			$select = [
				Item::FIELD_NAME_ID,
			];
			if ($factory->isFieldExists(Item::FIELD_NAME_ASSIGNED))
			{
				$select[] = $assignedByFiledName;
			}
			if ($factory->isFieldExists(Item::FIELD_NAME_CATEGORY_ID))
			{
				$select[] = $categoryIdFieldName;
			}

			$entities = $factory->getDataClass()::query()
				->setSelect($select)
				->whereIn('ID', $ownerIds)
				->fetchAll();

			foreach ($entities as $entity)
			{
				$category = $entity[$categoryIdFieldName] ?? null;
				if ($category !== null)
				{
					$category = (int)$category;
				}

				$result[] = [
					'OWNER_TYPE_ID' => $ownerTypeId,
					'OWNER_ID' => (int)$entity[Item::FIELD_NAME_ID],
					'CATEGORY_ID' => $category,
					'ASSIGNED_ID' => (int)$entity[$assignedByFiledName]
				];
			}
		}

		return new EntitiesInfo($result);
	}

	/**
	 * Retrieves and groups the bindings for the given activity IDs.
	 *
	 * @param int[] $activityIds The array of activity IDs to retrieve bindings for.
	 * @return GroupedBindings The grouped bindings object.
	 */
	public function queryGroupedBindings(array $activityIds): GroupedBindings
	{
		$groupedBindings = new GroupedBindings();
		if (empty($activityIds)) {
			return $groupedBindings;
		}

		$bindings = ActivityBindingTable::query()
			->addSelect('OWNER_TYPE_ID')
			->addSelect('OWNER_ID')
			->whereIn('ACTIVITY_ID', $activityIds)
			->addOrder('OWNER_TYPE_ID')
			->fetchAll();

		foreach ($bindings as $binding) {
			$ownerTypeId = (int) $binding['OWNER_TYPE_ID'];
			$ownerId = (int) $binding['OWNER_ID'];
			$groupedBindings->add($ownerTypeId, $ownerId);
		}

		return $groupedBindings;
	}
}