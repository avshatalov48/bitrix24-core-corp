<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('bizproc') || !CBPRuntime::isFeatureEnabled())
{
	ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
	return;
}

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arTypes = Array(
	'CRM_LEAD' => array(
		'ID' => 'CRM_LEAD',
		'NAME' => GetMessage('CRM_BP_LEAD'),
		'DOCUMENT' => 'CCrmDocumentLead',
		'TYPE' => 'LEAD'
	),
	'CRM_CONTACT' => array(
		'ID' => 'CRM_CONTACT',
		'NAME' => GetMessage('CRM_BP_CONTACT'),
		'DOCUMENT' => 'CCrmDocumentContact',
		'TYPE' => 'CONTACT'
	),
	'CRM_COMPANY' => array(
		'ID' => 'CRM_COMPANY',
		'NAME' => GetMessage('CRM_BP_COMPANY'),
		'DOCUMENT' => 'CCrmDocumentCompany',
		'TYPE' => 'COMPANY'
	),
	'CRM_DEAL' => array(
		'ID' => 'CRM_DEAL',
		'NAME' => GetMessage('CRM_BP_DEAL'),
		'DOCUMENT' => 'CCrmDocumentDeal',
		'TYPE' => 'DEAL'
	)
);

$arResult['ENTITY_ID'] = isset($_REQUEST['entity_id']) ? $_REQUEST['entity_id']: $arParams['BP_ENTITY_ID'];
$arResult['ENTITY_NAME'] = $arTypes[$arResult['ENTITY_ID']]['NAME'];
$arResult['DOCUMENT_TYPE'] = $arTypes[$arResult['ENTITY_ID']]['TYPE'];
$arResult['ENTITY_TYPE'] = $arTypes[$arResult['ENTITY_ID']]['DOCUMENT'];

$arResult['~ENTITY_LIST_URL'] = $arParams['~ENTITY_LIST_URL'];
$arResult['ENTITY_LIST_URL'] = htmlspecialcharsbx($arResult['~ENTITY_LIST_URL']);

$arResult['~BP_LIST_URL'] = str_replace('#entity_id#', $arResult['ENTITY_ID'], $arParams['~BP_LIST_URL']);
$arResult['BP_LIST_URL'] = htmlspecialcharsbx($arResult['~BP_LIST_URL']);

$arResult['~BP_EDIT_URL'] = str_replace(array('#entity_id#'), array($arResult['ENTITY_ID']), $arParams['~BP_EDIT_URL']);
$arResult['BP_EDIT_URL'] = htmlspecialcharsbx($arResult['~BP_EDIT_URL']);

$this->IncludeComponentTemplate();

$APPLICATION->SetTitle(GetMessage('CRM_BP_LIST_TITLE_EDIT', array('#NAME#' => $arResult['ENTITY_NAME'])));
$APPLICATION->AddChainItem(GetMessage('CRM_BP_ENTITY_LIST'), $arResult['~ENTITY_LIST_URL']);
$APPLICATION->AddChainItem($arResult['ENTITY_NAME'], $arResult['~BP_LIST_URL']);

?>