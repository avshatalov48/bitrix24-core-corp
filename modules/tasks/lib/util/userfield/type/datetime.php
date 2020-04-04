<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Util\UserField\Type;

class DateTime extends \Bitrix\Tasks\Util\UserField\Type
{
	protected static function getFormatName()
	{
		return 'FULL';
	}

	protected static function getFormatValue()
	{
		return 'YYYY-MM-DD HH:MI:SS';
	}

	public static function getDefaultValueSingle(array $field)
	{
		$default = $field['SETTINGS']['DEFAULT_VALUE'];
		if(!is_array($default) || !array_key_exists('TYPE', $default))
		{
			return null;
		}

		$type = $default['TYPE'];
		if($type == 'NOW')
		{
			return \ConvertTimeStamp(time(), static::getFormatName());
		}
		elseif($type == 'NONE')
		{
			return '';
		}
		else
		{
			return \CDatabase::formatDate($default["VALUE"], static::getFormatValue(), \CLang::GetDateFormat(static::getFormatName()));
		}
	}
}