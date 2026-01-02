<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

/**
 * @var $arParams []
 */

$arParams['~CONFIG'] = $arParams['~CONFIG'] ?? [];
$arParams['~CONFIG'] = array_merge(
	$arParams['~CONFIG'],
	[
		'AUTOFOCUS' => false,
	],
);

$gridID = $arParams['GRID_ID'];
$gridContext = CCrmGridContext::Get($gridID);
if(empty($gridContext) && isset($arParams['FILTER_FIELDS']))
{
	$gridContext = CCrmGridContext::Parse($arParams['FILTER_FIELDS']);
	if(isset($arParams['IS_EXTERNAL_FILTER']) && $arParams['IS_EXTERNAL_FILTER'])
	{
		$gridContext['FILTER_INFO']['IS_APPLIED'] = false;
	}
}
$arResult['FILTER_INFO'] = ($gridContext['FILTER_INFO'] ?? []);
$arResult['SHOW_AUTOMATION_VIEW'] = ($arParams['SHOW_AUTOMATION_VIEW'] ?? true);
$arResult['SHOW_REPEAT_SALE_VIEW'] = ($arParams['SHOW_REPEAT_SALE_VIEW'] ?? true);

$this->IncludeComponentTemplate();
