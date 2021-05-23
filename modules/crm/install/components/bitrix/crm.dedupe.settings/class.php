<?php

use Bitrix\Crm\Integrity\DuplicateIndexType;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

CBitrixComponent::includeComponentClass("bitrix:crm.dedupe.wizard");

if(!Bitrix\Main\Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

class CCrmDedupeSettingsComponent extends CCrmDedupeWizardComponent
{
	public function executeComponent()
	{
		$this->initUser();
		$this->initEntityType();
		$this->initGuid();
		$this->initTypesAndScopes();

		if (count($this->arResult['SCOPE_LIST_ITEMS']) > 1)
		{
			// remove default scope if alternatives exist
			unset($this->arResult['SCOPE_LIST_ITEMS'][DuplicateIndexType::DEFAULT_SCOPE]);

			$selectedScope = $this->arResult['CONFIG']['scope'];
			if ($selectedScope === DuplicateIndexType::DEFAULT_SCOPE)
			{
				$selectedScope = array_keys($this->arResult['SCOPE_LIST_ITEMS'])[0];
				$this->arResult['CONFIG']['scope'] = $selectedScope;
				$this->arResult['CURRENT_SCOPE'] = $selectedScope;
			}
		}
		else
		{
			if (!isset($this->arResult['SCOPE_LIST_ITEMS'][$this->arResult['CURRENT_SCOPE']]))
			{
				$selectedScope = array_keys($this->arResult['SCOPE_LIST_ITEMS'])[0];
				$this->arResult['CONFIG']['scope'] = $selectedScope;
				$this->arResult['CURRENT_SCOPE'] = $selectedScope;
			}
		}

		$this->includeComponentTemplate();
	}
}