<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class SignConfigPermissionsComponent extends SignBaseComponent
{

	public function exec(): void
	{
		$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage('SIGN_CONFIG_PERMISSIONS'));
		$this->arResult['ACCESS_RIGHTS'] = (new RolePermissionService())->getAccessRights();
		$this->arResult['USER_GROUPS'] = (new RolePermissionService())->getUserGroups();
		
		if ($this->request->getQuery('resetAccessRights') === 'y'
			&& $this->accessController->check(ActionDictionary::ACTION_ACCESS_RIGHTS))
		{
			if (\Bitrix\Main\Loader::includeModule('crm'))
			{
				\Bitrix\Crm\Integration\Sign\Access::installDefaultRoles(true);
				\Bitrix\Sign\Access\Install\AccessInstaller::install(true);
			}
		}
	}
	
	public function getAction(): array
	{
		return [
			\Bitrix\Sign\Access\AccessController::RULE_AND => [
				ActionDictionary::ACTION_ACCESS_RIGHTS,
			]
		];
	}
}