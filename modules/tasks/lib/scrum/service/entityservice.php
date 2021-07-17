<?php
namespace Bitrix\Tasks\Scrum\Service;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ORM\Query;
use Bitrix\Tasks\Scrum\Internal\EntityTable;
use Bitrix\Tasks\Scrum\Internal\ItemTable;

class EntityService implements Errorable
{
	const ERROR_COULD_NOT_READ_ENTITY = 'TASKS_ES_01';
	const ERROR_COULD_NOT_READ_ENTITY_IDS = 'TASKS_ES_02';
	const ERROR_COULD_NOT_READ_ITEM_SOURCE_IDS = 'TASKS_ES_03';
	const ERROR_COULD_NOT_GET_LIST_READ_ENTITY = 'TASKS_ES_04';

	private $errorCollection;

	private static $entitiesById = [];

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection;
	}

	/**
	 * @param array $select
	 * @param array $filter
	 * @param array $order
	 * @return Query\Result|null
	 */
	public function getList(array $select = [], array $filter = [], array $order = []): ?Query\Result
	{
		try
		{
			return EntityTable::getList([
				'select' => $select,
				'filter' => $filter,
				'order' => $order
			]);
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
	 * @return EntityTable
	 */
	public function getEntityById(int $entityId): EntityTable
	{
		if (array_key_exists($entityId, self::$entitiesById))
		{
			return self::$entitiesById[$entityId];
		}

		self::$entitiesById[$entityId] = EntityTable::createEntityObject();

		try
		{
			$queryObject = EntityTable::getList([
				'filter' => [
					'ID' => (int)$entityId,
				],
			]);
			if ($entityData = $queryObject->fetch())
			{
				self::$entitiesById[$entityId] = EntityTable::createEntityObject($entityData);
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ENTITY));
		}

		return self::$entitiesById[$entityId];
	}

	/**
	 * Returns all entity ids by group id.
	 *
	 * @param int $groupId
	 * @return array
	 */
	public function getEntityIdsByGroupId(int $groupId): array
	{
		$entityIds = [];

		try
		{
			$queryObject = EntityTable::getList([
				'select' => ['ID'],
				'filter' => [
					'GROUP_ID' => $groupId,
				],
			]);
			while ($entityData = $queryObject->fetch())
			{
				$entityIds[] = $entityData['ID'];
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ENTITY_IDS)
			);
		}

		return $entityIds;
	}

	/**
	 * Returns all task ids by group id.
	 *
	 * @param int $groupId
	 * @return array
	 */
	public function getTaskIdsByGroupId(int $groupId): array
	{
		$taskIds = [];

		try
		{
			$queryObject = EntityTable::getList([
				'select' => ['ID', 'TASK_ID' => 'ITEMS.SOURCE_ID'],
				'filter' => [
					'GROUP_ID' => $groupId,
					'ITEMS.ITEM_TYPE' => ItemTable::TASK_TYPE,
				],
			]);
			while ($entityData = $queryObject->fetch())
			{
				$taskIds[] = $entityData['TASK_ID'];
			}
		}
		catch (\Exception $exception)
		{
			$this->errorCollection->setError(
				new Error($exception->getMessage(), self::ERROR_COULD_NOT_READ_ITEM_SOURCE_IDS)
			);
		}

		return $taskIds;
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}