<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $APPLICATION;

if (!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$personTypeID = isset($arParams['PERSON_TYPE_ID']) ? intval($arParams['PERSON_TYPE_ID']) : 0;
if($personTypeID <= 0  && isset($_REQUEST['person_type_id']))
{
	$personTypeID = intval($_REQUEST['person_type_id']);
}
$arResult['PERSON_TYPE_ID'] = $arParams['PERSON_TYPE_ID'] = $personTypeID;

$contextID = isset($arParams['CONTEXT_ID']) ? $arParams['CONTEXT_ID'] : '';
if($contextID === '' && isset($_REQUEST['context_id']))
{
	$contextID = $_REQUEST['context_id'];
}
$arResult['CONTEXT_ID'] = $arParams['CONTEXT_ID'] = $contextID;

$UID = isset($arParams['UID']) ? $arParams['UID'] : '';
if($UID === '')
{
	$UID = 'mobile_crm_client_requisite_editor';
}
$arResult['UID'] = $arParams['UID'] = $UID;

$this->IncludeComponentTemplate();
