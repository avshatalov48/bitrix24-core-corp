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

$entityTypeID = $arResult['ENTITY_TYPE_ID'] = isset($arParams['ENTITY_TYPE_ID']) ? intval($arParams['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
$entityID = $arResult['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
$arResult['ACTIVITY_EDITOR_UID'] = isset($arParams['ACTIVITY_EDITOR_UID']) ? $arParams['ACTIVITY_EDITOR_UID'] : 'livefeed';
$arResult['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? $arParams['NAME_TEMPLATE'] : CSite::GetNameFormat(false);
$arResult['PATH_TO_USER_PROFILE'] = $arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');


$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'crm_'.mb_strtolower(CCrmOwnerType::ResolveName($entityTypeID)).'_'.$entityID.'_feed_activities';
}
$arResult['UID'] =$arParams['UID'] = $uid;

//--> ACTIVITIES
$arResult['ACTIVITIES'] = array();
$activityFilter = array(
	'COMPLETED' => 'N'
);

if(CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0)
{
	$activityFilter['BINDINGS'] = array(
		array(
			'OWNER_TYPE_ID' => $entityTypeID,
			'OWNER_ID' => $entityID
		)
	);
}
else
{
	$activityFilter['RESPONSIBLE_ID'] = CCrmSecurityHelper::GetCurrentUserID();
}

$dbActivity = CCrmActivity::GetList(
	array('DEADLINE' => 'ASC'),
	$activityFilter,
	false,
	array('nTopCount' => 5),
	array(
		'ID', 'TYPE_ID', 'DIRECTION',
		'SUBJECT', 'RESPONSIBLE_ID',
		'START_TIME', 'END_TIME', 'DEADLINE', 'COMPLETED',
		'OWNER_TYPE_ID', 'OWNER_ID'
	)
);

if(is_object($dbActivity))
{
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	$responsibleIDs = array();
	$activities = array();
	while($activityFields = $dbActivity->GetNext())
	{
		$itemID = intval($activityFields['~ID']);
		$activityIDs[] = $itemID;

		$ownerID = intval($activityFields['~OWNER_ID']);
		$ownerTypeID = intval($activityFields['~OWNER_TYPE_ID']);

		if($arResult['READ_ONLY'])
		{
			$activityFields['CAN_EDIT'] = $activityFields['CAN_DELETE'] = false;
		}
		else
		{
			if($ownerID > 0 && $ownerTypeID > 0)
			{
				$activityFields['CAN_EDIT'] = CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID, $userPermissions);
				$activityFields['CAN_DELETE'] = CCrmActivity::CheckDeletePermission($ownerTypeID, $ownerID, $userPermissions);
			}
			else
			{
				$activityFields['CAN_EDIT'] = $activityFields['CAN_DELETE'] = true;
			}
		}

		$responsibleID = isset($activityFields['~RESPONSIBLE_ID'])
			? intval($activityFields['~RESPONSIBLE_ID']) : 0;

		$activityFields['~RESPONSIBLE_ID'] = $responsibleID;

		if($responsibleID <= 0)
		{
			$activityFields['RESPONSIBLE_FULL_NAME'] = '';
			$activityFields['PATH_TO_RESPONSIBLE'] = '';
		}
		elseif(!in_array($responsibleID, $responsibleIDs, true))
		{
			$responsibleIDs[] = $responsibleID;
		}

		$activityFields['REFERENCE_TITLE'] =
			($ownerTypeID > 0 && $ownerID > 0 && ($ownerTypeID === CCrmOwnerType::Lead || $ownerTypeID === CCrmOwnerType::Deal))
			? CCrmOwnerType::GetCaption($ownerTypeID, $ownerID, false)
			: '';

		$activityFields['CLIENT_TITLE'] = '';

		if(isset($activityFields['~DEADLINE']) && CCrmDateTimeHelper::IsMaxDatabaseDate($activityFields['~DEADLINE']))
		{
			$activityFields['~DEADLINE'] = $activityFields['DEADLINE'] = '';
		}

		$activities[$itemID] = &$activityFields;
		unset($activityFields);
	}

	if(!empty($activities))
	{
		$clientInfos = CCrmActivity::PrepareClientInfos(array_keys($activities));
		foreach($clientInfos as $itemID => &$clientInfo)
		{
			$ttl = isset($clientInfo['TITLE']) ? $clientInfo['TITLE'] : '';
			if($ttl === '')
			{
				$ttl = CCrmOwnerType::GetCaption($clientInfo['ENTITY_TYPE_ID'], $clientInfo['ENTITY_ID']);
			}
			$activities[$itemID]['CLIENT_TITLE'] = $ttl;
		}
		unset($clientInfo);
	}

	$arResult['ACTIVITIES'] = array_values($activities);

	$responsibleInfos = array();
	if(!empty($responsibleIDs))
	{
		$dbUsers = CUser::GetList(
			'ID',
			'ASC',
			array('ID' => implode('||', $responsibleIDs)),
			array('FIELDS' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'TITLE'))
		);

		while($arUser = $dbUsers->Fetch())
		{
			$userID = intval($arUser['ID']);

			$responsibleInfo = array('USER' => $arUser);
			$responsibleInfo['FULL_NAME'] = CUser::FormatName($arResult['NAME_TEMPLATE'], $arUser, true, false);
			$responsibleInfo['HTML_FULL_NAME'] = htmlspecialcharsbx($responsibleInfo['FULL_NAME']);
			$responsibleInfo['PATH'] = CComponentEngine::MakePathFromTemplate(
				$arResult['PATH_TO_USER_PROFILE'],
				array('user_id' => $userID)
			);
			$responsibleInfos[$userID] = &$responsibleInfo;
			unset($responsibleInfo);
		}

		foreach($arResult['ACTIVITIES'] as &$activityFields)
		{
			$responsibleID = $activityFields['~RESPONSIBLE_ID'];
			if(!isset($responsibleInfos[$responsibleID]))
			{
				continue;
			}

			$responsibleInfo = $responsibleInfos[$responsibleID];

			$activityFields['RESPONSIBLE'] = $responsibleInfo['USER'];
			$activityFields['~RESPONSIBLE_FULL_NAME'] = $responsibleInfo['FULL_NAME'];
			$activityFields['RESPONSIBLE_FULL_NAME'] = $responsibleInfo['HTML_FULL_NAME'];
			$activityFields['PATH_TO_RESPONSIBLE'] = $responsibleInfo['PATH'];
		}
		unset($activityFields);
	}
}
//<-- ACTIVITIES

$arResult['LOADER'] = array(
	'serviceUrl' => '/bitrix/components/bitrix/crm.livefeed.activity.list/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
	'componentData' => [
		'signedParameters' => \CCrmInstantEditorHelper::signComponentParams([
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_ID' => $entityID,
		], 'crm.livefeed.activity.list')
	],
);

$this->IncludeComponentTemplate();