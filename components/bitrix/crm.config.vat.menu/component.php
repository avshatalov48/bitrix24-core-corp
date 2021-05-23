<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$arParams['PATH_TO_VAT_LIST'] = CrmCheckPath('PATH_TO_VAT_LIST', $arParams['PATH_TO_VAT_LIST'], '');
$arParams['PATH_TO_VAT_SHOW'] = CrmCheckPath('PATH_TO_VAT_SHOW', $arParams['PATH_TO_VAT_SHOW'], '?vat_id=#vat_id#&show');
$arParams['PATH_TO_VAT_ADD'] = CrmCheckPath('PATH_TO_VAT_ADD', $arParams['PATH_TO_VAT_ADD'], '?add');
$arParams['PATH_TO_VAT_EDIT'] = CrmCheckPath('PATH_TO_VAT_EDIT', $arParams['PATH_TO_VAT_EDIT'], '?vat_id=#vat_id#&edit');

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

$arResult['BUTTONS'] = array();

$vatID = isset($arParams['VAT_ID']) ? intval($arParams['VAT_ID']) : 0;

$CrmPerms = new CCrmPerms($USER->GetID());

$vatAdd = $vatEdit = $vatDelete = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
$vatShow = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');

$exists = isset($vatID) && is_array(CCrmVat::GetByID($vatID));

if ($arParams['TYPE'] !== 'list')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_VAT_LIST'),
		'TITLE' => GetMessage('CRM_VAT_LIST_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_VAT_LIST'], array()),
		'ICON' => 'btn-list'
	);
}

if ($vatAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_VAT_ADD'),
		'TITLE' => GetMessage('CRM_VAT_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_VAT_ADD'],
			array()
		),
		'ICON' => 'btn-new'
	);
}

if($arParams['TYPE'] == 'list')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_VAT_SETTINGS'),
		'TITLE' => GetMessage('CRM_VAT_SETTINGS_TITLE'),
		'LINK' => "javascript:(new BX.CDialog({
							'content_url':'/bitrix/components/bitrix/crm.config.tax.settings/box.php',
							'width':'498',
							'height':'275',
							'resizable':false })).Show();",
		'ICON' => 'btn-settings'
	);
}

if ($vatEdit && $arParams['TYPE'] == 'show' && $exists)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_VAT_EDIT'),
		'TITLE' => GetMessage('CRM_VAT_EDIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_VAT_EDIT'],
			array('vat_id' => $vatID)
		),
		'ICON' => 'btn-edit'
	);
}

/*
if ($vatShow && $arParams['TYPE'] == 'edit' && $exists)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_VAT_SHOW'),
		'TITLE' => GetMessage('CRM_VAT_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_VAT_SHOW'],
			array('vat_id' => $vatID)
		),
		'ICON' => 'btn-view'
	);
}
*/

if ($vatDelete && ($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $exists)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_VAT_DELETE'),
		'TITLE' => GetMessage('CRM_VAT_DELETE_TITLE'),
		'LINK' => "javascript:vat_delete('".GetMessage('CRM_VAT_DELETE_DLG_TITLE')."', '".GetMessage('CRM_VAT_DELETE_DLG_MESSAGE')."', '".GetMessage('CRM_VAT_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_VAT_EDIT'],
				array('vat_id' => $vatID)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

$this->IncludeComponentTemplate();
?>