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

final class Project extends \Bitrix\Tasks\Manager
{
	public static function getLegacyFieldName()
	{
		return 'GROUP_ID';
	}

	// new data struct to old data struct
	public static function adaptSet(array &$data)
	{
		if(array_key_exists(static::getCode(true), $data))
		{
			$toSave = 0;
			$parent = $data[static::getCode(true)];

			if(is_array($parent))
			{
				$toSave = intval($parent['ID']);
			}

			$data[static::getLegacyFieldName()] = $toSave;
		}
	}

	// old data struct to new data struct
	public static function formatSet(array &$data)
	{
		$from = static::getLegacyFieldName();
		$to = static::getCode(true);

		if(array_key_exists($from, $data))
		{
			$data[$to] = array();
			if(intval($data[$from]))
			{
				$data[$to]['ID'] = intval($data[$from]);
			}
		}
	}

	public static function extendData(array &$data, array $knownTasks = array())
	{
		$code = static::getCode(true);

		if(array_key_exists($code, $data))
		{
			if(isset($knownTasks[$data[$code]['ID']]))
			{
				$data[$code] = \Bitrix\Tasks\Integration\SocialNetwork\Group::extractPublicData($knownTasks[$data[$code]['ID']]);
			}
			else
			{
				$data[$code] = array();
			}
		}
	}
}