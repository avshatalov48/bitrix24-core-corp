<?php

namespace Bitrix\Crm\Timeline\Rest\HistoryItem;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Rest\TypeCast\OrmTypeCast;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Converter;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Traits\Singleton;

class ListAnswerMaker
{
	use Singleton;

	private Converter\OrmObject $converter;
	private OrmTypeCast $ormTypeCast;
	private TimelineItemManager $timelineItemManager;


	public function __construct()
	{
		$this->ormTypeCast = OrmTypeCast::getInstance();
		$this->converter = Container::getInstance()->getOrmObjectConverter();
		$this->timelineItemManager = TimelineItemManager::getInstance();
	}

	public function makeAnswer(ListParams\Select $select, UserPermissions $userPermissions, array $rows): array
	{
		$items = $this->prepareTimelineItems($rows, $select, $userPermissions);

		$rows = $this->filterFieldsBySelectParam($select, $rows);

		$rows = $this->castValuesToCorrectType($rows);
		$rows = $this->converter->convertKeysToCamelCase($rows);

		return $this->appendItemSpecificFields($select, $rows, $items);
	}

	private function appendItemSpecificFields(ListParams\Select $select, array $rows, array $items): array
	{
		if ($select->hasLayoutField())
		{
			$rows = $this->appendLayoutToResult($rows, $items);
		}

		return $rows;
	}

	/**
	 * @param array $rows
	 * @param ListParams\Select $select
	 * @param UserPermissions $userPermissions
	 * @return array
	 */
	private function prepareTimelineItems(array $rows, ListParams\Select $select, UserPermissions $userPermissions): array
	{
		/** @var Item[] $items */
		$items = [];

		if ($select->hasLayoutField())
		{
			$rows = $this->timelineItemManager->addAssociatedEntityDataToTimelineRows($rows, $userPermissions);

			foreach ($rows as $row)
			{
				$id = (string)$row['ID'];
				$bindings = $row['BINDINGS'][0] ?? [];
				['ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId] = $bindings;

				if ($entityTypeId === null || $entityId === null)
				{
					continue;
				}

				$items[$id] = $this->timelineItemManager->makeTimelineItem(
					$row,
					$userPermissions,
					new ItemIdentifier($entityTypeId, $entityId)
				);
			}
		}

		return $items;
	}

	private function filterFieldsBySelectParam(ListParams\Select $select, array $rows): array
	{
		$result = [];
		foreach ($rows as $row)
		{
			$result[] = array_filter($row, function (string $fieldName) use ($select) {
				return in_array($fieldName, $select->getAllFields());
			}, ARRAY_FILTER_USE_KEY);
		}

		return $result;
	}

	private function castValuesToCorrectType(array $rows): array
	{
		foreach ($rows as &$row)
		{
			$row = $this->ormTypeCast->castRecord(TimelineTable::class, $row);
		}

		return $rows;
	}

	/**
	 * @param array $rows
	 * @param Item[] $items
	 * @return array
	 */
	private function appendLayoutToResult(array $rows, array $items): array
	{
		foreach ($rows as &$row)
		{
			$item = $items[$row['id']] ?? null;
			if ($item instanceof Item\Configurable)
			{
				$row['layout'] = (new \Bitrix\Crm\Service\Timeline\Layout\Converter($item->getLayout()))->toArray();
			}
			else
			{
				$row['layout'] = null;
			}
		}

		return $rows;
	}
}