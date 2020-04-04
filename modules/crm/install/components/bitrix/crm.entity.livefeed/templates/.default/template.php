<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$canEdit = $arResult['CAN_EDIT'];
$entityTypeID = $arResult['ENTITY_TYPE_ID'];
$entityID = $arResult['ENTITY_ID'];

$UID = $arResult['UID'];
$prefix = htmlspecialcharsbx($UID).'_';
$activityEditorID = $arResult['ACTIVITY_EDITOR_UID'];

if($arResult['ENABLE_ACTIVITY_ADD'] || $arResult['SHOW_ACTIVITIES']):
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.editor',
		'',
		array(
			'CONTAINER_ID' => '',
			'PREFIX' => $UID,
			'EDITOR_ID' => $activityEditorID,
			'OWNER_TYPE' => CCrmOwnerType::ResolveName($entityTypeID),
			'OWNER_ID' => $entityID,
			'READ_ONLY' => true,
			'ENABLE_UI' => false,
			'ENABLE_TOOLBAR' => false,
			'ENABLE_TASK_TRACING' => false,
			'ENABLE_TASK_ADD' => $arResult['ENABLE_TASK_ADD'],
			'ENABLE_CALENDAR_EVENT_ADD' => $arResult['ENABLE_CALENDAR_EVENT_ADD'],
			'ENABLE_EMAIL_ADD' => $arResult['ENABLE_EMAIL_ADD']
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
endif;

?><div class="crm-feed-wrap">
<div class="crm-feed-right-side"><?
if($arResult['SHOW_ACTIVITIES']):
	$APPLICATION->IncludeComponent(
		'bitrix:crm.livefeed.activity.list',
		'',
		array(
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_ID' => $entityID,
			'ACTIVITY_EDITOR_UID' => $arResult['ACTIVITY_EDITOR_UID'],
			'PATH_TO_USER' => $arResult['PATH_TO_USER_PROFILE']
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
endif;
$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.birthday.nearest',
	SITE_TEMPLATE_ID === 'bitrix24' ? 'widget' : '',
	array('DATE_FORMAT' => 'j F'),
	null,
	array('HIDE_ICONS' => 'Y')
);
?></div>
<div class="crm-feed">
<div id="<?=$prefix?>menu" class="crm-feed-top-nav"><?
if($arResult['ENABLE_ACTIVITY_ADD']):
	?><span id="<?=$prefix?>add_message" class="crm-feed-top-nav-item"><?=htmlspecialcharsbx(GetMessage('CRM_ENTITY_LF_MENU_BTN_MESSAGE'))?></span>
	<span id="<?=$prefix?>add_task" class="crm-feed-top-nav-item"><?=htmlspecialcharsbx(GetMessage('CRM_ENTITY_LF_MENU_BTN_TASK'))?></span>
	<span id="<?=$prefix?>add_meeting" class="crm-feed-top-nav-item"><?=htmlspecialcharsbx(GetMessage('CRM_ENTITY_LF_MENU_BTN_MEETING'))?></span>
	<span id="<?=$prefix?>add_call" class="crm-feed-top-nav-item"><?=htmlspecialcharsbx(GetMessage('CRM_ENTITY_LF_MENU_BTN_CALL'))?></span>
	<span id="<?=$prefix?>add_email" class="crm-feed-top-nav-item"><?=htmlspecialcharsbx(GetMessage('CRM_ENTITY_LF_MENU_BTN_EMAIL'))?></span>
	<?
endif;

if($arResult['ENABLE_FILTER']):
	$APPLICATION->IncludeComponent(
		'bitrix:socialnetwork.log.filter',
		'body',
		array(
			'arParams' => array_merge(
				$arParams,
				array(
					'USE_TARGET' => 'N',
					'TARGET_ID' => '',
					'SHOW_FOLLOW' => 'N',
					'USE_SONET_GROUPS' => 'N',
					'USE_SMART_FILTER' => $arResult['USE_SMART_FILTER'],
					'MY_GROUPS_ONLY' => $arResult['USE_MY_GROUPS_FILTER_ONLY'],
					'POST_FORM_URI' => $arResult['POST_FORM_URI'],
					'ACTION_URI' => $arResult['ACTION_URI'],
					'TOP_OUT' => (isset($arParams["ENTITY_TYPE_ID"]) && !empty($arParams["ENTITY_TYPE_ID"]) && isset($arParams["ENTITY_ID"]) && !empty($arParams["ENTITY_ID"]) ? 'N' : 'Y')
				)
			),
			'arResult' => $arResult
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
endif;
?></div><?
if($arResult['ENABLE_MESSAGE_ADD']):
	$APPLICATION->IncludeComponent('bitrix:crm.socialnetwork.log_event.edit',
			'',
			array(
				'UID' => $arResult['SL_EVENT_EDITOR_UID'],
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
				'PERMISSION_ENTITY_TYPE' => $arResult['PERMISSION_ENTITY_TYPE'],
				'FORM_ID' => $arResult['FORM_ID'],
				'POST_FORM_URI' => $arResult['POST_FORM_URI']
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
endif;

$liveFeedFilter = new CCrmLiveFeedFilter(array('EntityTypeID' => $entityTypeID));
AddEventHandler('socialnetwork', 'OnSonetLogFilterProcess', array($liveFeedFilter, 'OnSonetLogFilterProcess'));
$APPLICATION->IncludeComponent(
	'bitrix:socialnetwork.log.ex',
	'',
	array(
		'IS_CRM' => 'Y',
		'USE_FOLLOW' => 'N',
		'LOG_ID' => $arResult['LOG_EVENT_ID'],
		'PATH_TO_LOG_ENTRY' => '/crm/stream/?log_id=#log_id#',
		'CRM_ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID),
		'CRM_ENTITY_ID' => $entityID,
		'CRM_EXTENDED_MODE' => 'Y',
		'CRM_ENABLE_ACTIVITY_EDITOR' => false,
		'HIDE_EDIT_FORM' => 'Y',
		'USE_COMMENTS' => 'Y',
		'SHOW_EVENT_ID_FILTER' => 'N',
		'SHOW_SETTINGS_LINK' => 'Y',
		'SET_LOG_CACHE' => 'Y',
		'PAGER_DESC_NUMBERING' => 'N',
		'AJAX_MODE' => 'N',
		'AJAX_OPTION_SHADOW' => 'N',
		'AJAX_OPTION_HISTORY' => 'N',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_STYLE' => 'Y',
		'SHOW_YEAR' => 'Y',
		'SHOW_LOGIN' => 'Y',
		'SET_TITLE' => 'N',
		'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE'],
		'DATE_TIME_FORMAT' => $arResult['DATE_TIME_FORMAT'],
		'CACHE_TYPE' => $arResult['CACHE_TYPE'],
		'CACHE_TIME' => $arResult['CACHE_TIME'],
		'PATH_TO_USER' => $arResult['PATH_TO_USER_PROFILE'],
		'PATH_TO_GROUP' => $arResult['PATH_TO_GROUP'],
		'PATH_TO_SMILE' => $arResult['PATH_TO_SMILE'],
		'PATH_TO_SEARCH_TAG' => $arResult['PATH_TO_SEARCH_TAG'],
		'PATH_TO_CONPANY_DEPARTMENT' => $arResult['PATH_TO_CONPANY_DEPARTMENT'],
		'CONTAINER_ID' => 'log_external_container',
		'SHOW_RATING' => '',
		'RATING_TYPE' => '',
		//'AVATAR_SIZE' => 42,
		//'AVATAR_SIZE_COMMENT' => 30,
		'SET_NAV_CHAIN' => 'N',
		'NEW_TEMPLATE' => 'Y',
		'USE_FAVORITES' => 'N',
		'PAGE_SIZE' => (isset($arParams['LAZYLOAD']) && $arParams['LAZYLOAD'] == 'Y' ? 5 : 0) // 0 - default value
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
?></div></div><?
if($arResult['ENABLE_ACTIVITY_ADD']):
?><script type="text/javascript">
	BX.ready(
		function()
		{
			var uid = "<?=CUtil::JSEscape($UID)?>";
			BX.CrmEntityLiveFeed.create(
				uid,
				{
					"prefix": "<?=CUtil::JSEscape($prefix)?>",
					"eventEditorId": "<?=CUtil::JSEscape($arResult['SL_EVENT_EDITOR_UID'])?>",
					"activityEditorId": "<?=CUtil::JSEscape($activityEditorID)?>",
					"formName": "bxForm_<?=$arResult['FORM_ID']?>",
					"clientTemplate": "<?=GetMessageJS('CRM_ENTITY_LF_ACTIVITY_CLIENT_INFO')?>",
					"referenceTemplate": "<?=GetMessageJS('CRM_ENTITY_LF_ACTIVITY_REFERENCE_INFO')?>"
				}
			);
		}
	);
</script><?
endif;