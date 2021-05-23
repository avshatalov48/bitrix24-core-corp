<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CCrmCurrency::EnsureReady())
{
	return;
}

global $USER, $APPLICATION;

$arParams['PATH_TO_CURRENCY_LIST'] = CrmCheckPath('PATH_TO_CURRENCY_LIST', $arParams['PATH_TO_CURRENCY_LIST'], '');
$arParams['PATH_TO_CURRENCY_SHOW'] = CrmCheckPath('PATH_TO_CURRENCY_SHOW', $arParams['PATH_TO_CURRENCY_SHOW'], '?currency_id=#currency_id#&show');
$arParams['PATH_TO_CURRENCY_ADD'] = CrmCheckPath('PATH_TO_CURRENCY_ADD', $arParams['PATH_TO_CURRENCY_ADD'], '?add');
$arParams['PATH_TO_CURRENCY_EDIT'] = CrmCheckPath('PATH_TO_CURRENCY_EDIT', $arParams['PATH_TO_CURRENCY_EDIT'], '?currency_id=#currency_id#&edit');

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

$arResult['BUTTONS'] = array();

$currencyID = isset($arParams['CURRENCY_ID']) ? strval($arParams['CURRENCY_ID']) : '';

$CrmPerms = new CCrmPerms($USER->GetID());

$currencyAdd = $currencyEdit = $currencyDelete = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
$currencyShow = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');

$exists = isset($currencyID[0]) && is_array(CCrmCurrency::GetByID($currencyID));

if ($arParams['TYPE'] !== 'list')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CURRENCY_LIST'),
		'TITLE' => GetMessage('CRM_CURRENCY_LIST_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CURRENCY_LIST'], array()),
		'ICON' => 'btn-list'
	);
}
if ($currencyAdd)
{
	if ($arParams['TYPE'] == 'list')
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CURRENCY_ADD'),
			'TITLE' => GetMessage('CRM_CURRENCY_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_CURRENCY_ADD'],
				array()
			),
			'ICON' => 'btn-new'
		);
	}
}
if ($currencyEdit && $arParams['TYPE'] == 'show' && $exists)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CURRENCY_EDIT'),
		'TITLE' => GetMessage('CRM_CURRENCY_EDIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_CURRENCY_EDIT'],
			array('currency_id' => $currencyID)
		),
		'ICON' => 'btn-edit'
	);
}
if ($currencyShow && $arParams['TYPE'] == 'edit' && $exists)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CURRENCY_SHOW'),
		'TITLE' => GetMessage('CRM_CURRENCY_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_CURRENCY_SHOW'],
			array('currency_id' => $currencyID)
		),
		'ICON' => 'btn-view'
	);
}
if ($currencyDelete && ($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $exists)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CURRENCY_DELETE'),
		'TITLE' => GetMessage('CRM_CURRENCY_DELETE_TITLE'),
		'LINK' => "javascript:currency_delete('".GetMessage('CRM_CURRENCY_DELETE_DLG_TITLE')."', '".GetMessage('CRM_CURRENCY_DELETE_DLG_MESSAGE')."', '".GetMessage('CRM_CURRENCY_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CURRENCY_EDIT'],
				array('currency_id' => $currencyID)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

$this->IncludeComponentTemplate();
?>