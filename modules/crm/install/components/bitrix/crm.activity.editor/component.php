<?php
use Bitrix\Crm\Integration\StorageType;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

CModule::IncludeModule('fileman');

$arResult['CONTAINER_ID'] = isset($arParams['~CONTAINER_ID']) ? $arParams['~CONTAINER_ID'] : '';
$arResult['PREFIX'] = isset($arParams['~PREFIX']) ? $arParams['~PREFIX'] : 'crm_default';
$arResult['EDITOR_ID'] = isset($arParams['~EDITOR_ID']) ? $arParams['~EDITOR_ID'] : $arResult['PREFIX'].'_activity_editor';
$arResult['EDITOR_TYPE'] = isset($arParams['~EDITOR_TYPE']) ? $arParams['~EDITOR_TYPE'] : 'MIXED';
$arResult['EDITOR_ITEMS'] = isset($arParams['~EDITOR_ITEMS']) ? $arParams['~EDITOR_ITEMS'] : array();
$arResult['OWNER_TYPE'] = isset($arParams['~OWNER_TYPE']) ? $arParams['~OWNER_TYPE'] : '';
$arResult['OWNER_TYPE_ID'] = CCrmOwnerType::ResolveID($arResult['OWNER_TYPE']);
$arResult['OWNER_ID'] = isset($arParams['~OWNER_ID']) ? $arParams['~OWNER_ID'] : 0;
$arResult['READ_ONLY'] = isset($arParams['~READ_ONLY']) ? (bool)$arParams['~READ_ONLY'] : false;
$arResult['ENABLE_UI'] = isset($arParams['~ENABLE_UI']) ? (bool)$arParams['~ENABLE_UI'] : true;
$arResult['ENABLE_TOOLBAR'] = isset($arParams['~ENABLE_TOOLBAR']) ? (bool)$arParams['~ENABLE_TOOLBAR'] : true;
$arResult['TOOLBAR_ID'] = isset($arParams['~TOOLBAR_ID']) ? $arParams['~TOOLBAR_ID'] : '';
$arResult['BUTTON_ID'] = isset($arParams['~BUTTON_ID']) ? $arParams['~BUTTON_ID'] : '';
$arResult['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$isTaskInstalled = IsModuleInstalled('tasks');

$arResult['ENABLE_TASK_TRACING'] = isset($arParams['~ENABLE_TASK_TRACING']) ? (bool)$arParams['~ENABLE_TASK_TRACING'] : $isTaskInstalled;
$arResult['ENABLE_TASK_ADD'] = isset($arParams['~ENABLE_TASK_ADD']) ? (bool)$arParams['~ENABLE_TASK_ADD'] : $isTaskInstalled;
$arResult['ENABLE_CALENDAR_EVENT_ADD'] = isset($arParams['~ENABLE_CALENDAR_EVENT_ADD']) ? (bool)$arParams['~ENABLE_CALENDAR_EVENT_ADD'] : IsModuleInstalled('calendar');
$arResult['ENABLE_EMAIL_ADD'] = isset($arParams['~ENABLE_EMAIL_ADD']) ? (bool)$arParams['~ENABLE_EMAIL_ADD'] : IsModuleInstalled('subscribe');

$arResult['MARK_AS_COMPLETED_ON_VIEW'] = isset($arParams['~MARK_AS_COMPLETED_ON_VIEW']) ? (bool)$arParams['~MARK_AS_COMPLETED_ON_VIEW'] : true;

if ($arResult['ENABLE_EMAIL_ADD'])
{
	if (is_object($USER) && $USER->isAuthorized() && CModule::includeModule('mail'))
	{
		$res = \Bitrix\Mail\MailboxTable::getList(array(
			'select' => array('NAME', 'LOGIN', 'SERVER_TYPE', 'OPTIONS'),
			'filter' => array('LID' => SITE_ID, 'ACTIVE' => 'Y', 'USER_ID' => array($USER->getId(), 0)),
			'order'  => array('TIMESTAMP_X' => 'DESC'),
		));

		while ($mailbox = $res->fetch())
		{
			if (!$mailbox['USER_ID'] && $mailbox['SERVER_TYPE'] != 'imap')
				continue;

			if (!empty($mailbox['OPTIONS']['flags']) && in_array('crm_connect', $mailbox['OPTIONS']['flags']))
			{
				if ($mailbox['USER_ID'] > 0)
				{
					if (strpos($mailbox['LOGIN'], '@') > 0 && empty($arResult['USER_CRM_EMAIL']))
						$arResult['USER_CRM_EMAIL'] = $mailbox['LOGIN'];
				}
				else
				{
					if (strpos($mailbox['NAME'], '@') > 0 && empty($arResult['SHARED_CRM_EMAIL']))
						$arResult['SHARED_CRM_EMAIL'] = $mailbox['NAME'];
				}
			}
		}
	}
}

if (empty($arResult['SHARED_CRM_EMAIL']))
	$arResult['SHARED_CRM_EMAIL'] = trim(\COption::getOptionString('crm', 'mail', ''));

$arResult['EVENT_VIEW_TAB_ID'] = isset($arParams['~EVENT_VIEW_TAB_ID']) ? $arParams['~EVENT_VIEW_TAB_ID'] : 'tab_event';
$arResult['FORM_ID'] = isset($arParams['~FORM_ID']) ? $arParams['~FORM_ID'] : '';

$arResult['DISABLE_STORAGE_EDIT'] = isset($arParams['~DISABLE_STORAGE_EDIT']) ? (bool)$arParams['~DISABLE_STORAGE_EDIT'] : false;

$storageTypeID = $arResult['STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
$arResult['ENABLE_DISK'] = $storageTypeID === StorageType::Disk;
$arResult['ENABLE_WEBDAV'] = $storageTypeID === StorageType::WebDav;

if(!$arResult['ENABLE_WEBDAV'])
{
	$arResult['WEBDAV_SELECT_URL'] = $arResult['WEBDAV_UPLOAD_URL'] = $arResult['WEBDAV_SHOW_URL'] = '';
}
else
{
	$webDavPaths = CCrmWebDavHelper::GetPaths();
	$arResult['WEBDAV_SELECT_URL'] = isset($webDavPaths['PATH_TO_FILES'])
		? $webDavPaths['PATH_TO_FILES'] : '';
	$arResult['WEBDAV_UPLOAD_URL'] = isset($webDavPaths['ELEMENT_UPLOAD_URL'])
		? $webDavPaths['ELEMENT_UPLOAD_URL'] : '';
	$arResult['WEBDAV_SHOW_URL'] = isset($webDavPaths['ELEMENT_SHOW_INLINE_URL'])
		? $webDavPaths['ELEMENT_SHOW_INLINE_URL'] : '';
}

$flashPlayerUrl = isset($arParams['~FLASH_PLAYER_URL']) ? $arParams['~FLASH_PLAYER_URL'] : '';
if($flashPlayerUrl === '')
{
	$flashPlayerUrl = CComponentEngine::makePathFromTemplate('#SITE_DIR#bitrix/components/bitrix/player/mediaplayer/player');
}
$arResult['FLASH_PLAYER_URL'] = $flashPlayerUrl;

$flashPlayerApiUrl = isset($arParams['~FLASH_PLAYER_API_URL']) ? $arParams['~FLASH_PLAYER_API_URL'] : '';
if($flashPlayerApiUrl === '')
{
	$flashPlayerApiUrl = CComponentEngine::makePathFromTemplate('#SITE_DIR#bitrix/components/bitrix/player/mediaplayer/jwplayer.js');
}
$arResult['FLASH_PLAYER_API_URL'] = $flashPlayerApiUrl;

$arResult['CREATE_EVENT_URL'] = CComponentEngine::makePathFromTemplate('#SITE_DIR#bitrix/components/bitrix/crm.event.add/box.php');

$this->IncludeComponentTemplate();