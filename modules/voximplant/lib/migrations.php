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
		$checkCursor = \Bitrix\Voximplant\ConfigTable::getList(array(
			'filter' => array('=PORTAL_MODE' => \CVoxImplantConfig::MODE_LINK),
			'limit' => 1
		));

		if($checkCursor->fetch())
			return '';

		$newConfig = array(
			'PORTAL_MODE' => \CVoxImplantConfig::MODE_LINK,
			'RECORDING' => \CVoxImplantConfig::GetLinkCallRecord()? 'Y': 'N',
			'CRM' => \CVoxImplantConfig::GetLinkCheckCrm()? 'Y': 'N',
			'MELODY_HOLD' => \CVoxImplantConfig::GetMelody('MELODY_HOLD'),
		);

		$callerId = \CVoxImplantPhone::GetCallerId();

		$newConfig['SEARCH_ID'] = \CVoxImplantConfig::LINK_BASE_NUMBER;
		$newConfig['PHONE_VERIFIED'] = ($callerId['VERIFIED'] ? 'Y' : 'N');
		$insertResult = ConfigTable::add($newConfig);
		if(!$insertResult->isSuccess())
			return '\Bitrix\Voximplant\Migrations::migrateTo_16_5_4();';

		$configId = $insertResult->getId();
		$portalAdmins = Bitrix24::getAdmins();
		if(count($portalAdmins) === 0)
		{
			$cursor = \CAllGroup::GetGroupUserEx(1);
			while($admin = $cursor->fetch())
			{
				$portalAdmins[] = $admin["USER_ID"];
			}
		}
		foreach ($portalAdmins as $portalAdmin)
		{
			QueueTable::add(array(
				'CONFIG_ID' => $configId,
				'USER_ID' => $portalAdmin
			));
		}

		return '';
	}
}