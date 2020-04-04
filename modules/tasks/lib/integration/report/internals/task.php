<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2012 Bitrix
 *
 * Also @see \CTasksReportHelper
 */
namespace Bitrix\Tasks\Integration\Report\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\User;
use CDatabase;
use CUser;
use Bitrix\Main\DB\SqlExpression;

use Bitrix\Tasks\Util\Assert;

Loc::loadMessages(__FILE__);

class TaskTable extends \Bitrix\Tasks\Internals\TaskTable
{
	/**
	 * @return array
	 */
	public static function getMap()
	{
		/**
		 * @global CDatabase $DB
		 * @global string $DBType
		 */
		global $DB, $DBType;

		// this is required only for getting STATUS_PSEUDO
		// avoid using this field :(
		$userId = (int) \Bitrix\Tasks\Util\User::getId();

		return array_merge(parent::getMap(), array(
			'DESCRIPTION_TR' => array(
				'data_type' => 'string',
				'expression' => array(
					self::getDbTruncTextFunction($DBType, '%s'),
					'DESCRIPTION'
				)
			),
			'STATUS_PSEUDO' => array(
				'data_type' => 'string',
				'expression' => array(
					"CASE
					WHEN
						%s < ".$DB->currentTimeFunction()." AND %s != '4' AND %s != '5' ".($userId ? " AND (%s != '7' OR %s != ".$userId.")" : "")."
					THEN
						'-1'
					ELSE
						%s
					END",
					'DEADLINE', 'STATUS', 'STATUS', 'STATUS', 'RESPONSIBLE_ID', 'STATUS'
				)
			),
			'CREATED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.CREATED_BY' => 'ref.ID'
				)
			),
			'CHANGED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.CHANGED_BY' => 'ref.ID')
			),
			'STATUS_CHANGED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.STATUS_CHANGED_BY' => 'ref.ID')
			),
			'CLOSED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.CLOSED_BY' => 'ref.ID')
			),
			'TIME_SPENT_IN_LOGS' => array( // in seconds
				'data_type' => 'integer',
				'expression' => array(
					'(SELECT  SUM(SECONDS) FROM b_tasks_elapsed_time WHERE TASK_ID = %s)',
					'ID'
				)
			),
			'DURATION' => array( // in minutes (deprecated, use TIME_SPENT_IN_LOGS, which is in seconds, that works correctly in expressions)
				'data_type' => 'integer',
				'expression' => array(
					'ROUND((SELECT  SUM(SECONDS)/60 FROM b_tasks_elapsed_time WHERE TASK_ID = %s),0)',
					'ID'
				)
			),
			// DURATION_PLAN_MINUTES field - only for old user reports, which use it
			'DURATION_PLAN_MINUTES' => array(
				'data_type' => 'integer',
				'expression' => array(
					'ROUND(%s / 60, 0)',
					'DURATION_PLAN' // in seconds
				)
			),
			'DURATION_PLAN_HOURS' => array(
				'data_type' => 'integer',
				'expression' => array(
					'ROUND(%s / 3600, 0)',
					'DURATION_PLAN' // in seconds
				)
			),
			'IS_OVERDUE' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s IS NOT NULL AND (%s < %s OR (%s IS NULL AND %s < '.$DB->currentTimeFunction().')) THEN 1 ELSE 0 END',
					'DEADLINE', 'DEADLINE', 'CLOSED_DATE', 'CLOSED_DATE', 'DEADLINE'
				),
				'values' => array(0, 1)
			),
			'IS_OVERDUE_PRCNT' => array(
				'data_type' => 'integer',
				'expression' => array(
					'SUM(%s)/COUNT(%s)*100',
					'IS_OVERDUE', 'ID'
				)
			),
			'IS_MARKED' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s IN(\'P\', \'N\') THEN 1 ELSE 0 END',
					'MARK'
				),
				'values' => array(0, 1)
			),
			'IS_MARKED_PRCNT' => array(
				'data_type' => 'integer',
				'expression' => array(
					'SUM(%s)/COUNT(%s)*100',
					'IS_MARKED', 'ID'
				)
			),
			'IS_EFFECTIVE' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s = \'P\' THEN 1 ELSE 0 END',
					'MARK'
				),
				'values' => array(0, 1)
			),
			'IS_EFFECTIVE_PRCNT' => array(
				'data_type' => 'integer',
				'expression' => array(
					'SUM(%s)/COUNT(%s)*100',
					'IS_EFFECTIVE', 'ID'
				)
			),
			'IS_RUNNING' => array(
				'data_type' => 'boolean',
				'expression' => array(
					'CASE WHEN %s IN (3,4) THEN 1 ELSE 0 END',
					'STATUS'
				),
				'values' => array(0, 1)
			),
			'DECLINE_REASON' => array(
				'data_type' => 'text',
			),
			'DEADLINE_COUNTED' => array(
				'data_type' => 'integer',
				'required' => true,
			),
		));
	}

	/**
	 * Dont rely on this function, it may become deprecated. This function DOES NOT check rights.
	 * @param integer task id
	 * @param mixed[] parameters
	 * @return \Bitrix\Main\DB\ArrayResult
	 * @access private
	 */
	public static function getChildrenTasksData($taskId, $parameters = array())
	{
		$taskId = Assert::expectIntegerPositive($taskId, '$taskId');

		if(!is_array($parameters))
		{
			$parameters = array();
		}

		// a shame, but no tree struct here, so have to make "recursive" calls...
		$queue = array($taskId);
		$meetings = array();
		$result = array();

		$i = -1;
		while(!empty($queue))
		{
			$i++;

			$nextId = array_shift($queue);
			if(isset($meetings[$nextId]))
			{
				throw new \Bitrix\Tasks\Exception('Task subtree seems to be loopy');
			}
			$meetings[$nextId] = true;

			$nextParams = array_merge_recursive(\Bitrix\Tasks\Internals\Runtime::cloneFields($parameters), array(
				'filter' => array(
					'=PARENT_ID' => $nextId
				),
				'select' => array(
					'ID'
				)
			));

			$res = static::getList($nextParams);
			while($item = $res->fetch())
			{
				if(intval($item['ID']))
				{
					array_unshift($queue, $item['ID']);

					$result[$item['ID']] = $item;
				}
			}
		}

		return new \Bitrix\Main\DB\ArrayResult($result);
	}

	/**
	 * Allows to add various runtime mixins to static::getList(), which depend on some external data and, therefore, cannot be placed at static::getMap()
	 *
	 * @param mixed[] $mixins Mixins to add
	 *
	 * 		<li> IN_FAVORITE mixed[] - check if selected tasks are in favorite (adds "IN_FAVORITE" column)
	 * 		<li> CHECK_RIGHTS mixed[] - check if we can read selected tasks
	 * 		<li> LEGACY_FILTER mixed[] - join a legacy filter (like CTasks::GetList() do) for the result
	 *
	 * @return mixed[]
	 *
	 * @deprecated
	 */
	public static function getRuntimeMixins(array $mixins = array())
	{
		$result = array();
		foreach($mixins as $alias => $mixinData)
		{
			$mixinData['NAME'] = !is_numeric($alias) ? $alias : $mixinData['CODE'];

			if(!array_key_exists('USER_ID', $mixinData))
			{
				// get current USER_ID

				$mixinData['USER_ID'] = 0;
				if(User::getId())
				{
					$mixinData['USER_ID'] = (int) User::getId();
				}
			}

			switch($mixinData['CODE'])
			{
				case 'IN_FAVORITE':
					$rt = \Bitrix\Tasks\Internals\RunTime\Task\Favorite::getFlag($mixinData);
					if(is_array($rt['runtime']) && !empty($rt['runtime']))
					{
						$result = array_shift($rt);
					}
					break;

				case 'CHECK_RIGHTS':
					$mixin = static::getRuntimeFieldMixinsCheckRights($mixinData);
					if($mixin)
					{
						$result[] = $mixin;
					}
					break;

				case 'LEGACY_FILTER':
					$mixin = static::getRuntimeFieldMixinsLegacyFilter($mixinData);
					if($mixin)
					{
						$result[] = $mixin;
					}
					break;

				default:
					throw new \Bitrix\Main\ArgumentException('Unknown mixin: '.$mixinData['CODE']);
					break;
			}
		}

		return $result;
	}

	/**
	 * @param $parameters
	 * @return Entity\ReferenceField
	 * @throws \Bitrix\Main\ArgumentException
	 * @deprecated
	 */
	protected static function getRuntimeFieldMixinsFavorite($parameters)
	{
		$parameters['USER_ID'] = Assert::expectIntegerPositive($parameters['USER_ID'], '$parameters[USER_ID]');
		$rf = $parameters['REF_FIELD'];

		return new Entity\ReferenceField(
			$parameters['NAME'],
			'Bitrix\Tasks\Task\Favorite',
			array(
				'=this.'.((string) $rf != '' ? $rf : 'ID') => 'ref.TASK_ID',
				'=ref.USER_ID' => array('?', $parameters['USER_ID'])
			)
		);
	}

	/**
	 * @param $parameters
	 * @return Entity\ExpressionField
	 * @deprecated
	 */
	protected static function getRuntimeFieldMixinsInFavorite($parameters)
	{
		return new Entity\ExpressionField(
			$parameters['NAME'],
			'CASE WHEN %s IS NOT NULL THEN 1 ELSE 0 END',
			array('FAVORITE.TASK_ID')
		);
	}

	/**
	 * @param $parameters
	 * @return array|mixed
	 * @deprecated
	 */
	protected static function getRuntimeFieldMixinsLegacyFilter($parameters)
	{
		$result = \Bitrix\Tasks\Internals\RunTime\Task::getLegacyFilter($parameters);
		if(is_array($result['runtime']) && !empty($result['runtime']))
		{
			return array_pop($result['runtime']);
		}

		return false;
	}

	/**
	 * @param $parameters
	 * @return bool|mixed
	 * @deprecated
	 */
	protected static function getRuntimeFieldMixinsCheckRights($parameters)
	{
		$result = \Bitrix\Tasks\Internals\RunTime\Task::getAccessCheck($parameters);
		if(!is_array($result) && is_array($result['runtime']))
		{
			return array_pop($result['runtime']);
		}

		return false;
	}

	/**
	 * @param string $dbtype Database type.
	 * @param string $param SQL field text.
	 * @return string
	 */
	private static function getDbTruncTextFunction($dbtype, $param)
	{
		return \Bitrix\Tasks\Internals\DataBase\Helper::getTruncateTextFunction($param);
	}

	/**
	 * @param mixed[] $mixinCodes Mixin codes to add
	 * @param mixed[] $param External parameters
	 * 		<li> USER_ID integer Current user id. If not set, takes the current user`s id
	 * 		<li> REF_FIELD string A reference field (or a chain of reference fields) that our target entity uses to join b_tasks table)
	 * @deprecated
	 */
	public static function getRuntimeFieldMixins($mixinCodes, $parameters = array())
	{
		if(!is_array($mixinCodes))
		{
			$mixinCodes = array();
		}

		if(!is_array($parameters))
		{
			$parameters = array();
		}

		$mixins = array();
		foreach($mixinCodes as $code)
		{
			$p = $parameters;
			$p['CODE'] = $code;
			$mixins[] = $p;
		}

		return static::getRuntimeMixins($mixins);
	}
}
