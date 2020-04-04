<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!(CCrmSecurityHelper::IsAuthorized() && CCrmPerms::IsAccessEnabled()))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array('#NOBR#','#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']);

$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'mobile_crm_config_user_email';
}
$arResult['UID'] = $arParams['UID'] = $uid;
$currentUserID = $arResult['USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();

$dbUser = CUser::GetList(
	($by = 'id'),
	($order = 'asc'),
	array('ID_EQUAL_EXACT' => $currentUserID),
	array('FIELDS' => array('LOGIN', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'EMAIL', 'PERSONAL_PHOTO'))
);
$user = $dbUser->Fetch();

$contextID = isset($arParams['CONTEXT_ID']) ? $arParams['CONTEXT_ID'] : '';
if($contextID === '' && isset($_REQUEST['context_id']))
{
	$contextID = $_REQUEST['context_id'];
}
$arResult['CONTEXT_ID'] = $contextID;

$arResult['CRM_EMAIL'] = CCrmMailHelper::ExtractEmail(COption::GetOptionString('crm', 'mail', ''));

$arResult['USER_FULL_NAME'] = CUser::FormatName(
	$arParams['NAME_TEMPLATE'],
	array(
		'LOGIN' => isset($user['LOGIN']) ? $user['LOGIN'] : '',
		'NAME' => isset($user['NAME']) ? $user['NAME'] : '',
		'SECOND_NAME' => isset($user['SECOND_NAME']) ? $user['SECOND_NAME'] : '',
		'LAST_NAME' => isset($user['LAST_NAME']) ? $user['LAST_NAME'] : '',
	),
	true,
	false
);
$arResult['USER_EMAIL'] = isset($user['EMAIL']) ? $user['EMAIL'] : '';

$userPhotoInfo = isset($user['PERSONAL_PHOTO'])
	? CFile::ResizeImageGet(
		$user['PERSONAL_PHOTO'],
		array('width' => 55, 'height' => 55),
		BX_RESIZE_IMAGE_EXACT
	) : null;
$arResult['USER_PHOTO_URL'] = is_array($userPhotoInfo) && isset($userPhotoInfo['src'])
	? $userPhotoInfo['src'] : '';

$lastEmailAddresser = CUserOptions::GetOption('crm', 'activity_email_addresser', '');
if($lastEmailAddresser === '')
{
	$arResult['USER_LAST_USED_NAME'] = '';
	$arResult['USER_LAST_USED_EMAIL'] = '';
}
else
{
	$info = CCrmMailHelper::ParseEmail($lastEmailAddresser);
	$arResult['USER_LAST_USED_NAME'] = $info['NAME'];
	$arResult['USER_LAST_USED_EMAIL'] = $info['EMAIL'];
}

$arResult['USER_ACTUAL_NAME'] = $arResult['USER_LAST_USED_NAME'] !== ''
	? $arResult['USER_LAST_USED_NAME'] : $arResult['USER_FULL_NAME'];

$arResult['USER_ACTUAL_EMAIL'] = $arResult['USER_LAST_USED_EMAIL'] !== ''
	? $arResult['USER_LAST_USED_EMAIL']
	: ($arResult['CRM_EMAIL'] != '' ? $arResult['CRM_EMAIL'] : $arResult['USER_EMAIL']);

$arResult['USER_ACTUAL_ADDRESSER'] = "{$arResult['USER_ACTUAL_NAME']} <{$arResult['USER_ACTUAL_EMAIL']}>";

$sid = bitrix_sessid();
$serviceURLTemplate = ($arParams["SERVICE_URL_TEMPLATE"]
	? $arParams["SERVICE_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.config.user_email/ajax.php?site_id=#SITE#&sessid=#SID#'
);
$arResult['SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => $sid)
);

$this->IncludeComponentTemplate();
