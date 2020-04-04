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

final class RelatedTask extends \Bitrix\Tasks\Manager
{
	public static function getIsMultiple()
	{
		return true;
	}

	public static function getLegacyFieldName()
	{
		return 'DEPENDS_ON';
	}

	public static function getListByParentEntity($userId, $taskId, array $parameters = array())
	{
		$data = array();
		$task = static::getTask($userId, $taskId);

		$related = $task->getDependsOn();
		if(is_array($related))
		{
			foreach($related as $id)
			{
				$data[] = array('ID' => $id);
			}
		}

		return array('DATA' => $data, 'CAN' => array());
	}

	public static function formatSet(array &$data)
	{
		$from = static::getLegacyFieldName();
		$to = static::getCode(true);

		if(static::getIsMultiple())
		{
			$items = \Bitrix\Tasks\Util\Type::normalizeArray($data[$from]);
			foreach($items as $item)
			{
				$data[$to][] = array('ID' => intval($item));
			}
		}
		else
		{
			$data[$to] = array('ID' => intval($data[$from]));
		}
	}

	// new data struct to old data struct
	public static function adaptSet(array &$data)
	{
		if(array_key_exists(static::getCode(true), $data))
		{
			$related = $data[static::getCode(true)];

			if(is_array($related))
			{
				$related = \Bitrix\Tasks\Util\Type::normalizeArray($related);

				$toSave = array();

				foreach($related as $k => $value)
				{
					if(intval($value['ID']))
					{
						$toSave[] = intval($value['ID']);
					}
				}

				$data[static::getLegacyFieldName()] = array_unique($toSave);
			}
		}
	}

	public static function extendData(array &$data, array $knownTasks = array())
	{
		$code = static::getCode(true);

		if(array_key_exists($code, $data))
		{
			$data[$code] = \Bitrix\Tasks\Util\Type::normalizeArray($data[$code]);

			foreach($data[$code] as $k => $item)
			{
				if(isset($knownTasks[$item['ID']]))
				{
					$data[$code][$k] = $knownTasks[$item['ID']];
				}
				else
				{
					unset($data[$code][$k]);
				}
			}
		}
	}
}