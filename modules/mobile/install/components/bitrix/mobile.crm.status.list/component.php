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

$typeID = isset($arParams['TYPE_ID']) ? $arParams['TYPE_ID'] : '';
if($typeID === '' && isset($_REQUEST['type_id']))
{
	$typeID = $_REQUEST['type_id'];
}

$typeID = strtoupper(trim($typeID));

if($typeID === '')
{
	ShowError(GetMessage('M_CRM_STATUS_LIST_TYPE_ID_UNDEFINED'));
	return;
}

$arResult['TYPE_ID'] = $typeID;

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

$UID = isset($arParams['UID']) ? $arParams['UID'] : '';
$UID = $UID === ''
	? 'mobile_crm_status_list_'.strtolower($typeID)
	: str_replace('#TYPE_ID#', strtolower($typeID), $UID);
$arResult['UID'] = $arParams['UID'] = $UID;

$arResult['ITEMS'] = CCrmStatus::GetStatus($typeID);

$this->IncludeComponentTemplate();
