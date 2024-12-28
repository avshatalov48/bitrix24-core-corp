<?php

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\HumanResources\Access\StructureAccessController;
use Bitrix\HumanResources\Access\StructureActionDictionary;
use Bitrix\HumanResources\Internals\HumanResourcesBaseComponent;
use Bitrix\HumanResources\Service\Container;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!Bitrix\Main\Loader::includeModule('humanresources'))
{
	return;
}

class HumanResourcesConfigPermissionsComponent extends HumanResourcesBaseComponent
{
	public function exec(): void
	{
		if (
			!StructureAccessController::can(
				CurrentUser::get()->getId(),
				StructureActionDictionary::ACTION_USERS_ACCESS_EDIT
			)
		)
		{
			$this->setTemplatePage('access_denied');

			return;
		}

		$this->setTemplateTitle(Loc::getMessage('HUMAN_RESOURCES_CONFIG_PERMISSIONS_TITLE'));

		$rolePermission = Container::getAccessRolePermissionService();
		$this->arResult['ACCESS_RIGHTS'] = $rolePermission->getAccessRights();
		$this->arResult['USER_GROUPS'] = $rolePermission->getUserGroups();

		if (!\Bitrix\HumanResources\Config\Storage::canUsePermissionConfig())
		{
			$this->arResult['CANT_USE'] = true;
		}
	}
}