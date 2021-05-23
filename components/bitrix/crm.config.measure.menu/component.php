<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CATALOG_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$arParams['PATH_TO_MEASURE_LIST'] = CrmCheckPath('PATH_TO_MEASURE_LIST', $arParams['PATH_TO_MEASURE_LIST'], '');
$arParams['PATH_TO_MEASURE_EDIT'] = CrmCheckPath('PATH_TO_MEASURE_EDIT', $arParams['PATH_TO_MEASURE_EDIT'], '?measure_id=#measure_id#&edit');

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

$arResult['BUTTONS'] = array();
$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;

$userPermissions = CCrmAuthorizationHelper::GetUserPermissions();
$canAdd = $canEdit = $canDelete = CCrmAuthorizationHelper::CheckConfigurationReadPermission($userPermissions);

if ($arParams['TYPE'] !== 'list')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_MEASURE_LIST'),
		'TITLE' => GetMessage('CRM_MEASURE_LIST_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_MEASURE_LIST'], array()),
		'ICON' => 'btn-list'
	);
}

if ($canAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_MEASURE_ADD'),
		'TITLE' => GetMessage('CRM_MEASURE_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_MEASURE_EDIT'],
			array('measure_id' => 0)
		),
		'ICON' => 'btn-new'
	);
}

if ($canDelete && ($arParams['TYPE'] === 'edit' && $arParams['ELEMENT_ID'] > 0))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_MEASURE_DELETE'),
		'TITLE' => GetMessage('CRM_MEASURE_DELETE_TITLE'),
		'LINK' => "javascript:measure_delete('".GetMessage('CRM_MEASURE_DELETE_DLG_TITLE')."', '".GetMessage('CRM_MEASURE_DELETE_DLG_MESSAGE')."', '".GetMessage('CRM_MEASURE_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_MEASURE_EDIT'],
				array('measure_id' => $arParams['ELEMENT_ID'])),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

$this->IncludeComponentTemplate();
?>