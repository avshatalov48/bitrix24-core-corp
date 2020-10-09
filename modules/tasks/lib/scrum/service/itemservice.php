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

	private $errorCollection;

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	public function createItem(ItemTable $epic): ItemTable
	{
		try
		{
			$result = ItemTable::add($epic->getFieldsToCreateEpicItem());

			if ($result->isSuccess())
			{
				$epic->setId($result->getId());
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
		$epicInfo = [];

		$item = $this->getItemById($itemId);

		if ($item->getId())
		{
			$epicInfo = [
				'id' => $item->getId(),
				'name' => $item->getName(),
				'description' => $item->getDescription(),
				'info' => $item->getInfo(),
			];
		}

		return $epicInfo;
	}

	public function getAllEpicTags(int $entityId): array
	{
		try
		{
			$tags = [];

			$queryObject = ItemTable::getList([
				'select' => [
					'ID', 'NAME'
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
				$tags[$itemObject->getName()] = [
					'id' => $itemObject->getId(),
					'name' => $itemObject->getName()
				];
			}

			return $tags;
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
		$taskIds = [];

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
			$taskIds[] = $itemData['SOURCE_ID'];
		}

		return $taskIds;
	}

	public function getItemById(int $itemId): ItemTable
	{
		$itemObject = ItemTable::createItemObject();

		$queryObject = ItemTable::getList([
			'filter' => ['ID' => $itemId],
			'order' => ['ID']
		]);
		if ($itemData = $queryObject->fetch())
		{
			$itemObject->setId($itemData['ID']);
			$itemObject->setEntityId($itemData['ENTITY_ID']);
			if ($itemData['NAME'])
			{
				$itemObject->setName($itemData['NAME']);
			}
			if ($itemData['DESCRIPTION'])
			{
				$itemObject->setDescription($itemData['DESCRIPTION']);
			}
			$itemObject->setItemType($itemData['ITEM_TYPE']);
			if ($itemData['PARENT_ID'])
			{
				$itemObject->setParentId($itemData['PARENT_ID']);
			}
			if ($itemData['SORT'])
			{
				$itemObject->setSort($itemData['SORT']);
			}
			$itemObject->setCreatedBy($itemData['CREATED_BY']);
			$itemObject->setModifiedBy($itemData['MODIFIED_BY']);
			if ($itemData['STORY_POINTS'])
			{
				$itemObject->setStoryPoints($itemData['STORY_POINTS']);
			}
			if ($itemData['SOURCE_ID'])
			{
				$itemObject->setSourceId($itemData['SOURCE_ID']);
			}
			if ($itemData['INFO'])
			{
				$itemObject->setInfo($itemData['INFO']);
			}
		}

		return $itemObject;
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
				$fields[$filesFieldName]['VALUE'] = array_unique($fields[$filesFieldName]['VALUE']);
			}
			return $fields;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_GET_UF_ITEM));
		}
		return [];
	}

	public function getItemStoryPointsBySourceId(int $sourceId): string
	{
		try
		{
			$queryObject = ItemTable::getList([
				'select' => ['STORY_POINTS'],
				'filter' => ['SOURCE_ID' => $sourceId]
			]);
			if ($itemData = $queryObject->fetch())
			{
				return ($itemData['STORY_POINTS'] ? $itemData['STORY_POINTS'] : '');
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ITEM));
		}

		return '';
	}

	public function getItemIdsBySourceIds(int $entityId, array $sourceIds): array
	{
		$itemIds = [];

		try
		{
			$queryObject = ItemTable::getList([
				'select' => ['ID'],
				'filter' => [
					'ENTITY_ID' => $entityId,
					'SOURCE_ID' => $sourceIds
				],
				'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
			]);
			while ($itemData = $queryObject->fetch())
			{
				$itemIds[] = $itemData['ID'];
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ITEM));
		}

		return $itemIds;
	}

	public function moveItemsToEntity(array $itemIds, int $entityId): void
	{
		try
		{
			foreach ($itemIds as $itemId)
			{
				$item = ItemTable::createItemObject();
				$item->setId($itemId);
				$item->setEntityId($entityId);
				$item->setSort(0);
				$this->changeItem($item);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_MOVE_ITEM));
		}
	}

	public function changeItem(ItemTable $item): bool
	{
		try
		{
			$result = ItemTable::update($item->getId(), $item->getFieldsToUpdateItem());

			if ($result->isSuccess())
			{
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

	public function removeItem(ItemTable $item, TaskService $taskService = null): bool
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

				return true;
			}
			else
			{
				$result = ItemTable::delete($item->getId());
				if ($result->isSuccess())
				{
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

	public function moveAndSort(array $sortInfo): void
	{
		try
		{
			$itemIds = [];
			$entityIdWhens = [];
			$sortWhens = [];

			foreach($sortInfo as $itemId => $info)
			{
				$itemId = (is_numeric($itemId) ? (int) $itemId : 0);
				if ($itemId)
				{
					$itemIds[] = $itemId;
					$sortWhens[] = 'WHEN ID = '.$itemId.' THEN '.$info['sort'];
					if (!empty($info['entityId']))
					{
						$entityIdWhens[] = 'WHEN ID = '.$itemId.' THEN '.$info['entityId'];
					}
				}
			}

			if ($itemIds)
			{
				$data = [
					'SORT' => new SqlExpression('(CASE '.implode(' ', $sortWhens).' END)')
				];
				if ($entityIdWhens)
				{
					$data['ENTITY_ID'] = new SqlExpression('(CASE '.implode(' ', $entityIdWhens).' END)');
				}
				ItemTable::updateMulti($itemIds, $data);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_CHANGE_SORT));
		}
	}

	public function getTaskItemsByEntityId(int $entityId): array
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

	public function getTaskIdByItemId(int $itemId): int
	{
		$queryObject = ItemTable::getList([
			'select' => ['SOURCE_ID'],
			'filter' => [
				'ID'=> (int) $itemId,
				'ITEM_TYPE'=> ItemTable::TASK_TYPE,
				'ACTIVE' => 'Y'
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
	 * @return array ItemTable[]
	 */
	public function getHierarchyChildItems(EntityTable $entity): array
	{
		$items = $this->getItemsFromDb(
			['*'],
			['ENTITY_ID'=> (int) $entity->getId(), 'ACTIVE' => 'Y'],
			['SORT' => 'ASC', 'ID' => 'ASC']
		);

		$tree = [];
		foreach ($items as $item)
		{
			$itemObject = ItemTable::createItemObject($item);
			if ($item['STORY_POINTS'] <> '')
			{
				//todo types storypoints
				$entity->setStoryPoints((float) $entity->getStoryPoints() + (float) $item['STORY_POINTS']);
			}
			$tree[] = $itemObject;
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

	public function cleanEpicInTaskName(string $name): string
	{
		if (isset($name) && preg_match_all('/\s@([^\s,\[\]<>]+)/is', ' '.$name, $matches))
		{
			$name = trim(str_replace($matches[0], '', $name));
		}
		return $name;
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