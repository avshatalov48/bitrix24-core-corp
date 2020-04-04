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

$entityTypeName = isset($arParams['ENTITY_TYPE']) ? $arParams['ENTITY_TYPE'] : '';
if($entityTypeName === '' && isset($_REQUEST['entity_type']))
{
	$entityTypeName = $_REQUEST['entity_type'];
}

$entityTypeName = strtoupper(trim($entityTypeName));
$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
if($entityTypeID === CCrmOwnerType::Undefined)
{
	ShowError(GetMessage('M_CRM_PROGRESS_BAR_LIST_ENTITY_TYPE_UNDEFINED'));
	return;
}

$arResult['ENTITY_TYPE_NAME'] = $entityTypeName;
$arResult['ENTITY_TYPE_ID'] = $entityTypeID;

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
	? 'mobile_crm_progress_bar_list_'.strtolower($entityTypeName)
	: str_replace('#ENTITY_TYPE#', strtolower($entityTypeName), $UID);
$arResult['UID'] = $arParams['UID'] = $UID;

if($entityTypeID === CCrmOwnerType::Lead)
{
	$arResult['TYPE_ID'] = 'STATUS';
}
elseif($entityTypeID === CCrmOwnerType::Deal)
{
	$arResult['TYPE_ID'] = 'DEAL_STAGE';
}
elseif($entityTypeID === CCrmOwnerType::Invoice)
{
	$arResult['TYPE_ID'] = 'INVOICE_STATUS';
}
else
{
	$arResult['TYPE_ID'] = '';
}
$arResult['ITEMS'] = $arResult['TYPE_ID'] !== '' ? CCrmStatus::GetStatus($arResult['TYPE_ID']) : array();

$currentStepID = isset($arParams['CURRENT_STEP_ID']) ? $arParams['CURRENT_STEP_ID'] : '';
if($currentStepID === '')
{
	$itemKeys = array_keys($arResult['ITEMS']);
	if(!empty($itemKeys))
	{
		$currentStepID = $itemKeys[0];
	}
}
$arResult['CURRENT_STEP_ID'] = $currentStepID;

$arResult['DISABLED_STEP_IDS'] = isset($arParams['DISABLED_STEP_IDS']) ? $arParams['DISABLED_STEP_IDS'] : array();

$this->IncludeComponentTemplate();
