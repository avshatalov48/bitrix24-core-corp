<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_SALE_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$arParams['PATH_TO_TAX_LIST'] = CrmCheckPath('PATH_TO_TAX_LIST', $arParams['PATH_TO_TAX_LIST'], '');
$arParams['PATH_TO_TAX_SHOW'] = CrmCheckPath('PATH_TO_TAX_SHOW', $arParams['PATH_TO_TAX_SHOW'], '?tax_id=#tax_id#&show');
$arParams['PATH_TO_TAX_ADD'] = CrmCheckPath('PATH_TO_TAX_ADD', $arParams['PATH_TO_TAX_ADD'], '?add');
$arParams['PATH_TO_TAX_EDIT'] = CrmCheckPath('PATH_TO_TAX_EDIT', $arParams['PATH_TO_TAX_EDIT'], '?tax_id=#tax_id#&edit');

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

$arResult['BUTTONS'] = array();

$taxID = isset($arParams['TAX_ID']) ? strval($arParams['TAX_ID']) : '';

$CrmPerms = new CCrmPerms($USER->GetID());

$taxAdd = $taxEdit = $taxDelete = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
$taxShow = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');

$exists = intval($taxID > 0) && is_array(CCrmTax::GetByID($taxID));

if ($arParams['TYPE'] !== 'list')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_TAX_LIST'),
		'TITLE' => GetMessage('CRM_TAX_LIST_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_TAX_LIST'], array()),
		'ICON' => 'btn-list'
	);
}

if ($taxAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_TAX_ADD'),
		'TITLE' => GetMessage('CRM_TAX_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_TAX_ADD'],
			array()
		),
		'ICON' => 'btn-new'
	);
}

if($arParams['TYPE'] == 'list')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_TAX_SETTINGS'),
		'TITLE' => GetMessage('CRM_TAX_SETTINGS_TITLE'),
		'LINK' => "javascript:(new BX.CDialog({
							'content_url':'/bitrix/components/bitrix/crm.config.tax.settings/box.php',
							'width':'498',
							'height':'275',
							'resizable':false })).Show();",
		'ICON' => 'btn-settings'
	);
}

if ($taxEdit && $arParams['TYPE'] == 'show' && $exists)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_TAX_EDIT'),
		'TITLE' => GetMessage('CRM_TAX_EDIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_TAX_EDIT'],
			array('tax_id' => $taxID)
		),
		'ICON' => 'btn-edit'
	);
}
/*
if ($taxShow && $arParams['TYPE'] == 'edit' && $exists)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_TAX_SHOW'),
		'TITLE' => GetMessage('CRM_TAX_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_TAX_SHOW'],
			array('tax_id' => $taxID)
		),
		'ICON' => 'btn-view'
	);
}
*/
if ($taxDelete && ($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $exists)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_TAX_DELETE'),
		'TITLE' => GetMessage('CRM_TAX_DELETE_TITLE'),
		'LINK' => "javascript:tax_delete('".GetMessage('CRM_TAX_DELETE_DLG_TITLE')."', '".GetMessage('CRM_TAX_DELETE_DLG_MESSAGE')."', '".GetMessage('CRM_TAX_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_TAX_EDIT'],
				array('tax_id' => $taxID)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

$this->IncludeComponentTemplate();
?>