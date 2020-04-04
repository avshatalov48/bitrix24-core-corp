<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Manager\Task;

use Bitrix\Tasks\Internals\Task\ParameterTable;

final class Parameter extends \Bitrix\Tasks\Manager
{
	public static function manageSet($userId, $taskId, array $items = array(), array $parameters = array('PUBLIC_MODE' => false, 'MODE' => self::MODE_ADD))
	{
		if($parameters['MODE'] == self::MODE_UPDATE)
		{
			// must try to update existing
			$res = ParameterTable::getList(array('filter' => array('=TASK_ID' => $taskId)));
			$currentItems = array();
			while($item = $res->fetch())
			{
				$currentItems[] = $item;
			}

			$items =        static::indexItemSets($items);
			$currentItems = static::indexItemSets($currentItems);

			list($toAdd, $toUpdate, $toDelete) = static::makeDeltaSets($items, $currentItems);

			foreach($toDelete as $k => $v)
			{
				ParameterTable::delete($v);
			}

			$toAdd = array_flip($toAdd);
			$toUpdate = array_flip($toUpdate);
			foreach($items as $k => $v)
			{
				if(isset($toAdd[$k]))
				{
					ParameterTable::add(array(
						'TASK_ID' => $taskId,
						'CODE' => $v['CODE'],
						'VALUE' => $v['VALUE'],
					));
				}
				elseif(isset($toUpdate[$k]))
				{
					ParameterTable::update($v['ID'], array(
						'TASK_ID' => $taskId,
						'CODE' => $v['CODE'],
						'VALUE' => $v['VALUE'],
					));
				}
			}
		}
		else
		{
			foreach($items as $k => $v)
			{
				try
				{
					ParameterTable::add(
						array(
							'TASK_ID' => $taskId,
							'CODE' => $v['CODE'],
							'VALUE' => $v['VALUE'],
						)
					);
				}
				catch (\Exception $e)
				{
					//do nothing
				}
			}
		}
	}

	public static function mergeData($primary = array(), $secondary = array())
	{
		// $primary - came in request
		// $secondary - currently in the entity

		$ixPrimary = array();
		if(is_array($primary))
		{
			foreach($primary as $v)
			{
				$ixPrimary[$v['CODE']] = $v;
			}
		}

		$ixSecondary = array();
		if(is_array($secondary))
		{
			foreach($secondary as $v)
			{
				$ixSecondary[$v['CODE']] = $v;
			}
		}

		// update secondary from primary

		foreach($ixSecondary as $code => $v)
		{
			if(array_key_exists($code, $ixPrimary))
			{
				if(intval($ixPrimary[$code]['ID']))
				{
					$ixSecondary[$code]['ID'] = intval($ixPrimary[$code]['ID']);
				}

				$ixSecondary[$code]['VALUE'] = $ixPrimary[$code]['VALUE'];
			}
		}

		// add absent
		foreach($ixPrimary as $code => $v)
		{
			if(!array_key_exists($code, $ixSecondary))
			{
				$ixSecondary[$code] = $v;
			}
		}

		return array_values($ixSecondary);
	}
}