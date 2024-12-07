<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

\CBitrixComponent::includeComponentClass('bitrix:sign.base');

class SignContactListComponent extends SignBaseComponent
{
	private function prepareResult()
	{
		$this->arResult['MENU_ITEMS'] = $this->arParams['MENU_ITEMS'] ?? [];
	}

	public function executeComponent(): void
	{
		$this->prepareResult();
		$this->includeComponentTemplate();
	}
}
