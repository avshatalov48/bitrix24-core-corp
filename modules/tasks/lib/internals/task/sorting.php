<?php

namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\DataBase\Helper;
use Bitrix\Tasks\Internals\Log\LogFacade;
use Bitrix\Tasks\Internals\TaskDataManager;
use CDBResult;
use CTaskFilterCtrl;
use CTaskFilterCtrlInterface;
use CTasks;
use Exception;
use TasksException;

Loc::loadMessages(__FILE__);

/**
 * Class SortingTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Sorting_Query query()
 * @method static EO_Sorting_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Sorting_Result getById($id)
 * @method static EO_Sorting_Result getList(array $parameters = [])
 * @method static EO_Sorting_Entity getEntity()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Sorting createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Sorting_Collection createCollection()
 * @method static \Bitrix\Tasks\Internals\Task\EO_Sorting wakeUpObject($row)
 * @method static \Bitrix\Tasks\Internals\Task\EO_Sorting_Collection wakeUpCollection($rows)
 */
class SortingTable extends TaskDataManager
{
	const MAX_LENGTH_BATCH_MYSQL_QUERY = 2048;
	const SORT_INDEX_INCREMENT = 1024;
	const MIN_SORT_DELTA = 0.00005;
	const MAX_PAGE_TOP = 50;

	/**
	 * @inheritdoc
	 */
	public static function getTableName(): string
	{
		return 'b_tasks_custom_sort';
	}

	public static function getClass(): string
	{
		return get_called_class();
	}

	/**
	 * @inheritdoc
	 */
	public static function getMap(): array
	{
		return [
			"ID" => [
				"data_type" => "integer",
				"primary" => true,
				"autocomplete" => true,
			],

			"TASK_ID" => [
				"data_type" => "integer",
				"required" => true,
				"title" => Loc::getMessage("TASKS_TASK_SORTING_ENTITY_TASK_ID_FIELD"),
			],

			"SORT" => [
				"data_type" => "float",
				"required" => true,
				"title" => Loc::getMessage("TASKS_TASK_SORTING_ENTITY_INDEX_FIELD"),
			],

			"USER_ID" => [
				"data_type" => "integer",
				"default_value" => 0,
				"title" => Loc::getMessage("TASKS_TASK_SORTING_ENTITY_USER_ID_FIELD"),
			],

			"GROUP_ID" => [
				"data_type" => "integer",
				"default_value" => 0,
				"title" => Loc::getMessage("TASKS_TASK_SORTING_ENTITY_GROUP_ID_FIELD"),
			],

			"PREV_TASK_ID" => [
				"data_type" => "integer",
				"default_value" => 0,
				"title" => Loc::getMessage("TASKS_TASK_SORTING_ENTITY_PREV_TASK_ID_FIELD"),
			],

			"NEXT_TASK_ID" => [
				"data_type" => "integer",
				"default_value" => 0,
				"title" => Loc::getMessage("TASKS_TASK_SORTING_ENTITY_NEXT_TASK_ID_FIELD"),
			],
		];
	}

	/**
	 * Adds rows to the table.
	 *
	 * @param array $items Items.
	 * @return void
	 */
	public static function insertBatch(array $items, bool $ignore = false): void
	{
		$tableName = static::getTableName();
		Helper::insertBatch($tableName, $items, $ignore);
	}

	public static function insertIgnore(array $fields): void
	{
		$taskId = (int)$fields['TASK_ID'];
		$sort = (double)$fields['SORT'];
		$userId = (int)($fields['USER_ID'] ?? null);
		$groupId = (int)($fields['GROUP_ID'] ?? null);
		$prevTaskId = (int)$fields['PREV_TASK_ID'];
		$nextTaskId = (int)$fields['NEXT_TASK_ID'];

		$connection = Application::getConnection();
		$query = $connection->getSqlHelper()->getInsertIgnore(
			static::getTableName(),
			'(TASK_ID, SORT, USER_ID, GROUP_ID, PREV_TASK_ID, NEXT_TASK_ID)',
			"VALUES ({$taskId}, {$sort}, {$userId}, {$groupId}, {$prevTaskId}, {$nextTaskId})"
		);

		$connection->query($query);
	}

	/**
	 * Deletes the task from the sorting tree
	 *
	 * @param $taskId
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function deleteByTaskId($taskId)
	{
		$taskId = intval($taskId);
		if (!$taskId)
		{
			return;
		}

		$rows = self::getList([
			"filter" => [
				"TASK_ID" => $taskId,
			],
		]);

		while ($row = $rows->fetch())
		{
			static::fixSiblings($row);
		}

		HttpApplication::getConnection()->query("delete from "
			. static::getTableName()
			. " where TASK_ID = "
			. $taskId);
	}

	/**
	 * The same as deleteByTaskId but without deletion itself
	 *
	 * @param $taskId
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function fixSiblingsEx($taskId)
	{
		$taskId = intval($taskId);
		if (!$taskId)
		{
			return;
		}

		$rows = self::getList([
			"filter" => [
				"TASK_ID" => $taskId,
			],
		]);

		while ($row = $rows->fetch())
		{
			static::fixSiblings($row);
		}
	}

	/**
	 * Sets sorting for the task.
	 *
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws TasksException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public static function setSorting($userId, $groupId, $sourceId, $targetId, $before)
	{
		$userId = intval($userId);
		$groupId = intval($groupId);
		$sourceId = intval($sourceId);
		$targetId = intval($targetId);

		$targetTask = self::getTask($targetId, $userId, $groupId);
		if (!$targetTask)
		{
			static::setTargetSorting($userId, $groupId, $sourceId, $targetId);
			$targetTask = self::getTask($targetId, $userId, $groupId);
		}

		static::moveTaskToTarget($userId, $groupId, $sourceId, $targetTask, $before);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws TasksException
	 * @throws Exception
	 */
	private static function setTargetSorting(int $userId, int $groupId, int $sourceId, int $targetId)
	{
		$filterCtrl = CTaskFilterCtrl::getInstance($userId, $groupId > 0);
		$filter = $filterCtrl->getFilterPresetConditionById(CTaskFilterCtrlInterface::STD_PRESET_ALL_MY_TASKS);
		$filter["CHECK_PERMISSIONS"] = "Y";
		$filter["SORTING"] = "N";

		$params = [
			"USER_ID" => $userId,
			"bIgnoreErrors" => true,
			"nPageTop" => static::MAX_PAGE_TOP,
		];

		if ($groupId > 0)
		{
			$filter["GROUP_ID"] = $groupId;
			$params["SORTING_GROUP_ID"] = $groupId;
		}

		//try to avoid a recursion
		$result = CTasks::getList(
			[],
			array_merge($filter, ["ID" => $targetId]),
			["ID", "SORTING"],
			$params
		);

		if (!$result->fetch())
		{
			return;
		}

		$order = [
			"SORTING" => "ASC",
			"STATUS_COMPLETE" => "ASC",
			"DEADLINE" => "ASC,NULLS",
			"ID" => "ASC",
		];

		$result = CTasks::getList($order, $filter, ["ID", "SORTING"], $params);
		$lastSortedItem = static::getMaxSort($userId, $groupId);
		$prevTaskSort = $lastSortedItem ? intval($lastSortedItem["SORT"]) : 0;
		$prevTaskId = $lastSortedItem && $lastSortedItem["TASK_ID"] ? $lastSortedItem["TASK_ID"] : 0;

		[$items, $targetFound] = static::getSortedItems($result, $userId, $groupId, $prevTaskSort, $prevTaskId,
			$sourceId, $targetId);
		static::insertBatch($items, true);

		if (count($items) > 0)
		{
			if ($lastSortedItem)
			{
				try
				{
					static::update($lastSortedItem["ID"], ["NEXT_TASK_ID" => $items[0]["TASK_ID"]]);
				}
				catch (Exception $exception)
				{
					LogFacade::logThrowable($exception);
				}
			}

			if (!$targetFound)
			{
				static::setTargetSorting($userId, $groupId, $sourceId, $targetId);
			}
		}
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private static function getTask($taskId, $userId, $groupId): ?array
	{
		if ($groupId)
		{
			return static::getRow([
				"filter" => [
					"=TASK_ID" => $taskId,
					"=GROUP_ID" => $groupId,
				],
			]);
		}
		elseif ($userId)
		{
			return static::getRow([
				"filter" => [
					"=TASK_ID" => $taskId,
					"=USER_ID" => $userId,
				],
			]);
		}

		return null;
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	private static function moveTaskToTarget($userId, $groupId, $sourceId, $targetTask, $before): bool
	{
		if (!$targetTask)
		{
			return false;
		}

		if (
			($before && $targetTask["PREV_TASK_ID"] == $sourceId)
			|| (!$before && $targetTask["NEXT_TASK_ID"] == $sourceId)
		)
		{
			return true;
		}

		$connection = Application::getConnection();
		$prevTask = null;
		$prevTaskSort = 0;

		$nextTask = null;
		$nextTaskSort = 0;
		if ($before)
		{
			$prevTaskId = intval($targetTask["PREV_TASK_ID"]);
			if ($prevTaskId)
			{
				$prevTask = static::getTask($prevTaskId, $userId, $groupId);
				if (!$prevTask || $prevTask["SORT"] > $targetTask["SORT"])
				{
					//try to correct wrong prev_task_id
					$filter = $groupId ? ["=GROUP_ID" => $groupId] : ["=USER_ID" => $userId];
					$filter["<SORT"] = $targetTask["SORT"];
					$prevTask = static::getRow([
						"filter" => $filter,
						"order" => ["SORT" => "DESC"],
					]);
				}

				$prevTaskId = $prevTask ? $prevTask["TASK_ID"] : 0;
				$prevTaskSort = $prevTask ? $prevTask["SORT"] : 0;
			}

			$nextTask = $targetTask;
			$nextTaskId = $targetTask["TASK_ID"];
			$nextTaskSort = $targetTask["SORT"];
		}
		else
		{
			$nextTaskId = intval($targetTask["NEXT_TASK_ID"]);
			if ($nextTaskId)
			{
				$nextTask = static::getTask($nextTaskId, $userId, $groupId);
				if (!$nextTask || $nextTask["SORT"] < $targetTask["SORT"])
				{
					//try to correct wrong next_task_id
					$filter = $groupId ? ["=GROUP_ID" => $groupId] : ["=USER_ID" => $userId];
					$filter[">SORT"] = $targetTask["SORT"];
					$nextTask = static::getRow([
						"filter" => $filter,
						"order" => ["SORT" => "ASC"],
					]);
				}

				$nextTaskId = $nextTask ? $nextTask["TASK_ID"] : 0;
				$nextTaskSort = $nextTask ? $nextTask["SORT"] : 0;
			}

			$prevTask = $targetTask;
			$prevTaskId = $targetTask["TASK_ID"];
			$prevTaskSort = $targetTask["SORT"];
		}

		if ($nextTask !== null && $prevTask !== null && ($nextTaskSort - $prevTaskSort) < static::MIN_SORT_DELTA)
		{
			$filter = $groupId > 0 ? "GROUP_ID = {$groupId}" : "USER_ID = {$userId}";
			$increment = static::SORT_INDEX_INCREMENT;
			$tableName = static::getTableName();
			try
			{
				$connection->query(
					"UPDATE {$tableName} SET SORT = SORT + {$increment} WHERE SORT >= {$nextTaskSort} AND {$filter}"
				);
			}
			catch (SqlQueryException $exception)
			{
				LogFacade::logThrowable($exception);
			}


			$nextTask = static::getTask($nextTaskId, $userId, $groupId);
			$nextTaskSort = $nextTask["SORT"];
		}

		if ($prevTaskId === 0)
		{
			$sourceTaskSort = $nextTaskSort - static::SORT_INDEX_INCREMENT;
		}
		elseif ($nextTaskId === 0)
		{
			$sourceTaskSort = $prevTaskSort + static::SORT_INDEX_INCREMENT;
		}
		else
		{
			$sourceTaskSort = ($nextTaskSort + $prevTaskSort) / 2;
		}

		$sourceTask = static::getTask($sourceId, $userId, $groupId);

		try
		{
			if ($sourceTask)
			{
				static::update($sourceTask["ID"], [
					"PREV_TASK_ID" => $prevTaskId,
					"NEXT_TASK_ID" => $nextTaskId,
					"SORT" => $sourceTaskSort,
				]);

				static::fixSiblings($sourceTask);
			}
			else
			{
				$fields = [
					"TASK_ID" => $sourceId,
					"PREV_TASK_ID" => $prevTaskId,
					"NEXT_TASK_ID" => $nextTaskId,
					"SORT" => $sourceTaskSort,
				];

				if ($groupId)
				{
					$fields["GROUP_ID"] = $groupId;
				}
				else
				{
					$fields["USER_ID"] = $userId;
				}

				static::insertIgnore($fields);
			}

			if ($prevTask)
			{
				static::update($prevTask["ID"], ["NEXT_TASK_ID" => $sourceId]);
			}

			if ($nextTask)
			{
				static::update($nextTask["ID"], ["PREV_TASK_ID" => $sourceId]);
			}
		}
		catch (Exception $exception)
		{
			LogFacade::logThrowable($exception);
		}

		return true;
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	private static function fixSiblings(?array $sourceTask)
	{
		$userId = (int)$sourceTask['USER_ID'];
		$groupId = (int)$sourceTask['GROUP_ID'];

		$oldPrevTaskId = $sourceTask['PREV_TASK_ID'];
		$oldPrevTask =
			$oldPrevTaskId
				? static::getTask($oldPrevTaskId, $userId, $groupId)
				: null;

		$oldNextTaskId = $sourceTask['NEXT_TASK_ID'];
		$oldNextTask =
			$oldNextTaskId
				? static::getTask($oldNextTaskId, $userId, $groupId)
				: null;

		try
		{
			if ($oldPrevTask)
			{
				static::update($oldPrevTask['ID'], ['NEXT_TASK_ID' => $oldNextTaskId]);
			}

			if ($oldNextTask)
			{
				static::update($oldNextTask['ID'], ['PREV_TASK_ID' => $oldPrevTaskId]);
			}
		}
		catch (Exception $exception)
		{
			LogFacade::logThrowable($exception);
		}
	}

	private static function getSortedItems(
		CDBResult $result,
		int $userId,
		int $groupId,
		int $prevTaskSort,
		int $prevTaskId,
		int $sourceId,
		int $targetId
	): array
	{
		$items = [];
		$itemIndex = -1;
		$prevTaskIndex = null;
		$targetFound = false;
		while ($row = $result->fetch())
		{
			if ($sourceId == $row["ID"])
			{
				//Skip source task
				continue;
			}

			if ($prevTaskIndex !== null)
			{
				$items[$prevTaskIndex]["NEXT_TASK_ID"] = $row["ID"];
			}

			$prevTaskSort += static::SORT_INDEX_INCREMENT;
			$fields = [
				"TASK_ID" => $row["ID"],
				"SORT" => $prevTaskSort,
				"PREV_TASK_ID" => $prevTaskId,
				"NEXT_TASK_ID" => 0,
			];

			if ($groupId)
			{
				$fields["GROUP_ID"] = $groupId;
			}
			else
			{
				$fields["USER_ID"] = $userId;
			}

			$items[++$itemIndex] = $fields;

			$prevTaskIndex = $itemIndex;
			$prevTaskId = $row["ID"];

			if ($targetId == $row["ID"])
			{
				$targetFound = true;
				break;
			}
		}

		return [$items, $targetFound];
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private static function getMaxSort($userId, $groupId): ?array
	{
		return static::getRow([
			'filter' => $groupId ? ['=GROUP_ID' => $groupId] : ['=USER_ID' => $userId],
			'order' => ['SORT' => 'DESC'],
		]);
	}
}