<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

namespace Bitrix\Tasks\Util\UserField;

use Bitrix\Main\UserFieldTable;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\UserField;
use Bitrix\Tasks\Integration\Bitrix24;

final class Restriction
{
	public static function canUse($entityCode, $userId = 0, $forceUpdate = false)
	{
		if(static::hadUserFieldsBefore($entityCode, $forceUpdate)) // you can read\write field values, but editing scheme is not guaranteed
		{
			return true;
		}

		// otherwise, bitrix24 will tell us
		return Bitrix24\Task::checkFeatureEnabled('task_user_field');
	}

	public static function canManage($entityCode, $userId = 0)
	{
		// for any entity, ask bitrix24
		return Bitrix24\Task::checkFeatureEnabled('task_user_field');
	}

	public static function canCreateMandatory($entityCode, $userId = 0)
	{
		return static::canManage($entityCode, $userId) && (Bitrix24::isLicensePaid() || Bitrix24::isLicenseShareware());
	}

	private static function checkUserFieldsExists($entityCode)
	{
		$filter = ['=ENTITY_ID' => $entityCode];

		$className = UserField::getControllerClassByEntityCode($entityCode);
		if ($className)
		{
			$filter['!@FIELD_NAME'] = array_keys($className::getSysScheme());
		}

		$item = UserFieldTable::getList([
			'filter' => $filter,
			'limit' => 1,
			'select' => ['ID']
		])->fetch();

		return intval($item['ID']) > 0;
	}

	private static function hadUserFieldsBefore($entityCode, $forceUpdate)
	{
		$optionName = 'have_uf_' . ToLower($entityCode);
		$optionValue = Util::getOption($optionName);

		if ($optionValue === '' || $forceUpdate) // not checked before, check then
		{
			$userFieldsExists = static::checkUserFieldsExists($entityCode);

			if ($userFieldsExists)
			{
				$possibleEntityCodes = [
					'TASKS_TASK' => true,
					'TASKS_TASK_TEMPLATE' => true
				];
				unset($possibleEntityCodes[$entityCode]);

				$anotherOptionName = 'have_uf_' . ToLower(key($possibleEntityCodes));
				$anotherUserFieldExists = static::checkUserFieldsExists(key($possibleEntityCodes));

				Util::setOption($anotherOptionName, ($anotherUserFieldExists? '1' : '0'));
			}

			Util::setOption($optionName, ($userFieldsExists? '1' : '0'));

			return $userFieldsExists;
		}
		else
		{
			return $optionValue == '1';
		}
	}
}