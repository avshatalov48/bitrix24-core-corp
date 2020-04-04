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

final class Tag extends \Bitrix\Tasks\Manager
{
	public static function getIsMultiple()
	{
		return true;
	}

	public static function getLegacyFieldName()
	{
		return 'TAGS';
	}

	public static function getList($userId, $taskId, array $parameters = array())
	{
		$task = static::getTask($userId, $taskId);
		$tags = $task->getTags();

		$data = array();
		if(is_array($tags))
		{
			foreach($tags as $tag)
			{
				$data[] = array('NAME' => $tag);
			}
		}
		return array('DATA' => $data, 'CAN' => array());
	}

	public static function formatSet(array &$data)
	{
		$from = static::getLegacyFieldName();
		$to = static::getCode(true);

		if(array_key_exists($to, $data))
		{
			return;
		}

		if(static::getIsMultiple())
		{
			$items = \Bitrix\Tasks\Util\Type::normalizeArray($data[$from]);
			foreach($items as $item)
			{
				$data[$to][] = array('NAME' => $item);
			}
		}
		else
		{
			$data[$to] = array('NAME' => $data[$from]);
		}
	}

	public static function adaptSet(array &$data)
	{
		if(array_key_exists(static::getCode(true), $data))
		{
			$tags = $data[static::getCode(true)];

			if(is_array($tags))
			{
				$tags = \Bitrix\Tasks\Util\Type::normalizeArray($tags);

				$toSave = array();

				foreach($tags as $k => $value)
				{
					if((string) $value['NAME'] != '')
					{
						$toSave[] = $value['NAME'];
					}
				}

				$data['TAGS'] = $toSave;
			}
		}
	}
}