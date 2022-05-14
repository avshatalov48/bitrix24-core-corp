<?php
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\TaskDataManager;
use CTaskFilterCtrl;

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
	public static function getTableName()
	{
		return "b_tasks_sorting";
	}

	/**
	 * @return static
	 */
	public static function getClass()
	{
		return get_called_class();
	}

	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return array(
			"ID" => array(
				"data_type" => "integer",
				"primary" => true,
				"autocomplete" => true,
			),

			"TASK_ID" => array(
				"data_type" => "integer",
				"required" => true,
				"title" => Loc::getMessage("TASKS_TASK_SORTING_ENTITY_TASK_ID_FIELD"),
			),

			"SORT" => array(
				"data_type" => "float",
				"required" => true,
				"title" => Loc::getMessage("TASKS_TASK_SORTING_ENTITY_INDEX_FIELD"),
			),

			"USER_ID" => array(
				"data_type" => "integer",
				"default_value" => 0,
				"title" => Loc::getMessage("TASKS_TASK_SORTING_ENTITY_USER_ID_FIELD"),
			),

			"GROUP_ID" => array(
				"data_type" => "integer",
				"default_value" => 0,
				"title" => Loc::getMessage("TASKS_TASK_SORTING_ENTITY_GROUP_ID_FIELD"),
			),

			"PREV_TASK_ID" => array(
				"data_type" => "integer",
				"default_value" => 0,
				"title" => Loc::getMessage("TASKS_TASK_SORTING_ENTITY_PREV_TASK_ID_FIELD"),
			),

			"NEXT_TASK_ID" => array(
				"data_type" => "integer",
				"default_value" => 0,
				"title" => Loc::getMessage("TASKS_TASK_SORTING_ENTITY_NEXT_TASK_ID_FIELD"),
			),
		);
	}

	/**
	 * Adds rows to the table.
	 * @param array $items Items.
	 * @return void
	 */
	public static function insertBatch(array $items)
	{
		$tableName = static::getTableName();

		\Bitrix\Tasks\Internals\DataBase\Helper::insertBatch($tableName, $items);
	}

	/**
	 * Deletes the task from the sorting tree
	 *
	 * @param $taskId
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function deleteByTaskId($taskId)
	{
		$taskId = intval($taskId);
		if (!$taskId)
		{
			return;
		}

		$rows = self::getList(array(
			"filter" => array(
				"TASK_ID" => $taskId
			)
		));

		while ($row = $rows->fetch())
		{
			static::fixSiblings($row);
		}

		HttpApplication::getConnection()->query("delete from ".static::getTableName()." where TASK_ID = ".$taskId);
	}

	/**
	 * The same as deleteByTaskId but without deletion itself
	 *
	 * @param $taskId
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
				"TASK_ID" => $taskId
			]
		]);

		while ($row = $rows->fetch())
		{
			static::fixSiblings($row);
		}
	}

	/**
	 * Sets sorting for the task.
	 *
	 * @param integer $userId
	 * @param integer $groupId
	 * @param integer $sourceId
	 * @param integer $targetId
	 * @param boolean $before
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

	private static function setTargetSorting($userId, $groupId, $sourceId, $targetId)
	{
		$filterCtrl = CTaskFilterCtrl::getInstance($userId, $groupId > 0);
		$filter = $filterCtrl->getFilterPresetConditionById(CTaskFilterCtrl::STD_PRESET_ALL_MY_TASKS);
		$filter["CHECK_PERMISSIONS"] = "Y";
		$filter["SORTING"] = "N";

		$params = array(
			"USER_ID" => $userId,
			"bIgnoreErrors" => true,
			"nPageTop" => static::MAX_PAGE_TOP
		);

		if ($groupId > 0)
		{
			$filter["GROUP_ID"] = $groupId;
			$params["SORTING_GROUP_ID"] = $groupId;
		}

		//try to avoid a recursion
		$result = \CTasks::getList(
			array(),
			array_merge($filter, array("ID" => $targetId)),
			array("ID", "SORTING"),
			$params
		);

		if (!$result->fetch())
		{
			return;
		}

		$order = array (
			"SORTING" => "ASC",
			"STATUS_COMPLETE" => "ASC",
			"DEADLINE" => "ASC,NULLS",
			"ID" => "ASC",
		);

		$result = \CTasks::getList($order, $filter, array("ID", "SORTING"), $params);
		$lastSortedItem = static::getMaxSort($userId, $groupId);
		$prevTaskSort = $lastSortedItem ? intval($lastSortedItem["SORT"]) : 0;
		$prevTaskId = $lastSortedItem && $lastSortedItem["TASK_ID"] ? $lastSortedItem["TASK_ID"] : 0;

		[$items, $targetFound] = static::getSortedItems($result, $userId, $groupId, $prevTaskSort, $prevTaskId, $sourceId, $targetId);
		static::insertBatch($items);

		if (count($items) > 0)
		{
			if ($lastSortedItem)
			{
				static::update($lastSortedItem["ID"], array("NEXT_TASK_ID" => $items[0]["TASK_ID"]));
			}

			if (!$targetFound)
			{
				static::setTargetSorting($userId, $groupId, $sourceId, $targetId);
			}
		}
	}

	private static function getTask($taskId, $userId, $groupId)
	{
		if ($groupId)
		{
			return static::getRow(array(
				"filter" => array(
					"=TASK_ID" => $taskId,
					"=GROUP_ID" => $groupId
				)
			));
		}
		elseif ($userId)
		{
			return static::getRow(array(
				"filter" => array(
					"=TASK_ID" => $taskId,
					"=USER_ID" => $userId
				)
			));
		}


		return null;
	}

	private static function moveTaskToTarget($userId, $groupId, $sourceId, $targetTask, $before)
	{
		if (!$targetTask)
		{
			return false;
		}

		if (($before && $targetTask["PREV_TASK_ID"] == $sourceId) ||
			(!$before && $targetTask["NEXT_TASK_ID"] == $sourceId))
		{
			return true;
		}

		$prevTask = null;
		$prevTaskId = 0;
		$prevTaskSort = 0;

		$nextTask = null;
		$nextTaskId = 0;
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
					$filter = $groupId ? array("=GROUP_ID" => $groupId) : array("=USER_ID" => $userId);
					$filter["<SORT"] = $targetTask["SORT"];
					$prevTask = static::getRow(array(
						"filter" => $filter,
						"order" => array("SORT" => "DESC")
					));
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
					$filter = $groupId ? array("=GROUP_ID" => $groupId) : array("=USER_ID" => $userId);
					$filter[">SORT"] = $targetTask["SORT"];
					$nextTask = static::getRow(array(
						"filter" => $filter,
						"order" => array("SORT" => "ASC")
					));
				}

				$nextTaskId = $nextTask ? $nextTask["TASK_ID"] : 0;
				$nextTaskSort = $nextTask ? $nextTask["SORT"] : 0;
			}

			$prevTask = $targetTask;
			$prevTaskId = $targetTask["TASK_ID"];
			$prevTaskSort = $targetTask["SORT"];
		}

		if ($nextTask !== null  && $prevTask !== null && ($nextTaskSort - $prevTaskSort) < static::MIN_SORT_DELTA)
		{
			$connection = Application::getConnection();
			$filter = $groupId > 0 ? "GROUP_ID = {$groupId}" : "USER_ID = {$userId}";
			$increment = static::SORT_INDEX_INCREMENT;
			$connection->queryExecute(
				"UPDATE b_tasks_sorting SET SORT = SORT + {$increment} WHERE SORT >= {$nextTaskSort} AND {$filter}"
			);

			$nextTask = static::getTask($nextTaskId, $userId, $groupId);
			$nextTaskSort = $nextTask["SORT"];
		}

		$sourceTaskSort = 0;
		if ($prevTaskId === 0)
		{
			$sourceTaskSort = $nextTaskSort - static::SORT_INDEX_INCREMENT;
		}
		else if ($nextTaskId === 0)
		{
			$sourceTaskSort = $prevTaskSort + static::SORT_INDEX_INCREMENT;
		}
		else
		{
			$sourceTaskSort = ($nextTaskSort + $prevTaskSort) / 2;
		}

//		$sourceTaskSort =
//			$nextTaskSort > $prevTaskSort
//				? ($nextTaskSort + $prevTaskSort) / 2
//				: $prevTaskSort + static::SORT_INDEX_INCREMENT
//		;

		$sourceTask = static::getTask($sourceId, $userId, $groupId);
		if ($sourceTask)
		{
			$result = static::update($sourceTask["ID"], array(
				"PREV_TASK_ID" => $prevTaskId,
				"NEXT_TASK_ID" => $nextTaskId,
				"SORT" => $sourceTaskSort
			));

			static::fixSiblings($sourceTask);
		}
		else
		{
			$fields = array(
				"TASK_ID" => $sourceId,
				"PREV_TASK_ID" => $prevTaskId,
				"NEXT_TASK_ID" => $nextTaskId,
				"SORT" => $sourceTaskSort
			);

			if ($groupId)
			{
				$fields["GROUP_ID"] = $groupId;
			}
			else
			{
				$fields["USER_ID"] = $userId;
			}

			$result = static::add($fields);
		}

		if ($prevTask)
		{
			static::update($prevTask["ID"], array("NEXT_TASK_ID" => $sourceId));
		}

		if ($nextTask)
		{
			static::update($nextTask["ID"], array("PREV_TASK_ID" => $sourceId));
		}

		return true;
	}

	private static function fixSiblings($sourceTask)
	{
		$oldPrevTaskId = $sourceTask["PREV_TASK_ID"];
		$oldPrevTask = $oldPrevTaskId ? static::getTask($oldPrevTaskId, $sourceTask["USER_ID"], $sourceTask["GROUP_ID"]) : null;

		$oldNextTaskId = $sourceTask["NEXT_TASK_ID"];
		$oldNextTask = $oldNextTaskId ? static::getTask($oldNextTaskId, $sourceTask["USER_ID"], $sourceTask["GROUP_ID"]) : null;

		if ($oldPrevTask)
		{
			static::update($oldPrevTask["ID"], array("NEXT_TASK_ID" => $oldNextTaskId));
		}

		if ($oldNextTask)
		{
			static::update($oldNextTask["ID"], array("PREV_TASK_ID" => $oldPrevTaskId));
		}
	}

	private static function getSortedItems(\CDBResult $result, $userId, $groupId, $prevTaskSort, $prevTaskId, $sourceId, $targetId)
	{
		$items = array();
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
			$fields = array(
				"TASK_ID" => $row["ID"],
				"SORT" => $prevTaskSort,
				"PREV_TASK_ID" => $prevTaskId,
				"NEXT_TASK_ID" => 0
			);

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

		return array($items, $targetFound);
	}

	private static function getMaxSort($userId, $groupId)
	{
		$filter = $groupId ? array("=GROUP_ID" => $groupId) : array("=USER_ID" => $userId);
		return static::getRow(array(
			"filter" => $filter,
			"order" => array("SORT" => "DESC")
		));
	}
}