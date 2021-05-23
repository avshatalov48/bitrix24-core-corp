<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

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
$arResult['FILTER_INFO'] = isset($gridContext['FILTER_INFO']) ? $gridContext['FILTER_INFO'] : array();
$this->IncludeComponentTemplate();
