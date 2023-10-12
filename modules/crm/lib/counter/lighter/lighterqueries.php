<?php

namespace Bitrix\Crm\Counter\Lighter;

use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;

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

	public function queryEntitiesData(GroupedBindings $groupedBindings): array
	{
		$result = [];
		foreach ($groupedBindings as $ownerTypeId => $binding)
		{
			$ownerIds = array_column($binding, 'OWNER_ID');

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

				$ownerId = (int)$entity[Item::FIELD_NAME_ID];

				$result[] = [
					'OWNER_TYPE_ID' => $ownerTypeId,
					'OWNER_ID' => $ownerId,
					'CATEGORY_ID' => $category,
					'ASSIGNED_ID' => (int)$entity[$assignedByFiledName],
					'ACTIVITY_IDS' => $binding[$ownerId]['ACTIVITY_IDS'] ?? [],
				];
			}
		}

		return $result;
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
			->addSelect('ACTIVITY_ID')
			->whereIn('ACTIVITY_ID', $activityIds)
			->addOrder('OWNER_TYPE_ID')
			->fetchAll();

		foreach ($bindings as $binding) {
			$groupedBindings->add(
				(int)$binding['OWNER_TYPE_ID'],
				(int)$binding['OWNER_ID'],
				(int)$binding['ACTIVITY_ID']
			);
		}

		return $groupedBindings;
	}
}