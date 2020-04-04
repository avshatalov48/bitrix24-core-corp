<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$mode = isset($arParams['MODE']) ? $arParams['MODE'] : '';
if($mode === '' && isset($_REQUEST['mode']))
{
	$mode = $_REQUEST['mode'];
}
$mode = strtoupper(trim($mode));
$arResult['MODE'] = $arParams['MODE'] = $mode;

$contextID = isset($arParams['CONTEXT_ID']) ? $arParams['CONTEXT_ID'] : '';
if($contextID === '' && isset($_REQUEST['context_id']))
{
	$contextID = $_REQUEST['context_id'];
}
$arResult['CONTEXT_ID'] = $arParams['CONTEXT_ID'] = $contextID;
$arResult['UID'] = $arParams['UID'] = isset($arParams['UID']) && $arParams['UID'] !== '' ? $arParams['UID'] : 'mobile_crm_currency_list';
$arResult['ITEMS'] = array_values(CCrmCurrency::GetAll());

$this->IncludeComponentTemplate();
