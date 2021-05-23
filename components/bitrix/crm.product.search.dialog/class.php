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
