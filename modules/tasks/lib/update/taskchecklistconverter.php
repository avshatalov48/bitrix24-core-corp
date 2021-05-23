<?php
namespace Bitrix\Tasks\Update;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Db\SqlQueryException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Update\Stepper;
use Bitrix\Tasks\Internals\Task\CheckListTable;

/**
 * Class TaskCheckListConverter
 *
 * @package Bitrix\Tasks\Update
 */
class TaskCheckListConverter extends Stepper
{
	protected static $moduleId = "tasks";

	public static $needOptionName = "needTaskCheckListConversion";
	protected static $paramsOptionName = "taskCheckListConversion";

	protected static $entityIdName = "TASK_ID";
	protected static $entityItemsTableName = "b_tasks_checklist_items";
	protected static $entityItemsTreeTableName = "b_tasks_checklist_items_tree";

	/** @var DataManager $entityItemsDataController */
	protected static $entityItemsDataController = CheckListTable::class;

	/**
	 * @param array $result
	 * @return bool
	 * @throws ArgumentException
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 * @throws LoaderException
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws ObjectPropertyException
	 */
	public function execute(array &$result)
	{
		if (!(
			Loader::includeModule("tasks") &&
			Option::get("tasks", static::$needOptionName, 'Y') === 'Y'
		))
		{
			return false;
		}

		$return = false;
		$found = false;

		$params = static::getParams();

		if ($params["count"] > 0)
		{
			$result["progress"] = 1;
			$result["steps"] = "";
			$result["count"] = $params["count"];

			$time = time();

			$entitiesIdsToConvert = static::getEntitiesIdsToConvert();
			foreach ($entitiesIdsToConvert as $entityId)
			{
				static::runConversionByEntityId($entityId);

				$params["number"]++;
				$found = true;

				if (time() - $time > 3)
				{
					break;
				}
			}

			if ($found)
			{
				Option::set("tasks", static::$paramsOptionName, serialize($params));
				$return = true;
			}

			$result["progress"] = (int)($params["number"] * 100 / $params["count"]);
			$result["steps"] = $params["number"];
		}

		if ($found === false)
		{
			Option::delete("tasks", ["name" => static::$paramsOptionName]);
			Option::set("tasks", static::$needOptionName, "N");

			$entityItemsTableName = static::$entityItemsTableName;

			$connection = Application::getConnection();
			$connection->query("DELETE FROM {$entityItemsTableName} WHERE TITLE = '==='");
		}

		return $return;
	}

	/**
	 * @return array|mixed|string
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 * @throws SqlQueryException
	 */
	private static function getParams()
	{
		$connection = Application::getConnection();

		$params = Option::get("tasks", static::$paramsOptionName);
		$params = ($params !== ""? unserialize($params, ['allowed_classes' => false]) : []);
		$params = (is_array($params)? $params : []);

		if (empty($params))
		{
			$entityIdName = static::$entityIdName;
			$entityItemsTableName = static::$entityItemsTableName;

			$entitiesCount = $connection->query("
				SELECT COUNT(CNT.{$entityIdName}) AS CNT
				FROM (
					SELECT {$entityIdName}
					FROM {$entityItemsTableName}
					WHERE TITLE = '==='
					GROUP BY {$entityIdName}
				) CNT
			")->fetch()['CNT'];

			$params = [
				"number" => 0,
				"count" => (int)$entitiesCount,
			];
		}

		return $params;
	}

	/**
	 * @return array
	 * @throws SqlQueryException
	 */
	protected static function getEntitiesIdsToConvert()
	{
		$ids = [];

		$connection = Application::getConnection();
		$tasksRes = $connection->query("
			SELECT I.TASK_ID, IF(T.STATUS = 5 OR T.STATUS = 6, 1, 0) as SORT
			FROM b_tasks_checklist_items I
				INNER JOIN b_tasks T on T.ID = I.TASK_ID
			WHERE I.TITLE = '==='
			GROUP BY I.TASK_ID
			ORDER BY SORT, I.TASK_ID DESC
			LIMIT 10
		");

		while ($task = $tasksRes->fetch())
		{
			$ids[] = $task['TASK_ID'];
		}

		return $ids;
	}

	/**
	 * @param $entityId
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	private static function runConversionByEntityId($entityId)
	{
		$items = static::fillItems($entityId);

		// empty($items) === all check list items are separators
		if (!empty($items))
		{
			static::clearOldRecords($entityId);
			static::insertCheckListRootItems($entityId, $items);
			$items = static::fillParents($entityId, $items);
			static::deleteUnnecessaryTreeConnections($entityId);
			static::insertTreeConnections($items);
		}

		$entityIdName = static::$entityIdName;
		$entityItemsTableName = static::$entityItemsTableName;

		$connection = Application::getConnection();
		$connection->query("DELETE FROM {$entityItemsTableName} WHERE TITLE = '===' AND {$entityIdName} = {$entityId}");
	}

	/**
	 * @param $entityId
	 * @return array
	 * @throws ArgumentException
	 * @throws SystemException
	 * @throws ObjectPropertyException
	 */
	private static function fillItems($entityId)
	{
		$i = 0;
		$items = [];
		$entityItemsDataController = static::$entityItemsDataController;

		$checkListItemsRes = $entityItemsDataController::getList([
			'select' => ['ID', 'TITLE'],
			'filter' => [static::$entityIdName => $entityId],
			'order' => ['SORT_INDEX' => 'ASC']
		]);

		while ($item = $checkListItemsRes->fetch())
		{
			if ($item['TITLE'] === '===')
			{
				if (!empty($items[$i]))
				{
					$i++;
				}

				continue;
			}

			$items[$i]['ITEMS'][] = $item['ID'];
		}

		return $items;
	}

	/**
	 * @param $taskId
	 * @param $items
	 * @throws SqlQueryException
	 */
	protected static function insertCheckListRootItems($taskId, $items)
	{
		// insert BX_CHECKLIST_#NUM# items
		$itemsCount = count($items);
		$connection = Application::getConnection();

		$sql = "INSERT INTO b_tasks_checklist_items (TASK_ID, CREATED_BY, TITLE, SORT_INDEX)
				VALUES ";

		for ($i = 0; $i < $itemsCount; $i++)
		{
			if ($i)
			{
				$sql .= ",";
			}

			$sql.= "(" . $taskId . ",1,'BX_CHECKLIST_" . ($i + 1) . "'," . $i . ")";
		}

		$connection->query($sql);
	}

	/**
	 * @param $entityId
	 * @throws SqlQueryException
	 */
	private static function clearOldRecords($entityId)
	{
		$connection = Application::getConnection();

		$entityIdName = static::$entityIdName;
		$entityItemsTableName = static::$entityItemsTableName;
		$entityItemsTreeTableName = static::$entityItemsTreeTableName;

		$roots = $connection->query("
			SELECT ID
			FROM {$entityItemsTableName}
			WHERE {$entityIdName} = {$entityId} AND TITLE LIKE 'BX_CHECKLIST_%'
		")->fetchAll();

		if (empty($roots))
		{
			return;
		}

		$rootIds = '(';
		foreach ($roots as $root)
		{
			$rootIds .= $root['ID'].',';
		}
		$rootIds = rtrim($rootIds, ',').')';

		$connection->query("
			DELETE FROM {$entityItemsTreeTableName}
			WHERE PARENT_ID IN {$rootIds} OR CHILD_ID IN {$rootIds}
		");
		$connection->query("
			DELETE FROM {$entityItemsTableName}
			WHERE ID IN {$rootIds}
		");
	}

	/**
	 * @param $entityId
	 * @param $items
	 * @return mixed
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private static function fillParents($entityId, $items)
	{
		$itemsCount = count($items);
		$connection = Application::getConnection();

		if ($itemsCount === 1)
		{
			$items[0]['PARENT_ID'] = $connection->getInsertedId();
		}
		else
		{
			$entityItemsDataController = static::$entityItemsDataController;
			$parents = $entityItemsDataController::getList([
				'select' => ['ID'],
				'filter' => [
					static::$entityIdName => $entityId,
					'%=TITLE' => 'BX_CHECKLIST\_%'
				]
			]);

			$i = 0;
			while ($item = $parents->fetch())
			{
				$items[$i]['PARENT_ID'] = $item['ID'];
				$i++;
			}
		}

		return $items;
	}

	/**
	 * @param $entityId
	 * @throws SqlQueryException
	 */
	private static function deleteUnnecessaryTreeConnections($entityId)
	{
		$connection = Application::getConnection();

		$entityNameId = static::$entityIdName;
		$entityItemsTableName = static::$entityItemsTableName;
		$entityItemsTreeTableName = static::$entityItemsTreeTableName;

		$connection->query("
			DELETE FROM {$entityItemsTreeTableName}
			WHERE PARENT_ID IN (SELECT ID FROM {$entityItemsTableName} WHERE {$entityNameId} = {$entityId})
			  AND (LEVEL > 0 OR PARENT_ID <> CHILD_ID)
		");
	}

	/**
	 * @param $items
	 * @throws SqlQueryException
	 */
	private static function insertTreeConnections($items)
	{
		$connection = Application::getConnection();
		$entityItemsTreeTableName = static::$entityItemsTreeTableName;

		$sql = "INSERT INTO {$entityItemsTreeTableName} (PARENT_ID, CHILD_ID, LEVEL)
				VALUES ";

		foreach ($items as $key => $item)
		{
			if ($key)
			{
				$sql .= ",";
			}

			$sql .= "(" . $item['PARENT_ID'] . "," . $item['PARENT_ID'] . ",0),";
			$sql .= "(" . $item['PARENT_ID'] . ",";
			$sql .= implode(", 1),(" . $item['PARENT_ID'] . ",", $item['ITEMS']);
			$sql .= ", 1)";
		}

		$connection->query($sql);
	}
}