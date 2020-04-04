<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CModule::IncludeModule('socialnetwork'))
{
	ShowError(GetMessage('SONET_MODULE_NOT_INSTALLED'));
	return;
}

if(!CCrmLiveFeed::hasEvents())
{
	$this->IncludeComponentTemplate('placeholder');
	return;
}

$entityTypeID = $arResult['ENTITY_TYPE_ID'] = isset($arParams['ENTITY_TYPE_ID']) ? intval($arParams['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
$entityID = $arResult['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;

$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'crm_'.strtolower(CCrmOwnerType::ResolveName($entityTypeID)).'_'.$entityID.'_feed';
}
$arResult['UID'] =$arParams['UID'] = $uid;

$slEventEditorUID = isset($arParams['SL_EVENT_EDITOR_UID']) ? $arParams['SL_EVENT_EDITOR_UID'] : '';
if($slEventEditorUID === '')
{
	$slEventEditorUID = 'crm_'.strtolower(CCrmOwnerType::ResolveName($entityTypeID)).'_'.$entityID.'_sl_event_editor';
}
$arResult['SL_EVENT_EDITOR_UID'] = $arParams['SL_EVENT_EDITOR_UID'] = $slEventEditorUID;

$arResult['CAN_EDIT'] = isset($arParams['CAN_EDIT']) ? (bool)$arParams['CAN_EDIT'] : false;
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['SHOW_ACTIVITIES'] = isset($arParams['SHOW_ACTIVITIES']) ? (bool)$arParams['SHOW_ACTIVITIES'] : true;
$arResult['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? $arParams['NAME_TEMPLATE'] : CSite::GetNameFormat(false);
$arResult['DATE_TIME_FORMAT'] = isset($arParams['DATE_TIME_FORMAT']) ? $arParams['DATE_TIME_FORMAT'] : '';
$arResult['PATH_TO_USER_PROFILE'] = $arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

$arResult['PATH_TO_GROUP'] = isset($arParams['PATH_TO_GROUP']) ? $arParams['PATH_TO_GROUP'] : '/workgroups/group/#group_id#/';
$arResult['PATH_TO_SMILE'] = isset($arParams['PATH_TO_SMILE']) ? $arParams['PATH_TO_SMILE'] : '';
$arResult['PATH_TO_SEARCH_TAG'] = isset($arParams['PATH_TO_SEARCH_TAG']) ? $arParams['PATH_TO_SEARCH_TAG'] : '';
$arResult['PATH_TO_CONPANY_DEPARTMENT'] = isset($arParams['PATH_TO_CONPANY_DEPARTMENT']) ? $arParams['PATH_TO_CONPANY_DEPARTMENT'] : '/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#';

$arResult['USE_SMART_FILTER'] = isset($arParams['USE_SMART_FILTER']) ? $arParams['USE_SMART_FILTER'] : 'N';
$arResult['USE_MY_GROUPS_FILTER_ONLY'] = isset($arParams['USE_MY_GROUPS_FILTER_ONLY']) ? $arParams['USE_MY_GROUPS_FILTER_ONLY'] : 'N';

$arResult['CACHE_TYPE'] = isset($arParams['CACHE_TYPE']) ? $arParams['CACHE_TYPE'] : '';
$arResult['CACHE_TIME'] = isset($arParams['CACHE_TIME']) ? $arParams['CACHE_TIME'] : '';

$arResult['POST_FORM_URI'] = isset($arParams['POST_FORM_URI']) ? $arParams['POST_FORM_URI'] : '';
$arResult['ACTION_URI'] = isset($arParams['ACTION_URI']) ? $arParams['ACTION_URI'] : '';
$arResult['PERMISSION_ENTITY_TYPE'] = isset($arParams['PERMISSION_ENTITY_TYPE']) ? $arParams['PERMISSION_ENTITY_TYPE'] : '';

$arResult['LOG_EVENT_ID'] = isset($_REQUEST["log_id"]) ? (int)$_REQUEST["log_id"] : 0;
if($arResult['LOG_EVENT_ID'] <= 0)
{
	$arResult['LOG_EVENT_ID'] = 0;
}

$arResult['ENABLE_FILTER'] = $arResult['LOG_EVENT_ID'] === 0;

//Fixed by livefeed components implementation
$arResult['ACTIVITY_EDITOR_UID'] = "livefeed";
$arResult['ENABLE_TASK_ADD'] = IsModuleInstalled('tasks');
$arResult['ENABLE_CALENDAR_EVENT_ADD'] = IsModuleInstalled('calendar');
$arResult['ENABLE_EMAIL_ADD'] = IsModuleInstalled('subscribe');

$arResult['ENABLE_ACTIVITY_ADD'] = $arResult['CAN_EDIT']
	&& $arResult['LOG_EVENT_ID'] === 0
	&& ($arResult['ENABLE_TASK_ADD'] || $arResult['ENABLE_CALENDAR_EVENT_ADD'] || $arResult['ENABLE_EMAIL_ADD']);

$arResult['ENABLE_MESSAGE_ADD'] = $arResult['CAN_EDIT']
&& $arResult['LOG_EVENT_ID'] === 0;

$liveFeedFilter = new CCrmLiveFeedFilter(
	array(
		'GridFormID' => isset($arParams) ? $arParams['FORM_ID'] : '',
		'EntityTypeID' => $entityTypeID
	)
);

AddEventHandler('socialnetwork', 'OnBeforeSonetLogFilterFill', array($liveFeedFilter, 'OnBeforeSonetLogFilterFill'));
$this->IncludeComponentTemplate();