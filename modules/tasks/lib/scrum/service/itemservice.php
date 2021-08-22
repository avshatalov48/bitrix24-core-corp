<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Result;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Internal\ItemTable;
use Bitrix\Tasks\Scrum\Internal\ItemInfoColumn;

class ItemService implements Errorable
{
	const ERROR_COULD_NOT_ADD_ITEM = 'TASKS_IS_01';
	const ERROR_COULD_NOT_UPDATE_ITEM = 'TASKS_IS_02';
	const ERROR_COULD_NOT_READ_ITEM = 'TASKS_IS_03';
	const ERROR_COULD_NOT_REMOVE_ITEM = 'TASKS_IS_04';
	const ERROR_COULD_NOT_GET_EPIC_TAGS = 'TASKS_IS_05';
	const ERROR_COULD_NOT_GET_EPIC_LIST = 'TASKS_IS_06';
	const ERROR_COULD_NOT_ADD_FILES_ITEM = 'TASKS_IS_07';
	const ERROR_COULD_NOT_GET_UF_ITEM = 'TASKS_IS_08';
	const ERROR_COULD_NOT_MOVE_ITEM = 'TASKS_IS_09';
	const ERROR_COULD_NOT_CHANGE_SORT = 'TASKS_IS_10';
	const ERROR_COULD_NOT_READ_ITEM_INFO = 'TASKS_IS_11';
	const ERROR_COULD_NOT_UPDATE_ITEMS_ENTITY = 'TASKS_IS_12';
	const ERROR_COULD_NOT_READ_ALL_ITEMS = 'TASKS_IS_13';
	const ERROR_COULD_NOT_READ_ITEM_BY_TYPE_ID = 'TASKS_IS_14';
	const ERROR_COULD_NOT_CLEAN_ITEMS_TYPE_ID = 'TASKS_IS_15';

	private $errorCollection;

	private static $allEpicTags = [];
	private static $taskIdsByParentId = [];
	private static $epicInfo = [];

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	public function createTaskItem(ItemTable $item, PushService $pushService = null): ItemTable
	{
		try
		{
			$result = ItemTable::add($item->getFieldsToCreateTaskItem());

			if ($result->isSuccess())
			{
				$item->setId($result->getId());

				if ($pushService)
				{
					$pushService->sendAddItemEvent($item);
				}
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_ADD_ITEM);
			}

			return $item;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_ITEM));
		}

		return $item;
	}

	public function createEpicItem(ItemTable $epic, PushService $pushService = null): ItemTable
	{
		try
		{
			$result = ItemTable::add($epic->getFieldsToCreateEpicItem());

			if ($result->isSuccess())
			{
				$epic->setId($result->getId());

				if ($pushService)
				{
					$pushService->sendAddEpicEvent($epic);
				}
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_ADD_ITEM);
			}

			return $epic;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_ITEM));
		}

		return $epic;
	}

	public function getEpicInfo(int $itemId): array
	{
		if (isset(self::$epicInfo[$itemId]))
		{
			return self::$epicInfo[$itemId];
		}

		self::$epicInfo[$itemId] = [];

		$item = $this->getItemById($itemId);

		if ($item->getId())
		{
			self::$epicInfo[$itemId] = [
				'id' => $item->getId(),
				'name' => $item->getName(),
				'description' => $item->getDescription(),
				'info' => $item->getInfo()->getInfoData(),
			];
		}

		return self::$epicInfo[$itemId];
	}

	public function getAllEpicTags(int $entityId): array
	{
		try
		{
			if (isset(self::$allEpicTags[$entityId]))
			{
				return self::$allEpicTags[$entityId];
			}

			self::$allEpicTags[$entityId] = [];

			$queryObject = ItemTable::getList([
				'select' => [
					'ID', 'NAME', 'DESCRIPTION', 'INFO'
				],
				'filter' => [
					'ENTITY_ID'=> $entityId,
					'ITEM_TYPE'=> ItemTable::EPIC_TYPE,
					'ACTIVE' => 'Y'
				],
			]);
			foreach ($queryObject->fetchAll() as $itemData)
			{
				$itemObject = ItemTable::createItemObject($itemData);
				if (!$itemObject->isEmpty())
				{
					self::$allEpicTags[$entityId][$itemObject->getId()] = [
						'id' => $itemObject->getId(),
						'name' => $itemObject->getName(),
						'description' => $itemObject->getDescription(),
						'info' => $itemObject->getInfo()->getInfoData(),
					];
				}
			}

			return self::$allEpicTags[$entityId];
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_GET_EPIC_TAGS));
			return [];
		}
	}

	/**
	 * Returns a list of items by a parameters for ui grid.
	 *
	 * @param int $entityId
	 * @param array $order
	 * @param PageNavigation $nav
	 * @return array
	 */
	public function getEpicsList(int $entityId, array $order, PageNavigation $nav): array
	{
		try
		{
			$queryObject = ItemTable::getList([
				'select' => [
					'ID', 'NAME', 'CREATED_BY', 'INFO'
				],
				'filter' => [
					'ENTITY_ID'=> $entityId,
					'ITEM_TYPE'=> ItemTable::EPIC_TYPE,
					'ACTIVE' => 'Y'
				],
				'order' => $order,
				'offset' => $nav->getOffset(),
				'limit' => $nav->getLimit(),
				'count_total' => true,
			]);

			$list = [];

			foreach ($queryObject->fetchAll() as $itemData)
			{
				$itemObject = ItemTable::createItemObject($itemData);
				$list[] = $itemObject;
			}

			$nav->setRecordCount($queryObject->getCount());

			return $list;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_GET_EPIC_LIST));
			return [];
		}
	}

	public function getTaskIdsByParentId(int $parentId): array
	{
		if (isset(self::$taskIdsByParentId[$parentId]))
		{
			return self::$taskIdsByParentId[$parentId];
		}

		self::$taskIdsByParentId[$parentId] = [];

		$queryObject = ItemTable::getList([
			'select' => ['SOURCE_ID'],
			'filter' => [
				'PARENT_ID' => $parentId,
				'ITEM_TYPE'=> ItemTable::TASK_TYPE,
				'ACTIVE' => 'Y'
			],
			'order' => ['ID']
		]);
		while ($itemData = $queryObject->fetch())
		{
			self::$taskIdsByParentId[$parentId][] = $itemData['SOURCE_ID'];
		}

		return self::$taskIdsByParentId[$parentId];
	}

	public function getItemById(int $itemId): ItemTable
	{
		$item = ItemTable::createItemObject();

		$queryObject = ItemTable::getList([
			'filter' => ['ID' => $itemId],
			'order' => ['ID']
		]);
		if ($itemData = $queryObject->fetch())
		{
			$item = ItemTable::createItemObject($itemData);
		}

		return $item;
	}

	/**
	 * @param array $itemIds Item ids.
	 * @return ItemTable[]
	 */
	public function getItemsByIds(array $itemIds): array
	{
		try
		{
			$items = [];

			$queryObject = ItemTable::getList([
				'filter' => ['ID' => $itemIds],
				'order' => ['SORT' => 'ASC', 'ID' => 'DESC'],
			]);
			while ($itemData = $queryObject->fetch())
			{
				$item = ItemTable::createItemObject($itemData);

				$items[$item->getId()] = $item;
			}

			return $items;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ALL_ITEMS)
			);
			return [];
		}
	}

	public function attachFilesToItem(\CUserTypeManager $manager, int $itemId, array $files): array
	{
		try
		{
			$ufValues = $manager->getUserFieldValue('TASKS_SCRUM_ITEM', 'UF_SCRUM_ITEM_FILES', $itemId);
			if (is_array($ufValues))
			{
				$ufValues = array_merge($ufValues, $files);
			}
			else
			{
				$ufValues = $files;
			}

			$manager->update('TASKS_SCRUM_ITEM', $itemId, ['UF_SCRUM_ITEM_FILES' => $ufValues]);

			return $ufValues;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_FILES_ITEM)
			);
			return [];
		}
	}

	public function getUserFields(\CUserTypeManager $manager, int $valueId = 0): array
	{
		try
		{
			$fields = $manager->getUserFields('TASKS_SCRUM_ITEM', $valueId);
			$filesFieldName = 'UF_SCRUM_ITEM_FILES';
			if (isset($fields[$filesFieldName]))
			{
				$fields[$filesFieldName]['EDIT_FORM_LABEL'] = $filesFieldName;
				$fields[$filesFieldName]['TAG'] = 'DOCUMENT ID';
				if (is_array($fields[$filesFieldName]['VALUE']))
				{
					$fields[$filesFieldName]['VALUE'] = array_unique($fields[$filesFieldName]['VALUE']);
				}
				else
				{
					$fields[$filesFieldName]['VALUE'] = [];
				}
			}
			return $fields;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_GET_UF_ITEM));
		}
		return [];
	}

	public function getItemsStoryPointsBySourceId(array $sourceIds): array
	{
		try
		{
			$itemsStoryPoints = [];

			$queryObject = ItemTable::getList([
				'select' => ['ID', 'STORY_POINTS', 'SOURCE_ID'],
				'filter' => ['SOURCE_ID' => $sourceIds]
			]);
			while ($itemData = $queryObject->fetch())
			{
				$itemsStoryPoints[$itemData['SOURCE_ID']] = $itemData['STORY_POINTS'] ? $itemData['STORY_POINTS'] : '';
			}

			return $itemsStoryPoints;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ITEM));
		}

		return [];
	}

	public function getItemBySourceId(int $sourceId): ItemTable
	{
		try
		{
			$itemId = 0;
			$queryObject = ItemTable::getList([
				'select' => ['ID'],
				'filter' => [
					'SOURCE_ID' => $sourceId
				],
				'order' => ['SORT' => 'ASC', 'ID' => 'DESC'],
			]);
			if ($itemData = $queryObject->fetch())
			{
				$itemId = $itemData['ID'];
			}
			return $this->getItemById($itemId);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ITEM));
		}

		return ItemTable::createItemObject();
	}

	public function getItemIdsBySourceIds(array $sourceIds, int $entityId = 0, PageNavigation $nav = null): array
	{
		$itemIds = [];

		try
		{
			$filter = ['SOURCE_ID' => $sourceIds];
			if ($entityId)
			{
				$filter['ENTITY_ID'] = $entityId;
			}

			$queryParams = [
				'select' => ['ID'],
				'filter' => $filter,
				'order' => [
					'SORT' => 'ASC',
					'ID' => 'DESC',
				],
			];

			if ($nav)
			{
				$queryParams['offset'] = $nav->getOffset();
				$queryParams['limit'] = $nav->getLimit() + 1;
			}

			$queryObject = ItemTable::getList($queryParams);

			$n = 0;
			while ($itemData = $queryObject->fetch())
			{
				$n++;

				if ($nav && $n > $nav->getPageSize())
				{
					break;
				}

				$itemIds[] = $itemData['ID'];
			}

			if ($nav)
			{
				$nav->setRecordCount($nav->getOffset() + $n);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ITEM));
		}

		return $itemIds;
	}

	public function getItemIdsByTypeId(int $typeId): array
	{
		$itemIds = [];

		try
		{
			$queryObject = ItemTable::getList([
				'select' => ['ID'],
				'filter' => ['TYPE_ID' => $typeId],
				'order' => ['ID' => 'DESC'],
			]);
			while ($itemData = $queryObject->fetch())
			{
				$itemIds[] = $itemData['ID'];
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_READ_ITEM_BY_TYPE_ID
				)
			);
		}

		return $itemIds;
	}

	public function cleanTypeIdToItems(array $itemIds): void
	{
		try
		{
			if ($itemIds)
			{
				ItemTable::updateMulti($itemIds, ['TYPE_ID' => null]);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_CLEAN_ITEMS_TYPE_ID
				)
			);
		}
	}

	public function moveItemsToEntity(array $itemIds, int $entityId, PushService $pushService = null): void
	{
		try
		{
			foreach ($itemIds as $itemId)
			{
				$item = ItemTable::createItemObject();
				$item->setId($itemId);
				$item->setEntityId($entityId);

				$this->changeItem($item, $pushService);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_MOVE_ITEM));
		}
	}

	public function updateEntityIdToItems(int $entityId, array $itemIds): void
	{
		try
		{
			if ($itemIds)
			{
				ItemTable::updateMulti($itemIds, ['ENTITY_ID' => $entityId]);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_UPDATE_ITEMS_ENTITY)
			);
		}
	}

	public function changeItem(ItemTable $item, PushService $pushService = null): bool
	{
		try
		{
			$result = ItemTable::update($item->getId(), $item->getFieldsToUpdateItem());

			if ($result->isSuccess())
			{
				if ($pushService)
				{
					$pushService->sendUpdateItemEvent($item);
				}

				return true;
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_UPDATE_ITEM);
				return false;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_UPDATE_ITEM));
			return false;
		}
	}

	public function removeItem(
		ItemTable $item,
		PushService $pushService = null,
		TaskService $taskService = null
	): bool
	{
		try
		{
			if ($taskService)
			{
				$taskService->removeTask($item->getSourceId());
				if ($taskService->getErrors())
				{
					$this->errorCollection->add($taskService->getErrors());
					return false;
				}

				ItemTable::deactivateBySourceId($item->getSourceId());

				if ($pushService)
				{
					$pushService->sendRemoveItemEvent($item);
				}

				return true;
			}
			else
			{
				$result = ItemTable::delete($item->getId());
				if ($result->isSuccess())
				{
					if ($pushService)
					{
						$pushService->sendRemoveItemEvent($item);
					}

					return true;
				}
				else
				{
					$this->setErrors($result, self::ERROR_COULD_NOT_REMOVE_ITEM);
					return false;
				}
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_REMOVE_ITEM));
			return false;
		}
	}

	public function sortItems(array $sortInfo, PushService $pushService = null): void
	{
		try
		{
			$itemIds = [];
			$sortWhens = [];

			$updatedItems = [];

			foreach($sortInfo as $itemId => $info)
			{
				$itemId = (is_numeric($itemId) ? (int)$itemId : 0);
				$sort = (is_numeric($info['sort']) ? (int)$info['sort'] : 0);
				$entityId = (is_numeric($info['entityId']) ? (int)$info['entityId'] : 0);
				if ($itemId)
				{
					$itemIds[] = $itemId;
					$sortWhens[] = 'WHEN ID = '.$itemId.' THEN '.$sort;
					$updatedItemId = (is_numeric($info['updatedItemId']) ? (int)$info['updatedItemId'] : 0);
					if ($updatedItemId)
					{
						$tmpId = (is_string($info['tmpId']) ? $info['tmpId'] : '');
						$updatedItems[$itemId] = [
							'sort' => $sort,
							'tmpId' => $tmpId,
						];
						if ($entityId)
						{
							$updatedItems[$itemId]['entityId'] = $entityId;
						}
					}
				}
			}

			if ($itemIds)
			{
				$data = [
					'SORT' => new SqlExpression('(CASE '.implode(' ', $sortWhens).' END)')
				];
				ItemTable::updateMulti($itemIds, $data);
			}

			if ($updatedItems && $pushService)
			{
				$pushService->sendSortItemEvent($updatedItems);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_CHANGE_SORT));
		}
	}

	/**
	 * The method returns task ids from active items.
	 *
	 * @param int $entityId Entity id.
	 * @return array
	 */
	public function getTaskIdsByEntityId(int $entityId): array
	{
		$items = $this->getItemsFromDb(
			['SOURCE_ID'],
			[
				'ENTITY_ID'=> (int) $entityId,
				'ITEM_TYPE'=> ItemTable::TASK_TYPE,
				'ACTIVE' => 'Y'
			]
		);

		return array_map(function ($item)
		{
			return $item['SOURCE_ID'];
		}, $items);
	}

	/**
	 * The method returns active items by entity id.
	 *
	 * @param int $entityId Entity id.
	 * @return ItemTable[]
	 */
	public function getTaskItemsByEntityId(int $entityId): array
	{
		$items = $this->getItemsFromDb(
			['*'],
			[
				'ENTITY_ID'=> (int)$entityId,
				'ITEM_TYPE'=> ItemTable::TASK_TYPE,
				'ACTIVE' => 'Y'
			]
		);

		$itemObjects = [];
		foreach ($items as $item)
		{
			$itemObjects[] = ItemTable::createItemObject($item);
		}

		return $itemObjects;
	}

	/**
	 * @param array $sourceIds
	 * @return ItemInfoColumn[]
	 */
	public function getItemsInfoBySourceIds(array $sourceIds): array
	{
		$itemsInfo = [];

		try
		{
			$queryObject = ItemTable::getList([
				'select' => ['ID', 'INFO'],
				'filter' => [
					'SOURCE_ID' => $sourceIds
				],
				'order' => ['SORT' => 'ASC', 'ID' => 'DESC'],
			]);
			while ($itemData = $queryObject->fetch())
			{
				$itemsInfo[$itemData['ID']] = $itemData['INFO'];
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ITEM_INFO)
			);
		}

		return $itemsInfo;
	}

	public function getTaskIdByItemId(int $itemId): int
	{
		$queryObject = ItemTable::getList([
			'select' => ['SOURCE_ID'],
			'filter' => [
				'ID'=> $itemId,
				'ITEM_TYPE'=> ItemTable::TASK_TYPE,
				'ACTIVE' => 'Y',
			]
		]);
		if ($itemData = $queryObject->fetch())
		{
			return (int) $itemData['SOURCE_ID'];
		}
		return 0;
	}

	/**
	 * Returns a hierarchy of children by a parent entity id.
	 *
	 * @param EntityTable $entity Entity object.
	 * @param PageNavigation|null $nav If you need to navigation.
	 * @param array $filteredSourceIds If you need to get filtered items.
	 * @return array ItemTable[]
	 */
	public function getHierarchyChildItems(
		EntityTable $entity,
		PageNavigation $nav = null,
		array $filteredSourceIds = []
	): array
	{
		$queryParams = [
			'select' => ['*'],
			'filter' => [
				'ENTITY_ID'=> $entity->getId(),
				'ACTIVE' => 'Y',
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'DESC',
			],
		];

		if (!empty($filteredSourceIds))
		{
			$queryParams['filter']['SOURCE_ID'] = $filteredSourceIds;
		}

		if ($nav)
		{
			$queryParams['offset'] = $nav->getOffset();
			$queryParams['limit'] = $nav->getLimit() + 1;
		}

		$queryObject = ItemTable::getList($queryParams);

		$tree = [];

		$n = 0;
		while ($item = $queryObject->fetch())
		{
			$n++;

			if ($nav && $n > $nav->getPageSize())
			{
				break;
			}

			$itemObject = ItemTable::createItemObject($item);

			$tree[] = $itemObject;
		}

		if ($nav)
		{
			$nav->setRecordCount($nav->getOffset() + $n);
		}

		return $tree;
	}

	public function getSumStoryPointsBySourceIds(array $sourceIds): float
	{
		$sumStoryPoints = 0;

		try
		{
			$queryObject = ItemTable::getList([
				'select' => ['STORY_POINTS'],
				'filter' => ['SOURCE_ID' => $sourceIds]
			]);
			while ($itemData = $queryObject->fetch())
			{
				$sumStoryPoints += (float) $itemData['STORY_POINTS'];
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ITEM));
		}

		return $sumStoryPoints;
	}

	/**
	 * The method returns an array of data in the required format for the client app.
	 *
	 * @param ItemTable $item Data object.
	 * @return array
	 */
	public function getItemData(ItemTable $item): array
	{
		return [
			'itemId' => $item->getId(),
			'tmpId' => $item->getTmpId(),
			'itemType' => $item->getItemType(),
			'entityId' => $item->getEntityId(),
			'sort' => $item->getSort(),
			'parentId' => $item->getParentId(),
			'storyPoints' => $item->getStoryPoints(),
			'sourceId' => $item->getSourceId(),
			'epic' => $this->getEpicInfo($item->getParentId()),
			'info' => $item->getInfo()->getInfoData(),
		];
	}

	/**
	 * The method returns an array of data in the required format for the client app.
	 *
	 * @param ItemTable[] $items Items.
	 * @return array
	 */
	public function getItemsData(array $items): array
	{
		$itemsData = [];

		foreach ($items as $item)
		{
			$itemsData[$item->getSourceId()] = $this->getItemData($item);
		}

		return $itemsData;
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	private function getItemsFromDb(array $select = [], array $filter = [], array $order = []): array
	{
		$queryObject = ItemTable::getList([
			'select' => $select,
			'filter' => $filter,
			'order' => $order
		]);
		return $queryObject->fetchAll();
	}

	private function setErrors(Result $result, string $code): void
	{
		$this->errorCollection->setError(new Error(implode('; ', $result->getErrorMessages()), $code));
	}
}