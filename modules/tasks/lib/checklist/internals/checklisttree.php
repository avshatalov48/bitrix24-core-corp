<?php
namespace Bitrix\Tasks\CheckList\Internals;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals\DataBase\Helper;
use Bitrix\Tasks\Internals\Log\Logger;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Util\Result;
use Exception;

Loc::loadMessages(__FILE__);

/**
 * Class CheckListTree
 *
 * @package Bitrix\Tasks\CheckList\Internals
 */
abstract class CheckListTree
{
	protected static $locPrefix = 'TASKS_CHECKLIST_TREE_';

	#region table data

	/**
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function getDataController()
	{
		throw new NotImplementedException('Default checklist tree table class doesnt exist');
	}

	/**
	 * @return string
	 * @throws NotImplementedException
	 */
	protected static function getTableName()
	{
		/** @var DataManager $dataController */
		$dataController = static::getDataController();
		return $dataController::getTableName();
	}

	/**
	 * @return string
	 */
	protected static function getParentNodeColumnName()
	{
		return 'PARENT_ID';
	}

	/**
	 * @return string
	 */
	protected static function getNodeColumnName()
	{
		return 'CHILD_ID';
	}

	/**
	 * @return string
	 */
	protected static function getLevelColumnName()
	{
		return 'LEVEL';
	}

	#endregion

	#region primary operations

	/**
	 * @param $id
	 * @param int $parentId
	 * @return Result
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function canAttach($id, $parentId = 0)
	{
		$result = new Result();

		$id = (int)$id;
		$parentId = (int)$parentId;

		$replaces = [
			'#ID#' => $id,
			'#PARENT_ID#' => $parentId,
		];

		if (!$id)
		{
			$result = static::addErrorToResult($result, $replaces, __METHOD__, 'ILLEGAL_NODE');
		}
		else if ($parentId)
		{
			if ($id === $parentId)
			{
				$result = static::addErrorToResult($result, $replaces, __METHOD__, 'SELF_ATTACH');
			}
			else if (static::isPathExist($id, $parentId))
			{
				$result = static::addErrorToResult($result, $replaces, __METHOD__, 'CHILD_ATTACH');
			}
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param int $parentId
	 * @param array $parameters
	 * @return Result
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function attach($id, $parentId = 0, array $parameters = [])
	{
		$result = static::canAttach($id, $parentId);

		$replaces = [
			'#ID#' => $id,
			'#PARENT_ID#' => $parentId,
		];

		if ($result->isSuccess())
		{
			// check if link is already exists
			if ($parentId && static::isPathExist($parentId, $id, ['DIRECT' => true]))
			{
				$result = static::addErrorToResult($result, $replaces, __METHOD__, 'PATH_EXISTS');
				return $result;
			}

			if (static::isNodeExist($id))
			{
				if ($parameters['NEW_NODE'] ?? null)
				{
					$result = static::addErrorToResult($result, $replaces, __METHOD__, 'EXISTING_NODE_ADDING');
					$logData = [
						'first' => CheckListTable::getById($id)->fetch(),
						'second' => CheckListTable::getById($parentId)->fetch(),
						'dc' => static::getDataController(),
						'pcn' => static::getParentNodeColumnName(),
						'ccn' => static::getNodeColumnName(),
					];

					Logger::log($logData, 'TASKS_CHECKLIST_RACE_CONDITION');
				}
				else
				{
					// we should do detach node from the previous point, if any
					$detachResult = static::detach($id);
					$result->loadErrors($detachResult->getErrors());
				}
			}

			// if !$parentId, then it behaves like detach()
			if ($parentId && $result->isSuccess())
			{
				$connection = Application::getConnection();

				$tableName = static::getTableName();
				$parentColumnName = static::getParentNodeColumnName();
				$childColumnName = static::getNodeColumnName();
				$levelColumnName = static::getLevelColumnName();

				// attach to a new point
				static::ensureNodeExists($parentId);
				static::ensureNodeExists($id);

				// now link each item of path to $parentId with each item of subtree of $id
				$connection->query("
					INSERT INTO {$tableName} (PARENT_ID, CHILD_ID, LEVEL)
					SELECT P.{$parentColumnName},
						   CH.{$childColumnName},
						   P.{$levelColumnName} + CH.{$levelColumnName} + 1 AS {$levelColumnName}
					FROM {$tableName} P
					CROSS JOIN (
						SELECT {$id} AS {$childColumnName}, 0 AS {$levelColumnName}
						UNION
						SELECT CH1.{$childColumnName}, CH2.{$levelColumnName} + 1 AS {$levelColumnName}
						FROM {$tableName} CH1
						LEFT JOIN {$tableName} CH2 ON CH1.{$parentColumnName} = CH2.{$childColumnName}
						WHERE CH2.{$parentColumnName} = {$id} AND CH1.{$levelColumnName} = 1
						) CH
					WHERE P.{$childColumnName} = {$parentId}
				");
			}
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param $parentId
	 * @param array $parameters
	 * @return Result
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function attachNew($id, $parentId, array $parameters = [])
	{
		$parameters['NEW_NODE'] = true;
		return static::attach($id, $parentId, $parameters);
	}

	/**
	 * @param $id
	 * @return Result
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function detach($id)
	{
		$result = new Result();

		if (!static::isNodeExist($id))
		{
			$result = static::addErrorToResult($result, ['#ID#' => $id], __METHOD__, 'NODE_NOT_FOUND');
		}
		else
		{
			$connection = Application::getConnection();

			$tableName = static::getTableName();
			$parentColumnName = static::getParentNodeColumnName();
			$childColumnName = static::getNodeColumnName();
			$levelColumnName = static::getLevelColumnName();

			/** @noinspection PhpUndefinedClassInspection */
			$parentSubQuerySql = Helper::getTemporaryTableSubQuerySql(
				"SELECT {$parentColumnName} FROM {$tableName} WHERE {$childColumnName} = {$id} AND {$levelColumnName} <> 0",
				$parentColumnName
			);

			/** @noinspection PhpUndefinedClassInspection */
			$childSubQuerySql = Helper::getTemporaryTableSubQuerySql(
				"SELECT {$childColumnName} FROM {$tableName} WHERE {$parentColumnName} = {$id}",
				$childColumnName
			);

			$sql = "
				DELETE FROM {$tableName}
				WHERE
					/*nodes from path (above node)*/
					{$parentColumnName} IN ({$parentSubQuerySql})
					AND
					/*nodes from subtree (below node + node itself)*/
					{$childColumnName} IN ({$childSubQuerySql})
			";

			try
			{
				$connection->query($sql);
			}
			catch (SqlException $exception)
			{
				$result->addException($exception);
			}

			if ($result->isSuccess())
			{
				$removed = $connection->getAffectedRowsCount();
				if (!$removed)
				{
					$result->addWarning('NO_ROWS_AFFECTED', 'No rows were affected');
				}
			}
		}

		return $result;
	}

	/**
	 * @param $rootId
	 * @return Result
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function detachSubTree($rootId)
	{
		$result = new Result();

		if (!static::isNodeExist($rootId))
		{
			$result = static::addErrorToResult($result, ['#ID#' => $rootId], __METHOD__, 'NODE_NOT_FOUND');
		}
		else
		{
			$children = static::getChildren($rootId);

			if (empty($children))
			{
				$result = static::detach($rootId);
			}
			else
			{
				$children[] = $rootId;
				$children = implode(', ', $children);

				$connection = Application::getConnection();

				$tableName = static::getTableName();
				$parentColumnName = static::getParentNodeColumnName();
				$childColumnName = static::getNodeColumnName();
				$levelColumnName = static::getLevelColumnName();

				/** @noinspection PhpUndefinedClassInspection */
				$parentSubQuerySql = Helper::getTemporaryTableSubQuerySql(
					"SELECT {$parentColumnName} FROM {$tableName} WHERE {$childColumnName} = {$rootId} AND {$levelColumnName} <> 0",
					$parentColumnName
				);

				$sql = "
					DELETE FROM {$tableName}
					WHERE {$parentColumnName} IN ({$parentSubQuerySql}) AND {$childColumnName} IN ({$children})
				";

				try
				{
					$connection->query($sql);
				}
				catch (SqlException $exception)
				{
					$result->addException($exception);
				}

				if ($result->isSuccess())
				{
					$removed = $connection->getAffectedRowsCount();
					if (!$removed)
					{
						$result->addWarning('NO_ROWS_AFFECTED', 'No rows were affected');
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param array $parameters
	 * @return Result
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function delete($id, array $parameters = [])
	{
		$result = new Result();

		if (!static::isNodeExist($id))
		{
			$result = static::addErrorToResult($result, ['#ID#' => $id], __METHOD__, 'NODE_NOT_FOUND');
		}
		else if ($parameters['DELETE_SUBTREE'])
		{
			$connection = Application::getConnection();

			$tableName = static::getTableName();
			$parentColumnName = static::getParentNodeColumnName();
			$childColumnName = static::getNodeColumnName();

			/** @noinspection PhpUndefinedClassInspection */
			$subQuerySql = Helper::getTemporaryTableSubQuerySql(
				"SELECT {$childColumnName} FROM {$tableName} WHERE {$parentColumnName} = {$id}",
				$childColumnName
			);

			$sql = "
				DELETE FROM {$tableName}
				WHERE {$childColumnName} IN ({$subQuerySql})
			";

			try
			{
				$connection->query($sql);
			}
			catch (SqlException $exception)
			{
				$result->addException($exception);
			}

			if($result->isSuccess())
			{
				$removed = $connection->getAffectedRowsCount();
				if(!$removed)
				{
					$result->addWarning('NO_ROWS_AFFECTED', 'No rows were affected');
				}
			}
		}
		else
		{
			$parentId = static::getClosestParentId($id);

			$detachResult = static::detach($id); //detach node with its subtree
			$result->adoptErrors($detachResult);

			if ($detachResult->isSuccess())
			{
				$children = static::getChildren($id, ['DIRECT' => true]);

				if (!empty($children))
				{
					foreach ($children as $childId)
					{
						$subDetachResult = static::detach($childId); //detach each sub-tree
						$result->adoptErrors($subDetachResult);

						if ($parentId !== $id && $subDetachResult->isSuccess())
						{
							static::attach($childId, $parentId); //attach each sub-tree to the parent
						}
					}
				}

				static::delete($id, ['DELETE_SUBTREE' => true]); //remove node itself
			}
		}

		return $result;
	}

	#endregion

	#region secondary operations

	/**
	 * @param $id
	 * @return int
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function getClosestParentId($id)
	{
		$id = (int)$id;

		/** @var DataManager $dataController */
		$dataController = static::getDataController();
		$parentColumnName = static::getParentNodeColumnName();
		$childColumnName = static::getNodeColumnName();
		$levelColumnName = static::getLevelColumnName();

		$item = $dataController::getList([
			'select' => [$parentColumnName],
			'filter' => [
				$childColumnName => $id,
				$levelColumnName => 1
			],
		])->fetch();

		if (!$item)
		{
			return $id;
		}

		return $item[$parentColumnName];
	}

	/**
	 * @param $id
	 * @return string|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function getRootId($id)
	{
		$id = (int)$id;
		if (!$id)
		{
			return null;
		}

		/** @var DataManager $dataController */
		$dataController = static::getDataController();
		$parentColumnName = static::getParentNodeColumnName();
		$childColumnName = static::getNodeColumnName();
		$levelColumnName = static::getLevelColumnName();

		$item = $dataController::getList([
			'select' => [$parentColumnName],
			'filter' => [$childColumnName => $id],
			'order' => [$levelColumnName => 'DESC'],
			'limit' => 1
		])->fetch();

		return $item[$parentColumnName];
	}

	/**
	 * @param $id
	 * @param array $parameters
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function getChildren($id, array $parameters = [])
	{
		$children = [];

		$id = (int)$id;

		/** @var DataManager $dataController */
		$dataController = static::getDataController();
		$parentColumnName = static::getParentNodeColumnName();
		$childColumnName = static::getNodeColumnName();
		$levelColumnName = static::getLevelColumnName();

		$filter = [
			$parentColumnName => $id,
			'!' . $levelColumnName => 0
		];

		if ($parameters['DIRECT'])
		{
			$filter[$levelColumnName] = 1;
		}

		$res = $dataController::getList([
			'select' => [$childColumnName],
			'filter' => $filter
		]);

		while ($child = $res->fetch())
		{
			$children[] = (int)$child['CHILD_ID'];
		}

		return $children;
	}

	/**
	 * @param $id
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function isNodeExist($id)
	{
		$id = (int)$id;

		/** @var DataManager $dataController */
		$dataController = static::getDataController();
		$parentColumnName = static::getParentNodeColumnName();
		$childColumnName = static::getNodeColumnName();

		$item = $dataController::getList([
			'select' => ['PARENT_ID'],
			'filter' => [
				$parentColumnName => $id,
				$childColumnName => $id
			]
		])->fetch();

		return (bool)$item;
	}

	/**
	 * @param $parentId
	 * @param $id
	 * @param array $parameters
	 * @return bool
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public static function isPathExist($parentId, $id, array $parameters = [])
	{
		$id = (int)$id;
		$parentId = (int)$parentId;

		// no path from\to nowhere
		if (!$id || !$parentId)
		{
			return false;
		}

		/** @var DataManager $dataController */
		$dataController = static::getDataController();
		$parentColumnName = static::getParentNodeColumnName();
		$childColumnName = static::getNodeColumnName();
		$levelColumnName = static::getLevelColumnName();

		$filter = [
			$parentColumnName => $parentId,
			$childColumnName => $id
		];

		if ($parameters['DIRECT'] ?? null)
		{
			$filter[$levelColumnName] = 1;
		}

		if ($parameters['BOTH_DIRECTIONS'] ?? null)
		{
			$filter = [
				'LOGIC' => 'OR',
				$filter,
				[
					$parentColumnName => $id,
					$childColumnName => $parentId
				]
			];
		}

		$item = $dataController::getList([
			'select' => ['PARENT_ID'],
			'filter' => $filter
		])->fetch();

		return (bool)$item;
	}

	/**
	 * @param $id
	 * @return bool
	 * @throws NotImplementedException
	 */
	public static function ensureNodeExists($id)
	{
		$id = (int)$id;

		/** @var DataManager $dataController */
		$dataController = static::getDataController();
		$parentColumnName = static::getParentNodeColumnName();
		$childColumnName = static::getNodeColumnName();
		$levelColumnName = static::getLevelColumnName();

		try
		{
			$dataController::add([
				$parentColumnName => $id,
				$childColumnName => $id,
				$levelColumnName => 0
			]);
		}
		catch (Exception $exception)
		{
			return false;
		}

		return true;
	}

	#endregion

	/**
	 * @param Result $result
	 * @param array $replaces
	 * @param string $method
	 * @param string $message
	 * @return Result
	 */
	private static function addErrorToResult($result, $replaces, $method, $message)
	{
		$search = array_keys($replaces);
		$replace = array_values($replaces);

		$code = $message;
		$message = $method.': '.str_replace($search, $replace, Loc::getMessage(static::$locPrefix.$message));

		$result->addError($code, $message);

		return $result;
	}
}