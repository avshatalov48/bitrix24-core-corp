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

final class Enumeration extends \Bitrix\Tasks\Util\UserField\Type
{
	private static $enums = array();

	public static function cloneValue($value, array &$entityData, array $fromField, array $toField, $userId = false)
	{
		$newValue = '';

		if((string) $value != '')
		{
			$newValue = $value;

			$fromEnum = static::getEnum($fromField['ID']);
			$toEnum = static::getEnum($toField['ID']);

			// create mapping from $fromEnum to $toEnum
			$eMap = array();
			foreach($fromEnum as $xml => $eData)
			{
				if(is_array($toEnum[$xml]))
				{
					$eMap[$eData['ID']] = $toEnum[$xml]['ID'];
				}
			}

			// now map values
			if($fromField['MULTIPLE'] == 'Y')
			{
				if(is_array($newValue))
				{
					$mappedValue = array();
					foreach($newValue as $eValue)
					{
						$mappedValue[] = $eMap[$eValue];
					}
					$newValue = $mappedValue;
				}
			}
			else
			{
				$newValue = $eMap[$newValue];
			}
		}

		return static::translateValueByMultiple($newValue, $fromField, $toField);
	}

	private static function getEnum($id)
	{
		if(static::$enums[$id] == null)
		{
			$enum = new \CUserFieldEnum();
			$res = $enum->GetList(array(), array(
				"USER_FIELD_ID" => $id,
			));

			static::$enums[$id] = array();
			$i = 0;
			while($item = $res->fetch())
			{
				if((string) $item['XML_ID'] == '')
				{
					$item['XML_ID'] = 'id_'.$i++;
				}

				static::$enums[$id][ToLower(trim($item['XML_ID']))] = $item;
			}
		}

		return static::$enums[$id];
	}
}