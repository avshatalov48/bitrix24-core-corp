<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query;
use Bitrix\Main\Result;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Tasks\Scrum\Form\EntityForm;
use Bitrix\Tasks\Scrum\Form\ItemForm;
use Bitrix\Tasks\Scrum\Form\ItemInfo;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Internal\ItemTable;

class ItemService implements Errorable
{
	const ERROR_COULD_NOT_ADD_ITEM = 'TASKS_IS_01';
	const ERROR_COULD_NOT_UPDATE_ITEM = 'TASKS_IS_02';
	const ERROR_COULD_NOT_READ_ITEM = 'TASKS_IS_03';
	const ERROR_COULD_NOT_REMOVE_ITEM = 'TASKS_IS_04';
	const ERROR_COULD_NOT_MOVE_ITEM = 'TASKS_IS_09';
	const ERROR_COULD_NOT_READ_ITEM_INFO = 'TASKS_IS_11';
	const ERROR_COULD_NOT_UPDATE_ITEMS_ENTITY = 'TASKS_IS_12';
	const ERROR_COULD_NOT_READ_ALL_ITEMS = 'TASKS_IS_13';
	const ERROR_COULD_NOT_READ_ITEM_BY_TYPE_ID = 'TASKS_IS_14';
	const ERROR_COULD_NOT_CLEAN_ITEMS_TYPE_ID = 'TASKS_IS_15';
	const ERROR_COULD_NOT_GET_LIST = 'ITEM_LIST_01';

	private $errorCollection;

	private static $taskIdsByEpicId = [];

	private $userId;

	public function __construct(int $userId = 0)
	{
		$this->userId = $userId;
		$this->errorCollection = new ErrorCollection;
	}

	public function createTaskItem(ItemForm $item, PushService $pushService = null): ItemForm
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
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_ADD_ITEM)
			);
		}

		return $item;
	}

	/**
	 * @param PageNavigation $nav
	 * @param array $filter
	 * @param array $select
	 * @param array $order
	 * @return \Bitrix\Main\ORM\Query\Result|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getList(
		PageNavigation $nav,
		$filter = [],
		$select = [],
		$order = []
	): ?Query\Result
	{
		try
		{
			if (!Loader::includeModule('socialnetwork'))
			{
				$this->errorCollection->setError(
					new Error(
						'Unable to load socialnetwork.',
						self::ERROR_COULD_NOT_GET_LIST
					)
				);

				return null;
			}

			$query = new Query\Query(ItemTable::getEntity());

			if (empty($select))
			{
				$select = ['*'];
			}
			$query->setSelect($select);
			$query->setFilter($filter);
			$query->setOrder($order);

			if ($nav)
			{
				$query->setOffset($nav->getOffset());
				$query->setLimit($nav->getLimit() + 1);
			}

			$query->registerRuntimeField(
				'SE',
				new ReferenceField(
					'SE',
					EntityTable::getEntity(),
					Join::on('this.ENTITY_ID', 'ref.ID'),
					['join_type' => 'inner']
				)
			);

			$query->registerRuntimeField(
				'UG',
				new ReferenceField(
					'UG',
					UserToGroupTable::getEntity(),
					Join::on('this.SE.GROUP_ID', 'ref.GROUP_ID')->where('ref.USER_ID', $this->userId),
					['join_type' => 'inner']
				)
			);

			$queryResult = $query->exec();

			return $queryResult;
		}
		catch (\Exception $e)
		{
			$this->errorCollection->setError(
				new Error(
					$e->getMessage(),
					self::ERROR_COULD_NOT_GET_LIST
				)
			);

			return null;
		}
	}

	public function getTaskIdsByEpicId(int $epicId): array
	{
		if (isset(self::$taskIdsByEpicId[$epicId]))
		{
			return self::$taskIdsByEpicId[$epicId];
		}

		self::$taskIdsByEpicId[$epicId] = [];

		$queryObject = ItemTable::getList([
			'select' => ['SOURCE_ID'],
			'filter' => [
				'EPIC_ID' => $epicId,
				'ACTIVE' => 'Y'
			],
			'order' => ['ID']
		]);
		while ($itemData = $queryObject->fetch())
		{
			self::$taskIdsByEpicId[$epicId][] = $itemData['SOURCE_ID'];
		}

		return self::$taskIdsByEpicId[$epicId];
	}

	public function getItemById(int $itemId): ItemForm
	{
		$item = new ItemForm();

		$queryObject = ItemTable::getList([
			'filter' => ['ID' => $itemId],
			'order' => ['ID']
		]);
		if ($itemData = $queryObject->fetch())
		{
			$item->fillFromDatabase($itemData);
		}

		return $item;
	}

	/**
	 * @param array $itemIds Item ids.
	 * @return ItemForm[]
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
				$item = new ItemForm();

				$item->fillFromDatabase($itemData);

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

	/**
	 * @param array $sourceIds
	 * @return ItemForm[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getItemsBySourceIds(array $sourceIds): array
	{
		$items = [];

		$queryObject = ItemTable::getList([
			'filter' => ['SOURCE_ID' => $sourceIds]
		]);
		while ($itemData = $queryObject->fetch())
		{
			$itemForm = new ItemForm();

			$itemForm->fillFromDatabase($itemData);

			$items[] = $itemForm;
		}

		return $items;
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
				$itemsStoryPoints[$itemData['SOURCE_ID']] = $itemData['STORY_POINTS'] !== null
					? $itemData['STORY_POINTS']
					: ''
				;
			}

			return $itemsStoryPoints;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ITEM)
			);
		}

		return [];
	}

	public function getItemBySourceId(int $sourceId): ItemForm
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
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ITEM)
			);
		}

		return new ItemForm();
	}

	public function getItemIdsBySourceIds(array $sourceIds, array $entityIds = []): array
	{
		$itemIds = [];

		$filter = ['SOURCE_ID' => $sourceIds];
		if ($entityIds)
		{
			$filter['ENTITY_ID'] = $entityIds;
		}

		$queryParams = [
			'select' => ['ID'],
			'filter' => $filter,
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'DESC',
			],
		];

		$queryObject = ItemTable::getList($queryParams);

		while ($itemData = $queryObject->fetch())
		{
			$itemIds[] = $itemData['ID'];
		}

		return $itemIds;
	}

	public function getItemIdsByEntityId(int $entityId): array
	{
		$itemIds = [];

		try
		{
			$queryParams = [
				'select' => ['ID'],
				'filter' => ['ENTITY_ID' => $entityId],
				'order' => [
					'SORT' => 'ASC',
					'ID' => 'DESC',
				],
			];

			$queryObject = ItemTable::getList($queryParams);

			while ($itemData = $queryObject->fetch())
			{
				$itemIds[] = $itemData['ID'];
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ITEM)
			);
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

	/**
	 * Moves up the items in entity.
	 *
	 * @param array $itemIds Items list id.
	 * @param int $entityId Entity id.
	 * @param PushService|null $pushService For push.
	 * @return void
	 */
	public function moveItemsToEntity(array $itemIds, int $entityId, PushService $pushService = null): void
	{
		if (empty($itemIds))
		{
			return;
		}

		try
		{
			$sortInfo = [];

			foreach ($itemIds as $key => $itemId)
			{
				$sortInfo[$itemId] = [
					'sort' => $key + 1,
					'entityId' => $entityId,
					'updatedItemId' => $itemId,
				];
			}

			$limit = count($itemIds);

			$queryObject = ItemTable::getList([
				'select' => ['ID', 'SORT'],
				'filter' => ['ENTITY_ID' => $entityId],
				'order' => ['SORT' => 'ASC', 'ID' => 'DESC'],
				'limit' => $limit,
			]);
			while ($itemData = $queryObject->fetch())
			{
				$sortInfo[$itemData['ID']] = [
					'sort' => $itemData['SORT'] + $limit,
					'entityId' => $entityId,
					'updatedItemId' => $itemData['ID'],
				];
			}

			$this->sortItems($sortInfo, $pushService);
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_MOVE_ITEM
				)
			);
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

	public function changeItem(ItemForm $item, PushService $pushService = null): bool
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
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_UPDATE_ITEM
				)
			);

			return false;
		}
	}

	public function removeItem(ItemForm $item, PushService $pushService = null): bool
	{
		try
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
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_REMOVE_ITEM
				)
			);

			return false;
		}
	}

	public function sortItems(array $sortInfo, PushService $pushService = null): void
	{
		$itemIds = [];
		$sortWhens = [];
		$entityWhens = [];

		$updatedItems = [];

		foreach($sortInfo as $itemId => $info)
		{
			$itemId = (is_numeric($itemId ?? null) ? (int) $itemId : 0);
			$sort = (is_numeric($info['sort'] ?? null) ? (int) $info['sort'] : 0);
			$entityId = (is_numeric($info['entityId'] ?? null) ? (int) $info['entityId'] : 0);
			$updatedItemId = (is_numeric($info['updatedItemId'] ?? null) ? (int) $info['updatedItemId'] : 0);
			$tmpId = (is_string($info['tmpId'] ?? null) ? $info['tmpId'] : '');

			if ($itemId)
			{
				$itemIds[] = $itemId;
				$sortWhens[] = 'WHEN ID = ' . $itemId . ' THEN ' . $sort;

				if ($updatedItemId)
				{
					$updatedItems[$itemId] = [
						'sort' => $sort,
						'tmpId' => $tmpId,
					];
					if ($entityId)
					{
						$entityWhens[] = 'WHEN ID = ' . $itemId . ' THEN ' . $entityId;
						$updatedItems[$itemId]['entityId'] = $entityId;
					}
				}
			}
		}

		if ($itemIds)
		{
			$data = [];
			if ($sortWhens)
			{
				$data['SORT'] = new SqlExpression('(CASE ' . implode(' ', $sortWhens) . ' END)');
			}
			if ($entityWhens && count($entityWhens) === count($sortWhens))
			{
				$data['ENTITY_ID'] = new SqlExpression('(CASE ' . implode(' ', $entityWhens) . ' END)');
			}

			ItemTable::updateMulti($itemIds, $data);
		}

		if ($updatedItems && $pushService)
		{
			$pushService->sendSortItemEvent($updatedItems);
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
	 * @return ItemForm[]
	 */
	public function getTaskItemsByEntityId(int $entityId): array
	{
		$items = $this->getItemsFromDb(
			['*'],
			[
				'ENTITY_ID'=> (int)$entityId,
				'ACTIVE' => 'Y'
			]
		);

		$itemObjects = [];
		foreach ($items as $item)
		{
			$itemForm = new ItemForm();

			$itemForm->fillFromDatabase($item);

			$itemObjects[] = $itemForm;
		}

		return $itemObjects;
	}

	/**
	 * @param array $sourceIds
	 * @return ItemInfo[]
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
				'ACTIVE' => 'Y',
			]
		]);
		if ($itemData = $queryObject->fetch())
		{
			return (int) $itemData['SOURCE_ID'];
		}
		return 0;
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
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ITEM)
			);
		}

		return $sumStoryPoints;
	}

	/**
	 * The method returns an array of data in the required format for the client app.
	 *
	 * @param ItemForm $item Data object.
	 * @return array
	 */
	public function getItemData(ItemForm $item): array
	{
		return [
			'id' => $item->getId(),
			'tmpId' => $item->getTmpId(),
			'entityId' => $item->getEntityId(),
			'sort' => $item->getSort(),
			'storyPoints' => $item->getStoryPoints(),
			'sourceId' => $item->getSourceId(),
			'epicId' => $item->getEpicId(),
			'info' => $item->getInfo()->getInfoData(),
		];
	}

	/**
	 * The method returns an array of data in the required format for the client app.
	 *
	 * @param ItemForm[] $items Items.
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
		$this->errorCollection->setError(
			new Error(implode('; ', $result->getErrorMessages()), $code)
		);
	}
}