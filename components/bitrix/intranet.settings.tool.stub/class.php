<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class SettingsToolStubComponent extends CBitrixComponent
{
	public function onPrepareComponentParams($arParams): array
	{
		$arParams['LIMIT_CODE'] = is_string($arParams['LIMIT_CODE'] ?? null) ? $arParams['LIMIT_CODE'] : '';
		$arParams['MODULE'] = is_string($arParams['MODULE'] ?? null) ? $arParams['MODULE'] : '';
		$arParams['SOURCE'] = is_string($arParams['SOURCE'] ?? null) ? $arParams['SOURCE'] : '';

		return $arParams;
	}

	public function executeComponent(): void
	{
		$this->arResult = $this->arParams;
		$this->includeComponentTemplate();
	}
}