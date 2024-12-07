<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

namespace Bitrix\Tasks\Util\UserField;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Loader;
use Bitrix\Main\UserFieldTable;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\UserField;

final class Restriction
{
	public static function canUse($entityCode, $userId = 0, $forceUpdate = false)
	{
		// you can read\write field values, but editing scheme is not guaranteed
		if (static::hadUserFieldsBefore($entityCode, $forceUpdate))
		{
			return true;
		}

		return Bitrix24\Task::checkFeatureEnabled(Bitrix24\FeatureDictionary::TASK_CUSTOM_FIELDS);
	}

	public static function canManage($entityCode, $userId = 0)
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return true;
		}
		
		return Feature::isFeatureEnabled(Bitrix24\FeatureDictionary::TASK_CUSTOM_FIELDS);
	}

	public static function canCreateMandatory($entityCode, $userId = 0)
	{
		return static::canManage($entityCode, $userId) && (Bitrix24::isLicensePaid() || Bitrix24::isLicenseShareware());
	}

	private static function checkUserFieldsExists($entityCode)
	{
		$filter = ['=ENTITY_ID' => $entityCode];
		$fieldsToExclude = ['UF_MAIL_MESSAGE'];

		$className = UserField::getControllerClassByEntityCode($entityCode);
		if ($className)
		{
			$fieldsToExclude = array_merge($fieldsToExclude, array_keys($className::getSysScheme()));
		}
		$filter['!@FIELD_NAME'] = $fieldsToExclude;

		$item = UserFieldTable::getList([
			'filter' => $filter,
			'limit' => 1,
			'select' => ['ID']
		])->fetch();

		return isset ($item['ID']) && (int)$item['ID'] > 0;
	}

	private static function hadUserFieldsBefore($entityCode, $forceUpdate)
	{
		$optionName = 'have_uf_' . mb_strtolower($entityCode);
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

				$anotherOptionName = 'have_uf_' . mb_strtolower(key($possibleEntityCodes));
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