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

$arParams['PATH_TO_PS_LIST'] = CrmCheckPath('PATH_TO_PS_LIST', $arParams['PATH_TO_PS_LIST'], '');
$arParams['PATH_TO_PS_ADD'] = CrmCheckPath('PATH_TO_PS_ADD', $arParams['PATH_TO_PS_ADD'], '?add');
$arParams['PATH_TO_PS_EDIT'] = CrmCheckPath('PATH_TO_PS_EDIT', $arParams['PATH_TO_PS_EDIT'], '?ps_id=#ps_id#&edit');

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

$arResult['BUTTONS'] = array();

$psID = isset($arParams['PS_ID']) ? strval($arParams['PS_ID']) : '';

$CrmPerms = new CCrmPerms($USER->GetID());

$psAdd = $psEdit = $psDelete = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

$exists = intval($psID > 0) && is_array(CSalePaySystem::GetList(
																	array(),
																	array('ID' => $psID),
																	false,
																	false,
																	array('ID')
										));

if ($arParams['TYPE'] !== 'list')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_PS_LIST'),
		'TITLE' => GetMessage('CRM_PS_LIST_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PS_LIST'], array()),
		'ICON' => 'btn-list'
	);
}

if ($psAdd && $arParams['TYPE'] == 'edit')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_PS_ADD'),
		'TITLE' => GetMessage('CRM_PS_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_PS_ADD'],
			array()
		),
		'ICON' => 'btn-new'
	);
}

if ($psDelete && $arParams['TYPE'] == 'edit' && $exists)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_PS_DELETE'),
		'TITLE' => GetMessage('CRM_PS_DELETE_TITLE'),
		'LINK' => "javascript:ps_delete('".GetMessage('CRM_PS_DELETE_DLG_TITLE')."', '".GetMessage('CRM_PS_DELETE_DLG_MESSAGE')."', '".GetMessage('CRM_PS_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PS_EDIT'],
				array('ps_id' => $psID)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

$this->IncludeComponentTemplate();
?>