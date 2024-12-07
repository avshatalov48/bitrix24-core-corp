<?php

use Bitrix\HumanResources\Internals\HumanResourcesBaseComponent;
use Bitrix\Main\Localization\Loc;
use Bitrix\HumanResources\Service\Container;


if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Bitrix\Main\Loader::includeModule('humanresources');

class HumanResourcesConfigPermissionsComponent extends HumanResourcesBaseComponent
{
	public function exec(): void
	{
		$this->setTemplateTitle(Loc::getMessage('HUMAN_RESOURCES_CONFIG_PERMISSIONS_TITLE'));

		$rolePermission = Container::getAccessRolePermissionService();
		$this->arResult['ACCESS_RIGHTS'] = $rolePermission->getAccessRights();
		$this->arResult['USER_GROUPS'] = $rolePermission->getUserGroups();
	}
}