<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CCrmProductSearchDialogComponent extends \CBitrixComponent
{
	public function executeComponent()
	{
		if (!CModule::IncludeModule('crm'))
		{
			ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
			return;
		}

		if (!CCrmSecurityHelper::IsAuthorized())
		{
			ShowError(GetMessage('CRM_PERMISSION_DENIED'));
			return;
		}

		/** @var $permissions CCrmPerms */
		$permissions = CCrmPerms::GetCurrentUserPermissions();
		if (
			!(CCrmPerms::IsAccessEnabled($permissions)
			&& $permissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		)
		{
			ShowError(GetMessage('CRM_PERMISSION_DENIED'));
			return;
		}

		$catalogID = isset($this->arParams['CATALOG_ID']) ? intval($this->arParams['CATALOG_ID']) : 0;
		if ($catalogID <= 0)
			$catalogID = CCrmCatalog::EnsureDefaultExists();
		$this->arResult['CATALOG_ID'] = $catalogID;

		$this->arResult['JS_EVENTS_MANAGER_ID'] = isset($this->arParams['JS_EVENTS_MANAGER_ID'])? $this->arParams['JS_EVENTS_MANAGER_ID'] : '';
		if (!is_string($this->arResult['JS_EVENTS_MANAGER_ID']) || $this->arResult['JS_EVENTS_MANAGER_ID'] === '')
			return;

		$this->includeComponentTemplate();
	}
}
