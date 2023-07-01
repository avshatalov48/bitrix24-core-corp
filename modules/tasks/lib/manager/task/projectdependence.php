<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 *
 * @access private
 *
 * This class should be used in components, inside agent functions, in rest, ajax and more, bringing unification to all places and processes
 */

namespace Bitrix\Tasks\Manager\Task;

use \Bitrix\Main\Loader;

use \Bitrix\Tasks\Util\Error\Collection;
use \Bitrix\Tasks\Task\DependenceTable;
use \Bitrix\Tasks\TaskTable;

final class ProjectDependence extends \Bitrix\Tasks\Manager
{
	const OUTGOING = 	0x01;
	const INGOING = 	0x02;
	const ALL = 		0x03;

	public static function getIsMultiple()
	{
		return true;
	}

	public static function getListByParentEntity($userId, $taskId, array $parameters = array())
	{
		static::checkCanReadTaskThrowException($userId, $taskId);

		$data = array();

		if($parameters['TYPE'] == self::ALL || $parameters['TYPE'] == self::OUTGOING)
		{
			throw new \Bitrix\Main\NotImplementedException('Only INGOING');
		}
		if(!$parameters['DIRECT'])
		{
			throw new \Bitrix\Main\NotImplementedException('Only DIRECT');
		}

		$listParameters = array(
			'select' => array(
				'DEPENDS_ON_ID',
				//'DIRECT', // system field, do not select
				//'MPCITY', // system field, do not select
				'TASK_ID',
				'TYPE',
			),
			'filter' => array('=DIRECT' => '1', '=TASK_ID' => $taskId)
		);

		if ($parameters['DEPENDS_ON_DATA'] ?? null)
		{
			$listParameters['select'][static::SE_PREFIX.'DEPENDS_ON_TITLE'] = 'DEPENDS_ON.TITLE';
		}

		// checking rights on dependent tasks
		$mixins = TaskTable::getRuntimeMixins(
			array(
				array(
					'CODE' => 			'CHECK_RIGHTS',
					'USER_ID' => 		$userId,
					'REF_FIELD' => 		'DEPENDS_ON_ID',
					'APPLY_FILTER'=>['ID'=> $taskId]
				)
			)
		);
		if(!empty($mixins))
		{
			$listParameters['runtime'] = $mixins;
		}

		$res = DependenceTable::getList($listParameters);
		while($item = $res->fetch())
		{
			$seDependsOn = array();
			foreach($item as $fld => $value)
			{
				if($fld == static::SE_PREFIX.'DEPENDS_ON_TITLE')
				{
					$seDependsOn['TITLE'] = $value;
					unset($item[$fld]);
				}
			}

			if(!empty($seDependsOn))
			{
				$seDependsOn['ID'] = $item['DEPENDS_ON_ID'];
				$item[static::SE_PREFIX.'DEPENDS_ON'] = $seDependsOn;
			}

			if (isset($parameters['DROP_PRIMARY']))
			{
				unset($item['TASK_ID']);
			}

			$data[] = $item;
		}

		return array('DATA' => $data, 'CAN' => array());
	}

	public static function manageSet($userId, $taskId, array $items = array(), array $parameters = array('PUBLIC_MODE' => false, 'MODE' => self::MODE_ADD))
	{
		$errors = static::ensureHaveErrorCollection($parameters);
		$result = array(
			'DATA' => array(),
			'CAN' => array(),
			'ERRORS' => $errors
		);

		if(!static::checkSetPassed($items, $parameters['MODE']))
		{
			return $result;
		}

		$task = static::getTask($userId, $taskId);
		$data = array();

		$currentItems = array('DATA' => array());
		if($parameters['MODE'] == static::MODE_UPDATE) // update existing
		{
			$parameters['TYPE'] = self::INGOING;
			$parameters['DIRECT'] = true;
			$currentItems = static::getListByParentEntity($userId, $taskId, $parameters);
		}

		$items = 			static::indexItemSets($items);
		$currentItems = 	static::indexItemSets($currentItems['DATA']);

		list($toAdd, $toUpdate, $toDelete) = static::makeDeltaSets($items, $currentItems);
		if(empty($toAdd) && empty($toUpdate) && empty($toDelete))
		{
			return $result;
		}

		foreach($toDelete as $index)
		{
			$item = $currentItems[$index];
			$task->deleteProjectDependence($item['DEPENDS_ON_ID']);
		}

		$toAdd = array_flip($toAdd);
		$toUpdate = array_flip($toUpdate);

		try
		{
			foreach($items as $index => $item)
			{
				if(isset($toAdd[$index]))
				{
					$task->addProjectDependence($item['DEPENDS_ON_ID'], $item['TYPE']);
				}
				if(isset($toUpdate[$index]))
				{
					$task->updateProjectDependence($item['DEPENDS_ON_ID'], $item['TYPE']);
				}
			}
		}
		catch(\Bitrix\Tasks\DB\Tree\LinkExistsException $e)
		{
			// todo: PROJECTDEPENDENCE: more clever error code here
			$errors->add('PROJECTDEPENDENCE', $e->getMessageFriendly());
		}

		$result['DATA'] = $data;

		return $result;
	}

	public static function extendData(array &$data, array $knownTasks = array())
	{
		$code = static::getCode(true);

		if(array_key_exists($code, $data))
		{
			if(is_array($data[$code]))
			{
				foreach($data[$code] as $i => $link)
				{
					if(intval($link['DEPENDS_ON_ID']) && isset($knownTasks[$link['DEPENDS_ON_ID']]))
					{
						$data[$code][$i][static::SE_PREFIX.'DEPENDS_ON'] = $knownTasks[$link['DEPENDS_ON_ID']];
					}
					else
					{
						unset($data[$code][$i]);
					}
				}
			}
			else
			{
				$data[$code] = array();
			}
		}
	}

	private static function getFieldMap()
	{
		// READ, WRITE, SORT, FILTER, DATE
		return array(
			'TYPE' => 			array(1, 1, 0, 0, 0),
			'DEPENDS_ON_ID' => 	array(1, 1, 0, 0, 0),
		);
	}

	protected static function extractPrimaryIndex(array $data)
	{
		return $data['DEPENDS_ON_ID'];
	}
}