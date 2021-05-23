<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('iblock'))
{
	ShowError(GetMessage('CRM_IBLOCK_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$arParams['PATH_TO_PRODUCTPROPS_LIST'] = CrmCheckPath('PATH_TO_PRODUCTPROPS_LIST', $arParams['PATH_TO_PRODUCTPROPS_LIST'], '');
$arParams['PATH_TO_PRODUCTPROPS_ADD'] = CrmCheckPath('PATH_TO_PRODUCTPROPS_ADD', $arParams['PATH_TO_PRODUCTPROPS_ADD'], '?add');
$arParams['PATH_TO_PRODUCTPROPS_EDIT'] = CrmCheckPath('PATH_TO_PRODUCTPROPS_EDIT', $arParams['PATH_TO_PRODUCTPROPS_EDIT'], '?prop_id=#prop_id#&edit');

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

$arResult['BUTTONS'] = array();

$propID = isset($arParams['PROP_ID']) ? intval($arParams['PROP_ID']) : 0;

$CrmPerms = new CCrmPerms($USER->GetID());

$propAdd = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

$arProp = null;
if ($propID > 0)
{
	$iblockID = intval(CCrmCatalog::EnsureDefaultExists());
	$dbRes = CIBlockProperty::GetByID($propID, $iblockID);
	if (is_object($dbRes))
		$arProp = $dbRes->Fetch();
	unset($dbRes);
}

$exists = intval($propID > 0) && is_array($arProp);

if ($arParams['TYPE'] !== 'list')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_PRODUCTPROP_LIST'),
		'TITLE' => GetMessage('CRM_PRODUCTPROP_LIST_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCTPROPS_LIST'], array()),
		'ICON' => 'btn-list'
	);
}
if ($arParams['TYPE'] === 'list' && $propAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_PRODUCTPROP_ADD'),
		'TITLE' => GetMessage('CRM_PRODUCTPROP_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_PRODUCTPROPS_ADD'],
			array()
		),
		'ICON' => 'btn-new'
	);
}

$this->IncludeComponentTemplate();
?>