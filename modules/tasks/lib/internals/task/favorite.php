<?
namespace Bitrix\Tasks\Internals\Task;

use Bitrix\Main\HttpApplication;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Util\Assert;
use Bitrix\Tasks\TaskTable;

Loc::loadMessages(__FILE__);

/**
 * Class FavoriteTable
 *
 * Fields:
 * <ul>
 * <li> TASK_ID int mandatory
 * <li> USER_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Tasks
 **/

class FavoriteTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_tasks_favorite';
	}

	/**
	 * @return static
	 */
	public static function getClass()
	{
		return get_called_class();
	}

	/**
	 * Checks if a task is in favorites for a particular (or current) user. This function DOES NOT check permissions.
	 *
	 * @param mixed[] Primary key for \Bitrix\Tasks\Task\FavoriteTable entity
	 *
	 * @return boolean
	 */
	public static function check($primary)
	{
		$primary = static::processPrimary($primary);

		return static::getById($primary)->fetch();
	}

	/**
	 * Switch "favoriteness" of a certain task for a particular (or current) user. This function DOES NOT check permissions.
	 *
	 * @param mixed[] Primary key for \Bitrix\Tasks\Task\FavoriteTable entity
	 * @param mixed[] Does nothing, for prototype consistency with add() and delete() :)
	 *
	 * @return \Bitrix\Main\Entity\AddResult|\Bitrix\Main\Entity\DeleteResult Returns \Bitrix\Main\Entity\AddResult if task was not favorite, but became such, \Bitrix\Main\Entity\DeleteResult otherwise
	 */
	public static function toggle($primary, $behaviour = array())
	{
		$primary = static::processPrimary($primary);

		if (static::check($primary))
		{
			return parent::delete($primary);
		}
		else
		{
			return parent::add($primary);
		}
	}

	/**
	 * Adds a task to favorites for a particular (or current) user. This function DOES NOT check permissions.
	 *
	 * @param mixed[] Primary key for \Bitrix\Tasks\Task\FavoriteTable entity
	 * @param mixed[] Behaviour flags
	 *
	 *    <li> CHECK_EXISTENCE boolean if true, function will check if the task is already in favorites (+1 db query) (default true). Setting this to false may cause SQL error.
	 *  <li> AFFECT_CHILDREN boolean if true, all child tasks also will be added to favorite. (default false)
	 *
	 * @return \Bitrix\Main\Entity\AddResult
	 */
	public static function add(array $data)
	{
		// to avoid warnings in php7
		$behaviour = null;
		if (func_num_args() > 1)
		{
			$behaviour = func_get_arg(1);
		}

		if (!is_array($behaviour))
			$behaviour = array();
		if (!isset($behaviour['CHECK_EXISTENCE']))
			$behaviour['CHECK_EXISTENCE'] = true;
		if (!isset($behaviour['AFFECT_CHILDREN']))
			$behaviour['AFFECT_CHILDREN'] = false;

		$data = static::processPrimary($data);

		// already there, we dont want \Bitrix\Main\DB\SqlQueryException "Duplicate entry" crumble everywhere
		if ($behaviour['CHECK_EXISTENCE'] && static::check($data))
		{
			$result = new \Bitrix\Main\Entity\AddResult();
		}
		else
		{
			$result = parent::add($data);
		}

		if ($result->isSuccess() && $behaviour['AFFECT_CHILDREN'])
		{
			// add also all children...
			$res = TaskTable::getChildrenTasksData($data['TASK_ID'], array(
				'runtime' => TaskTable::getRuntimeFieldMixins(array('IN_FAVORITE'), array('USER_ID' => $data['USER_ID'])),
				'select' => array('IN_FAVORITE'),
			));
			while ($item = $res->fetch())
			{
				if (!$item['IN_FAVORITE'])
				{
					// our client
					static::add(array(
						'TASK_ID' => $item['ID']
					), array(
						'AFFECT_CHILDREN' => false,
						'CHECK_EXISTENCE' => false // list was already filtered by IN_FAVORITE, so no check is needed
					));
				}
			}
		}

		return $result;
	}

	/**
	 * Removes a task from favorites for a particular (or current) user. This function DOES NOT check permissions.
	 *
	 * @param mixed[] Primary key for \Bitrix\Tasks\Task\FavoriteTable entity
	 * @param mixed[] Behaviour flags
	 *
	 *  <li> AFFECT_CHILDREN boolean if true, all child tasks also will be added to favorite. (default false)
	 *
	 * @return \Bitrix\Main\Entity\DeleteResult
	 */
	public static function delete($primary, $behaviour = array('AFFECT_CHILDREN' => false))
	{
		if (!is_array($behaviour))
			$behaviour = array();
		if (!isset($behaviour['AFFECT_CHILDREN']))
			$behaviour['AFFECT_CHILDREN'] = false;

		$primary = static::processPrimary($primary);
		$result = parent::delete($primary);

		if ($result->isSuccess() && $behaviour['AFFECT_CHILDREN'])
		{
			// add also all children...
			$res = TaskTable::getChildrenTasksData($primary['TASK_ID'], array(
				'runtime' => TaskTable::getRuntimeFieldMixins(array('IN_FAVORITE'), array('USER_ID' => $primary['USER_ID'])),
				'select' => array('IN_FAVORITE'),
			));
			while ($item = $res->fetch())
			{
				if ($item['IN_FAVORITE'])
				{
					// our client
					static::delete(array(
						'TASK_ID' => $item['ID']
					), array(
						'AFFECT_CHILDREN' => false
					));
				}
			}
		}

		return $result;
	}

	/**
	 * Removes a task from favorites for all users. This function DOES NOT check permissions.
	 *
	 * @param integer Task id
	 * @param mixed[] Behaviour
	 *
	 *        <li> LOW_LEVEL boolean If set to true, function will ignore all events and do a low-level db query
	 *
	 * @return \Bitrix\Main\Entity\DeleteResult
	 */
	public static function deleteByTaskId($taskId, $behaviour = array('LOW_LEVEL' => false))
	{
		$taskId = Assert::expectIntegerPositive($taskId, '$taskId');

		if (!is_array($behaviour))
			$behaviour = array();
		if (!isset($behaviour['LOW_LEVEL']))
			$behaviour['LOW_LEVEL'] = false;

		if ($behaviour['LOW_LEVEL'])
		{
			HttpApplication::getConnection()->query("delete from " . static::getTableName() . " where TASK_ID = '" . intval($taskId) . "'");

			$result = true;
		}
		else
		{
			$result = array();

			$res = static::getList(array('filter' => array('=TASK_ID' => $taskId)));
			while ($item = $res->fetch())
			{
				$result[] = static::delete(array('TASK_ID' => $item['TASK_ID'], 'USER_ID' => $item['USER_ID']));
			}
		}

		return $result;
	}

	/**
	 * @param mixed[] Primary key for \Bitrix\Tasks\Task\FavoriteTable entity
	 * @return mixed[] Primary key for \Bitrix\Tasks\Task\FavoriteTable entity
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected static function processPrimary($primary)
	{
		if (is_array($primary) && !array_key_exists('USER_ID', $primary))
		{
			if (is_object($GLOBALS['USER']) && method_exists($GLOBALS['USER'], 'GetId'))
			{
				$primary['USER_ID'] = $GLOBALS['USER']->GetId();
			}
			else
			{
				throw new \Bitrix\Main\ArgumentException('Cannot set USER_ID automatically, global USER object does not look good. Specify $primary[USER_ID] manually.');
			}
		}

		return $primary;
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'TASK_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('TASKS_TASK_FAVORITE_ENTITY_TASK_ID_FIELD'),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'title' => Loc::getMessage('TASKS_TASK_FAVORITE_ENTITY_USER_ID_FIELD'),
			),

			// references
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID')
			),
			'TASK' => array(
				'data_type' => 'Bitrix\Tasks\TaskTable',
				'reference' => array('=this.TASK_ID' => 'ref.ID')
			),
		);
	}
}