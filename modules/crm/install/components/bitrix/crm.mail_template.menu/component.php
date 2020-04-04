<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;
$curPageUrl = $APPLICATION->GetCurPage();
$arParams['PATH_TO_MAIL_TEMPLATE_LIST'] = CrmCheckPath('PATH_TO_MAIL_TEMPLATE_LIST', $arParams['PATH_TO_MAIL_TEMPLATE_LIST'], $curPageUrl);
$arParams['PATH_TO_MAIL_TEMPLATE_EDIT'] = CrmCheckPath('PATH_TO_MAIL_TEMPLATE_EDIT', $arParams['PATH_TO_MAIL_TEMPLATE_EDIT'], $curPageUrl.'?element_id=#element_id#&edit');
$arParams['PATH_TO_MAIL_TEMPLATE_ADD'] = CrmCheckPath('PATH_TO_MAIL_TEMPLATE_ADD', $arParams['PATH_TO_MAIL_TEMPLATE_ADD'], $curPageUrl.'?add');

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

$arResult['BUTTONS'] = array();

$elementID = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;

if (!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$elementAdd = $elementEdit = $elementDelete = true;
$exists = $elementID > 0 && CCrmMailTemplate::Exists($elementID);

if ($arParams['TYPE'] !== 'list')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_MAIL_TEMPLATE_LIST'),
		'TITLE' => GetMessage('CRM_MAIL_TEMPLATE_LIST_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_MAIL_TEMPLATE_LIST'], array()),
		'ICON' => 'btn-list'
	);
}
if ($elementAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_MAIL_TEMPLATE_ADD'),
		'TITLE' => GetMessage('CRM_MAIL_TEMPLATE_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_MAIL_TEMPLATE_ADD'],
			array()
		),
		'ICON' => 'btn-new'
	);
}
if ($elementDelete && $arParams['TYPE'] == 'edit' && $exists)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_MAIL_TEMPLATE_DELETE'),
		'TITLE' => GetMessage('CRM_MAIL_TEMPLATE_DELETE_TITLE'),
		'LINK' => "javascript:crm_mail_template_delete('"
			.GetMessageJS('CRM_MAIL_TEMPLATE_DELETE_DLG_TITLE')."', '"
			.GetMessageJS('CRM_MAIL_TEMPLATE_DELETE_DLG_MESSAGE')."', '"
			.GetMessageJS('CRM_MAIL_TEMPLATE_DELETE_DLG_BTNTITLE')."', '"
			.CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_MAIL_TEMPLATE_EDIT'],
					array('element_id' => $elementID)
				),
				array('delete' => '', 'sessid' => bitrix_sessid())
			)."')",
		'ICON' => 'btn-delete'
	);
}

$this->IncludeComponentTemplate();
?>