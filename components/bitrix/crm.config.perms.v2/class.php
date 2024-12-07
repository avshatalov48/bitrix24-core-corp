<?php

use Bitrix\Crm\Security\Role\Utils\RoleManagerUtils;
use Bitrix\Main\Engine\Contract\Controllerable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}


if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));

	return;
}

class CrmConfigPermsV2 extends CBitrixComponent implements Controllerable
{
	public function executeComponent(): void
	{
		if (!RoleManagerUtils::getInstance()->hasAccessToEditPerms())
		{
			$this->IncludeComponentTemplate('error');

			return;
		}


		$this->IncludeComponentTemplate();
	}

	public function configureActions(): array
	{
		return [];
	}
}