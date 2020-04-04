<?php

namespace Bitrix\Voximplant;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Integration\Bitrix24;
use Bitrix\Voximplant\Model\QueueTable;
use Bitrix\Voximplant\Security;

Loc::loadMessages(__FILE__);

class Migrations
{
	/**
	 * Creates default access roles.
	 * @return string
	 */
	public static function migrateTo_16_5_1()
	{
		if(!Loader::includeModule('intranet'))
			return '';

		if(!class_exists('\Bitrix\Voximplant\Model\RoleTable')
			|| !class_exists('\Bitrix\Voximplant\Model\RoleAccessTable')
			|| !class_exists('\Bitrix\Voximplant\Security\RoleManager')
		)
		{
			return '\Bitrix\Voximplant\Migrations::migrateTo_16_5_1();';
		}

		Security\Helper::createDefaultRoles();

		return '';
	}

	/**
	 * Creates default config for
	 * Return string Returns agent name or empty string;
	 */
	public static function migrateTo_16_5_4()
	{
		// This method used to create default config for linked phone number (or LINK_BASE_NUMBER), but it is not needed anymore
		return '';
	}
}