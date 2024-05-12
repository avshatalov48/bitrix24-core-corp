<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

global $USER, $APPLICATION;

if (trim($arResult['additionalParameters']['FORM_NAME'] ?? '') === '')
{
	$arResult['additionalParameters']['FORM_NAME'] = 'form_element';
}

global $adminSidePanelHelper;
if(!is_object($adminSidePanelHelper))
{
	require_once($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/interface/admin_lib.php');
	$adminSidePanelHelper = new CAdminSidePanelHelper();
}

if($adminSidePanelHelper->isPublicSidePanel())
{
	\Bitrix\Main\UI\Extension::load([
		'admin_interface',
		'sidepanel'
	]);
	$titleUserId = $USER->GetID();
}
else
{
	$titleUserId = '<a title="' . CUtil::JSEscape(Loc::getMessage('MAIN_EDIT_USER_PROFILE')) . '" class="tablebodylink" href="/bitrix/admin/user_edit.php?ID=' . $USER->GetID() . '&lang=' . LANGUAGE_ID . '">' . $USER->GetID() . '</a>';
}

$arResult['titleUserId'] = $titleUserId;
$arResult['selfFolderUrl'] = (defined('SELF_FOLDER_URL') ? SELF_FOLDER_URL : '/bitrix/admin/');

$attrList = [];
if($arResult['userField']['EDIT_IN_LIST'] !== 'Y')
{
	$attrList['disabled'] = 'disabled';
}

foreach($arResult['value'] as $key => $value)
{
	$attrList['name'] = str_replace('[]', '[' . $key . ']', $arResult['fieldName']);
	$attrList['value'] = (int)$value;
	$attrList['name_x'] = preg_replace('/([^a-z0-9])/i', '_', $value);
	$arResult['value'][$key] = $attrList;
}