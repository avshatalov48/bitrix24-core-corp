<?php
namespace Bitrix\Tasks\Scrum\Service;

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
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Internal\EO_Item_Collection;

class EntityService implements Errorable
{
	const ERROR_COULD_NOT_READ_ENTITY = 'TASKS_ES_01';
	const ERROR_COULD_NOT_READ_ENTITY_IDS = 'TASKS_ES_02';
	const ERROR_COULD_NOT_REMOVE_ENTITY = 'TASKS_ES_03';
	const ERROR_COULD_NOT_GET_LIST_READ_ENTITY = 'TASKS_ES_04';
	const ERROR_COULD_NOT_GET_STORY_POINTS = 'TASKS_ES_05';

	private $errorCollection;

	private static $entitiesById = [];

	private $userId;

	public function __construct(int $userId = 0)
	{
		$this->userId = $userId;
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * @param int $entityId
	 * @return EO_Item_Collection
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getItems(int $entityId): EO_Item_Collection
	{
		$entity = EntityTable::getList([
			'select' => ['ITEMS'],
			'filter' => [
				'ID' => (int) $entityId,
				'ITEMS.ACTIVE' => 'Y',
			],
		])->fetchCollection();

		return $entity->getItemsCollection();
	}

	/**
	 * Returns the ids of the backlog and all sprints of the group.
	 *
	 * @param int $groupId Group id.
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getEntityIds(int $groupId): array
	{
		$entityIds = [];

		$queryObject = EntityTable::getList([
			'select' => ['ID'],
			'filter' => ['GROUP_ID' => $groupId],
			'order' => ['ID' => 'DESC'],
		]);
		while ($entityData = $queryObject->fetch())
		{
			$entityIds[] = (int) $entityData['ID'];
		}

		return $entityIds;
	}

	/**
	 * @param array $select
	 * @param array $filter
	 * @param array $order
	 * @return Query\Result|null
	 */
	public function getList(
		PageNavigation $nav = null,
		$filter = [],
		$select = [],
		$order = []
	): ?Query\Result
	{
		try
		{
			if ($this->userId && !Loader::includeModule('socialnetwork'))
			{
				$this->errorCollection->setError(
					new Error(
						'Unable to load socialnetwork.',
						self::ERROR_COULD_NOT_GET_LIST_READ_ENTITY
					)
				);

				return null;
			}

			$query = new Query\Query(EntityTable::getEntity());

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

			if ($this->userId)
			{
				$query->registerRuntimeField(
					'UG',
					new ReferenceField(
						'UG',
						UserToGroupTable::getEntity(),
						Join::on('this.GROUP_ID', 'ref.GROUP_ID')->where('ref.USER_ID', $this->userId),
						['join_type' => 'inner']
					)
				);
			}

			$queryResult = $query->exec();

			return $queryResult;
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_GET_LIST_READ_ENTITY
				)
			);

			return null;
		}
	}

	/**
	 * Returns an object with entity data by entity id.
	 *
	 * @param int $entityId Entity id.
	 * @return EntityForm
	 */
	public function getEntityById(int $entityId): EntityForm
	{
		if (array_key_exists($entityId, self::$entitiesById))
		{
			return self::$entitiesById[$entityId];
		}

		self::$entitiesById[$entityId] = new EntityForm();

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'ID' => $entityId,
				],
			]);
			if ($entityData = $queryObject->fetch())
			{
				$entity = new EntityForm();

				$entity->fillFromDatabase($entityData);

				self::$entitiesById[$entityId] = $entity;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_READ_ENTITY
				)
			);
		}

		return self::$entitiesById[$entityId];
	}

	/**
	 * Removes entity by id.
	 *
	 * @param int $id
	 * @return bool
	 */
	public function removeEntity(int $id): bool
	{
		try
		{
			$result = EntityTable::delete($id);

			if ($result->isSuccess())
			{
				return true;
			}
			else
			{
				$this->setErrors($result, self::ERROR_COULD_NOT_REMOVE_ENTITY);

				return false;
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_REMOVE_ENTITY
				)
			);

			return false;
		}
	}

	public function getCounters(
		int $groupId,
		int $entityId,
		TaskService $taskService,
		$skipCompletedTasks = true
	): array
	{
		$taskIds = [];
		$storyPoints = '';
		$countTotal = 0;

		$storyPointsMap = [];

		try
		{
			$queryObject = EntityTable::getList([
				'select' => [
					'SOURCE_ID' => 'ITEMS.SOURCE_ID',
					'STORY_POINTS' => 'ITEMS.STORY_POINTS',
				],
				'filter' => [
					'ID' => $entityId,
					'GROUP_ID' => $groupId,
					'=ITEMS.ACTIVE' => 'Y',
				],
			]);

			while ($data = $queryObject->fetch())
			{
				$storyPointsMap[$data['SOURCE_ID']] = $data['STORY_POINTS'];
			}

			$tasksInfo = [];

			if ($storyPointsMap)
			{
				$tasksInfo = $taskService->getTasksInfo(array_keys($storyPointsMap));
			}

			$parentIdsToCheck = [];
			foreach ($tasksInfo as $taskId => $taskInfo)
			{
				$parentId = (int) $taskInfo['PARENT_ID'];

				if ($parentId)
				{
					$parentIdsToCheck[$taskId] = $parentId;
				}
				else
				{
					$taskIds[] = $taskId;
				}
			}

			$actualParentIds = [];

			if ($parentIdsToCheck)
			{
				$actualParentIds = $taskService->getActualParentIds($parentIdsToCheck, $groupId);
			}

			foreach ($actualParentIds as $taskId => $parentId)
			{
				if (!$parentId)
				{
					$taskIds[] = $taskId;
				}
			}

			if ($skipCompletedTasks)
			{
				$taskIds = $taskService->getUncompletedTaskIds($taskIds);
			}

			foreach ($taskIds as $taskId)
			{
				$countTotal++;

				if (is_numeric($storyPointsMap[$taskId]))
				{
					$storyPoints = (float) $storyPoints + (float) $storyPointsMap[$taskId];
				}
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error(
					$exception->getMessage(),
					self::ERROR_COULD_NOT_GET_STORY_POINTS
				)
			);
		}

		return [
			'taskIds' => $taskIds,
			'storyPoints' => $storyPoints,
			'countTotal' => $countTotal,
		];
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	private function setErrors(Result $result, string $code): void
	{
		$this->errorCollection->setError(
			new Error(
				implode('; ', $result->getErrorMessages()),
				$code
			)
		);
	}
}
