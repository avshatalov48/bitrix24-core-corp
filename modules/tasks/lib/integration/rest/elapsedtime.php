<?php
/**
 * Class implements all further interactions with "rest" module considering "elapsed item" entity.
 * This class is for REST-only purposes. When working with API directly, use \Bitrix\Tasks\ElapsedTimeTable or CTaskElapsedItem instead.
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Rest;

use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\TaskTable;
use Bitrix\Tasks\Util\Assert;
use Bitrix\Main\ORM\Query\Result as QueryResult;

Loc::loadMessages(__FILE__);

/**
 * Class ElapsedTimeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ElapsedTime_Query query()
 * @method static EO_ElapsedTime_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ElapsedTime_Result getById($id)
 * @method static EO_ElapsedTime_Result getList(array $parameters = [])
 * @method static EO_ElapsedTime_Entity getEntity()
 * @method static \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime createObject($setDefaultValues = true)
 * @method static \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection createCollection()
 * @method static \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime wakeUpObject($row)
 * @method static \Bitrix\Tasks\Integration\Rest\EO_ElapsedTime_Collection wakeUpCollection($rows)
 */
final class ElapsedTimeTable extends \Bitrix\Tasks\ElapsedTimeTable
{
	/**
	 * Prepares "parameters" argument for self::getList() according to the purposes of a REST interface
	 *
	 * @param mixed[] Initial parameters
	 * @param mixed[] Behaviour flags
	 *
	 * 	<li> USER_ID integer Current user id, mandatory.
	 * 	<li> ROW_LIMIT integer Row limit on each rest query, optional
	 *
	 * @return QueryResult
	 */
	public static function getList(array $parameters = array(), $behaviour = array())
	{
		if(!is_array($behaviour))
		{
			$behaviour = array();
		}
		$behaviour['USER_ID'] = Assert::expectIntegerPositive($behaviour['USER_ID'], '$behaviour[USER_ID]');
		if(!isset($behaviour['ROW_LIMIT']))
		{
			$behaviour['ROW_LIMIT'] = false;
		}

		$runtime = array();

		if(is_array($parameters['order']) && !empty($parameters['order']))
		{
			static::parseOutSimpleAggregations(array_keys($parameters['order']), $runtime);
		}

		if(is_array($parameters['select']) && !empty($parameters['select']))
		{
			static::parseOutSimpleAggregations($parameters['select'], $runtime);
		}

		$rights = TaskTable::getRuntimeFieldMixins(array('CHECK_RIGHTS'), array('USER_ID' => $behaviour['USER_ID'], 'REF_FIELD' => 'TASK_ID'));
		if(is_array($rights) && !empty($rights))
		{
			foreach($rights as $right)
			{
				$parameters['runtime'][] = $right;
			}
		}

		$behaviour['ROW_LIMIT'] = intval($behaviour['ROW_LIMIT']);
		if($behaviour['ROW_LIMIT'] && (!isset($parameters['limit']) || ((int) $parameters['limit'] > $behaviour['ROW_LIMIT'])))
		{
			$parameters['limit'] = $behaviour['ROW_LIMIT'];
		}

		return parent::getList($parameters);
	}

	protected static function parseOutSimpleAggregations(array $list, array &$runtime)
	{
		$legalFuncs = array('MAX', 'MIN', 'SUM', 'COUNT', 'AVG');

		foreach($list as $key)
		{
			$key = (string) trim(mb_strtoupper($key));
			if($key != '')
			{
				$found = array();
				if(preg_match('#^([A-Z0-9_]+)_((MAX|MIN|SUM|COUNT|AVG){1})$#', $key, $found) !== false)
				{
					$field = (string)($found[1] ?? null);
					$func = (string)($found[3] ?? null);

					if($field != '' && $func != '')
					{
						$runtime[$key] = array(
							'data_type' => 'integer',
							'expression' => array(
								$func.'(%s)',
								$field
							)
						);
					}
				}
			}
		}
	}
}