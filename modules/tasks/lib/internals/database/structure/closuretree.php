<?
/**
 * Closure table tree implementation
 *
 * Tree struct and data fields are kept in separate tables
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */
namespace Bitrix\Tasks\Internals\DataBase\Structure;

use Bitrix\Disk\Internals\DataManager;
use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Internals\DataBase\Helper;
use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Util;

Loc::loadMessages(__FILE__);

abstract class ClosureTree
{
	/**
	 * @throws Main\NotImplementedException
	 * @return string
	 */
	protected static function getDataController()
	{
		throw new Main\NotImplementedException('No data controller');
	}

	protected static function getNodeColumnName()
	{
		return 'ITEM_ID';
	}

	protected static function getParentNodeColumnName()
	{
		return 'PARENT_ITEM_ID';
	}

	/**
	 * @return string
	 * @throws Main\NotImplementedException
	 */
	public static function getTableName()
	{
		/** @var DataManager $dc */
		$dc = static::getDataController();

		return $dc::getTableName();
	}

	public static function canAttach($id, $parentId = 0)
	{
		$result = new Util\Result();

		$id = intval($id);
		$parentId = intval($parentId);

		if(!$id)
		{
			$result->addError('ILLEGAL_ARGUMENT', Loc::getMessage('TASKS_CLOSURE_TREE_ILLEGAL_NODE'));
		}
		elseif($parentId)
		{
			if($id == $parentId)
			{
				$result->addError('ILLEGAL_ATTACH', Loc::getMessage('TASKS_CLOSURE_TREE_CANT_ATTACH_TO_SELF'));
			}
			elseif(static::isPathExist($id, $parentId))
			{
				$result->addError('ILLEGAL_ATTACH', Loc::getMessage('TASKS_CLOSURE_TREE_CANT_ATTACH_TO_CHILD'));
			}
		}

		return $result;
	}

	/**
	 * Attach node $id to node $parentId. If node $id has subtree, it will be relocated
	 *
	 * @param $id
	 * @param $parentId
	 * @param mixed[] $settings
	 * @return Util\Result
	 */
	public static function attach($id, $parentId = 0, array $settings = array())
	{
		$result = static::canAttach($id, $parentId);

		if($result->isSuccess())
		{
			// check if link is already on its place
			if($parentId && static::isPathExist($parentId, $id, array('DIRECT' => true)))
			{
				$result->addWarning('PATH_EXISTS', Loc::getMessage('TASKS_CLOSURE_TREE_LINK_EXISTS'));
				return $result;
			}

			if(static::isNodeExist($id))
			{
				if ($settings['NEW_NODE'] ?? null)
				{
					$result->addError('NODE_EXISTS', Loc::getMessage('TASKS_CLOSURE_TREE_NODE_EXISTS_BUT_DECLARED_NEW'));
				}
				else
				{
					// we should do detach node from the previous point, if any
					$dResult = static::detach($id);
					$result->adoptErrors($dResult);
				}
			}

			// if !$parentId, then it behaves like detachNode()
			if($result->isSuccess() && $parentId)
			{
				// attach to a new point
				static::ensureNodeExists($parentId);
				static::ensureNodeExists($id);

				$pCName = static::getParentNodeColumnName();
				$cName = static::getNodeColumnName();

				// now link each item of path to $parentId with each item of subtree of $id

				// todo: rewrite this IN SQL
				$path = static::getPath($parentId, array(), array('RETURN_ARRAY' => true));
				$subTree = static::getSubTree($id, array(), array('RETURN_ARRAY' => true));

				$edgeBuffer = array();

				foreach($path as $pItem)
				{
					foreach($subTree as $stItem)
					{
						$edgeBuffer[] = array(
							$pCName => $pItem['__ID'],
							$cName => $stItem['__ID'],
							'DIRECT' => $pItem['__ID'] == $parentId && $stItem['__ID'] == $id ? 1 : 0
						);
					}
				}

				try
				{
					Helper::insertBatch(static::getTableName(), $edgeBuffer);
				}
				catch(DB\SqlException $e)
				{
					$result->addException($e, 'Error linking nodes');
				}
			}
		}

		return $result;
	}

	/**
	 * Attaches new node (without checking for subtree existence)
	 *
	 * @param $id
	 * @param $parentId
	 * @param array $settings
	 * @return Util\Result
	 */
	public static function attachNew($id, $parentId, array $settings = array())
	{
		$settings['NEW_NODE'] = true;
		return static::attach($id, $parentId, $settings);
	}

	/**
	 * Detach node $id from its parent
	 *
	 * @param $id
	 * @return Util\Result
	 */
	public static function detach($id)
	{
		$result = new Util\Result();

		if(!static::isNodeExist($id))
		{
			$result->addError('NODE_NOT_FOUND', Loc::getMessage('TASKS_CLOSURE_TREE_NODE_NOT_FOUND'));
		}
		else
		{
			$pCName = static::getParentNodeColumnName();
			$cName = static::getNodeColumnName();
			$tableName = static::getTableName();

			$connection = Main\HttpApplication::getConnection();

			// detach node from its parent, if any...
			$sql = "
					delete
					from
						".$tableName."
					where
						/*nodes from path (above node)*/
						".$pCName." in (
							".Helper::getTemporaryTableSubQuerySql("
								select ".$pCName." from ".$tableName." where ".$cName." = '".intval($id)."' and ".$pCName." != ".$cName."
							", $pCName)."
						)
						and
						/*nodes from subtree (below node + node itself)*/
						".$cName." in (
							".Helper::getTemporaryTableSubQuerySql("
								select ".$cName." from ".$tableName." where ".$pCName." = '".intval($id)."'
							", $cName)."
						)
			";

			try
			{
				$connection->query($sql);
			}
			catch(DB\SqlException $e)
			{
				$result->addException($e, 'Error detaching node');
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

		return $result;
	}

	/**
	 * Deletes node $id and, optionally, its entire sub-tree
	 *
	 * @param $id
	 * @param mixed[] $settings
	 * @return Util\Result
	 */
	public static function delete($id, array $settings = array())
	{
		$result = new Util\Result();

		if(!static::isNodeExist($id))
		{
			$result->addError('NODE_NOT_FOUND', Loc::getMessage('TASKS_CLOSURE_TREE_NODE_NOT_FOUND'));
		}
		else
		{
			$pCName = static::getParentNodeColumnName();
			$cName = static::getNodeColumnName();
			$tableName = static::getTableName();

			$connection = Main\HttpApplication::getConnection();

			if ($settings['DELETE_SUBTREE'] ?? null)
			{
				$sql = "
					delete from ".$tableName." where ".$cName." in (
						".Helper::getTemporaryTableSubQuerySql("select ".$cName." from ".$tableName." where ".$pCName." = '".intval($id)."'", $cName)."
					)
				";
			}
			else
			{
				// we need to detach node with its subtree, then detach each sub-tree, then remove node itself
				$sql = "
					delete
					from
						".$tableName."
					where
						/*nodes from path (above node) + node itself */
						".$pCName." in (
							".Helper::getTemporaryTableSubQuerySql("
								select ".$pCName." from ".$tableName." where ".$cName." = '".intval($id)."' and ".$pCName." != ".$cName."
							", $pCName)."
							union
							select '".intval($id)."'
						)
						and
						/*nodes from subtree (below node + node itself)*/
						".$cName." in (
							".Helper::getTemporaryTableSubQuerySql("
								select ".$cName." from ".$tableName." where ".$pCName." = '".intval($id)."'
							", $cName)."
						)
				";
			}

			try
			{
				$connection->query($sql);
			}
			catch(DB\SqlException $e)
			{
				$result->addException($e, 'Error removing subtree');
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

		return $result;
	}

	/**
	 * Checks if node $id exists
	 *
	 * @param $id
	 * @return bool
	 */
	public static function isNodeExist($id)
	{
		$pCName = static::getParentNodeColumnName();
		$cName = static::getNodeColumnName();
		$tableName = static::getTableName();

		$connection = Main\HttpApplication::getConnection();

		$item = $connection->query("
			select 1
			from
				".$tableName."
			where ".$cName." = '".intval($id)."' and ".$pCName." = '".intval($id)."'
		")->fetch();

		return !!$item;
	}

	/**
	 * Checks if there is a path from node $parentId to node $id
	 *
	 * @param $id
	 * @param $parentId
	 * @param mixed[] $settings
	 * @return bool
	 */
	public static function isPathExist($parentId, $id, array $settings = array())
	{
		$id = intval($id);
		$parentId = intval($parentId);

		if(!$id || !$parentId)
		{
			// no path from\to nowhere
			return false;
		}

		$pCName = static::getParentNodeColumnName();
		$cName = static::getNodeColumnName();
		$tableName = static::getTableName();

		$connection = Main\HttpApplication::getConnection();

		$res = $connection->query("
			select 1
			from
				".$tableName."
			where

			(
			 ".$pCName." = ".intval($parentId)."
			 and
			 ".$cName." = ".intval($id)."

			 ".(($settings['DIRECT'] ?? null) ? "

			    and DIRECT = '1'

			 " : "")."
			)
			".(($settings['BOTH_DIRECTIONS'] ?? null) ? "

			or
			(
			 ".$cName." = ".intval($parentId)."
			 and
			 ".$pCName." = ".intval($id)."
			)

			" : "")."

		")->fetch();

		return !!$res;
	}

	/**
	 * Returns path from tree root to node $id
	 *
	 * @param $id
	 * @param array $parameters
	 * @param array $settings
	 * @return array
	 * @throws Main\NotImplementedException
	 */
	public static function getPath($id, array $parameters = array(), array $settings = array())
	{
		/** @var DataManager $dc */
		$dc = static::getDataController();
		$pCName = static::getParentNodeColumnName();
		$cName = static::getNodeColumnName();

		$parameters['filter']['='.$cName] = $id;
		$parameters['select'][] = $pCName;

		$res = $dc::getList($parameters);
		$result = array();
		while($item = $res->fetch())
		{
			$item['__PARENT_ID'] = 0; // todo: need to make join here
			$item['__ID'] = $item[$pCName];
			unset($item[$pCName]);

			$result[$item['__ID']] = $item;
		}

		if($settings['RETURN_ARRAY'])
		{
			return $result;
		}

		return new ClosureTree\Fragment($result);
	}

	/**
	 * Returns subtree for node $id
	 *
	 * todo: implement RETURN_DBRESULT key, if needed
	 *
	 * @param $id
	 * @param array $parameters
	 * @param array $settings
	 * @return array|ClosureTree\Fragment
	 * @throws Main\NotImplementedException
	 */
	public static function getSubTree($id, array $parameters = array(), array $settings = array())
	{
		/** @var DataManager $dc */
		$dc = static::getDataController();
		$pCName = static::getParentNodeColumnName();
		$cName = static::getNodeColumnName();

		$id = intval($id);

		$parameters['runtime'][] = new Entity\ReferenceField(
			'TDD',
			$dc::getEntity(),
			array(
				'=ref.'.$cName => 'this.'.$cName,
				'=ref.DIRECT' => array('?', '1'),
				'!=ref.'.$cName => 'ref.'.$pCName,
			),
			array(
				//'join_type' => 'inner'
			)
		);

		$parameters['filter']['='.$pCName] = $id;

		$parameters['select'][] = $cName;
		$parameters['select']['ACTUAL_PARENT_ID'] = 'TDD.'.$pCName;

		$res = $dc::getList($parameters);
		$data = array();

		$returnArray = ($settings['RETURN_ARRAY'] ?? null);

		if ($returnArray && ($settings['GROUP_PARENT'] ?? null))
		{
			while($item = $res->fetch())
			{
				$parentId = $item['ACTUAL_PARENT_ID'];
				unset($item['ACTUAL_PARENT_ID']);

				$data[intval($parentId)][$item[$cName]] = $item;
			}

			return $data;
		}

		while($item = $res->fetch())
		{
			$item['__PARENT_ID'] = $item['ACTUAL_PARENT_ID'];
			$item['__ID'] = $item[$cName];

			unset($item['ACTUAL_PARENT_ID']);
			unset($item[$cName]);

			$data[$item['__ID']] = $item;
		}

		if ($returnArray)
		{
			return $data;
		}

		return new ClosureTree\Fragment($data);
	}

	/**
	 * Returns parent tree for node $id
	 *
	 * @param $id
	 * @param array $parameters
	 * @param array $settings
	 * @return array|ClosureTree\Fragment
	 * @throws Main\NotImplementedException
	 */
	public static function getParentTree($id, array $parameters = array(), array $settings = array())
	{
		$root = static::getRootNode($id, array(), array('RETURN_ARRAY' => true));

		return static::getSubTree((int)($root['__ID'] ?? null), $parameters, $settings);
	}

	public static function getCount()
	{
		$tableName = static::getTableName();
		$connection = Main\HttpApplication::getConnection();

		$res = $connection->query("select count(*) as CNT from ".$tableName)->fetch();

		return intval($res['CNT']);
	}

	private static function getRootNode($id, array $parameters = array(), array $settings = array())
	{
		$id = intval($id);
		if(!$id)
		{
			return null;
		}

		if(!$settings['RETURN_ARRAY'])
		{
			// todo: future-reserved, to be able to return object
			throw new Main\NotImplementedException();
		}

		/** @var DataManager $dc */
		$dc = static::getDataController();
		$pCName = static::getParentNodeColumnName();
		$cName = static::getNodeColumnName();

		$parameters['runtime'][] = new Entity\ReferenceField(
			'TDD',
			$dc::getEntity(),
			array(
				'=ref.'.$cName => 'this.'.$pCName,
				//'=ref.DIRECT' => array('?', '1'),
				'!=ref.'.$cName => 'ref.'.$pCName,
			),
			array(
				'join_type' => 'left'
			)
		);
		$parameters['filter']['='.$cName] = $id;
		$parameters['filter']['=TDD.'.$pCName] = false;
		$parameters['limit'] = 1;

		$parameters['select'][] = $pCName;

		$item = $dc::getList($parameters)->fetch();

		if($item)
		{
			$item['__ID'] = $item[$pCName];
			unset($item[$pCName]);
		}

		return $item;
	}

	private static function ensureNodeExists($id)
	{
		$pCName = static::getParentNodeColumnName();
		$cName = static::getNodeColumnName();
		$tableName = static::getTableName();

		$connection = Main\HttpApplication::getConnection();

		try
		{
			$connection->query("insert into ".$tableName." (".$cName.", ".$pCName.", DIRECT) values (
				".intval($id).", ".intval($id).", '0'
			)");
		}
		catch(DB\SqlException $e)
		{
			return false;
		}

		return true;
	}
}