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

global $APPLICATION;

$personTypeID = isset($arParams['PERSON_TYPE_ID']) ? intval($arParams['PERSON_TYPE_ID']) : 0;
if($personTypeID <= 0 && isset($_REQUEST['person_type_id']))
{
	$personTypeID = $_REQUEST['person_type_id'];
}

$arResult['PERSON_TYPE_ID'] = $personTypeID;

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
	? 'mobile_crm_status_list_'.$personTypeID
	: str_replace('#PERSON_TYPE_ID#', $personTypeID, $UID);
$arResult['UID'] = $arParams['UID'] = $UID;

$arResult['ITEMS'] = array();
if($personTypeID > 0)
{
	$listItems = CCrmPaySystem::GetPaySystemsListItems($personTypeID);
	foreach($listItems as $k => &$v)
	{
		$arResult['ITEMS'][] = array('ID' => $k, 'NAME' => $v);
	}
	unset($v);
}
$arResult['RELOAD_URL_TEMPLATE'] = $APPLICATION->GetCurPageParam(
	'AJAX_CALL=Y&FORMAT=json&person_type_id=#person_type_id#',
	array('AJAX_CALL', 'SEARCH', 'FORMAT', 'save', 'apply_filter', 'clear_filter', 'person_type_id')
);

$format = isset($_REQUEST['FORMAT']) ? strtolower($_REQUEST['FORMAT']) : '';
// Only JSON format is supported
if($format !== '' && $format !== 'json')
{
	$format = '';
}
$this->IncludeComponentTemplate($format);
