<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2020 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class UserUtil
{
	public static function getAvailableFields()
	{
		if (ModuleManager::isModuleInstalled("bitrix24"))
		{
			return [
				'NAME',
				'LAST_NAME',
				'EMAIL',
				'WORK_POSITION',
				'UF_DEPARTMENT',
				'SECOND_NAME',
				'PERSONAL_BIRTHDAY',
				'PERSONAL_GENDER',
				'PERSONAL_WWW',
				'PERSONAL_MOBILE',
				'WORK_PHONE',
				'UF_PHONE_INNER',
				'PERSONAL_WWW',
				'PERSONAL_CITY',
				'UF_EMPLOYMENT_DATE',
				'UF_SKYPE',
				'UF_SKYPE_LINK',
				'UF_ZOOM',
				'TIME_ZONE',
				"DATE_REGISTER",
				"LAST_ACTIVITY_DATE",
			];
		}

		return [];
	}
}
