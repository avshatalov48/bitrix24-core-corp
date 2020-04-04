<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userID = $arResult['USER_ID'] = intval(CCrmSecurityHelper::GetCurrentUserID());
$userPerms = CCrmPerms::GetCurrentUserPermissions();
//$userPerms = CCrmPerms::GetCurrentUserPermissions();
if (!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

$arParams['ACTIVITY_CREATE_URL_TEMPLATE'] =  isset($arParams['ACTIVITY_CREATE_URL_TEMPLATE']) ? $arParams['ACTIVITY_CREATE_URL_TEMPLATE'] : '';
$arParams['ACTIVITY_EDIT_URL_TEMPLATE'] =  isset($arParams['ACTIVITY_EDIT_URL_TEMPLATE']) ? $arParams['ACTIVITY_EDIT_URL_TEMPLATE'] : '';
$arParams['ACTIVITY_SHOW_URL_TEMPLATE'] =  isset($arParams['ACTIVITY_SHOW_URL_TEMPLATE']) ? $arParams['ACTIVITY_SHOW_URL_TEMPLATE'] : '';
$arParams['LEAD_SHOW_URL_TEMPLATE'] =  isset($arParams['LEAD_SHOW_URL_TEMPLATE']) ? $arParams['LEAD_SHOW_URL_TEMPLATE'] : '';
$arParams['DEAL_SHOW_URL_TEMPLATE'] =  isset($arParams['DEAL_SHOW_URL_TEMPLATE']) ? $arParams['DEAL_SHOW_URL_TEMPLATE'] : '';
$arParams['CONTACT_SHOW_URL_TEMPLATE'] =  isset($arParams['CONTACT_SHOW_URL_TEMPLATE']) ? $arParams['CONTACT_SHOW_URL_TEMPLATE'] : '';
$arParams['COMPANY_SHOW_URL_TEMPLATE'] =  isset($arParams['COMPANY_SHOW_URL_TEMPLATE']) ? $arParams['COMPANY_SHOW_URL_TEMPLATE'] : '';
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
$arParams['COMMUNICATION_LIST_URL_TEMPLATE'] =  isset($arParams['COMMUNICATION_LIST_URL_TEMPLATE']) ? $arParams['COMMUNICATION_LIST_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = $arResult['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array('#NOBR#','#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']);

$entityID = $arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($entityID <= 0 && isset($_GET['activity_id']))
{
	$entityID = $arParams['ENTITY_ID'] = intval($_GET['activity_id']);
}
$arResult['ENTITY_ID'] = $entityID;

$arParams['UID'] = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$arParams['UID'] = 'mobile_crm_activity_view';
}
$arResult['UID'] = $arParams['UID'];

$dbFields = CCrmActivity::GetList(array(), array('ID' => $entityID));
$arFields = $dbFields->Fetch();

if(!$arFields)
{
	ShowError(GetMessage('CRM_ACTIVITY_VIEW_NOT_FOUND', array('#ID#' => $arParams['ENTITY_ID'])));
	return;
}

$ownerTypeID = intval($arFields['OWNER_TYPE_ID']);
$ownerID = intval($arFields['OWNER_ID']);

if (!CCrmActivity::CheckReadPermission($ownerTypeID, $ownerID, $userPerms))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$typeID = isset($arFields['TYPE_ID']) ? intval($arFields['TYPE_ID']) : CCrmActivityType::Undefined;
//Permissions -->
$canChange = CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID, $userPerms);
//for robots
$provider = CCrmActivity::GetActivityProvider($arFields);
$isTypeEditable= false;
if (
	$provider
	&& $provider::isTypeEditable($arFields['PROVIDER_TYPE_ID'], $arFields['DIRECTION'])
)
{
	$isTypeEditable = true;
}

$arResult['PERMISSIONS'] = array(
	'CAN_COMPLETE' => $canChange && $typeID !== CCrmActivityType::Email,
	'EDIT' => $isTypeEditable,
	'DELETE' => $canChange
);
//<-- Permissions

CCrmMobileHelper::PrepareActivityItem($arFields, $arParams);

//COMMUNICATION
$arFields['CLIENT_TITLE'] = '';
$arFields['CLIENT_SHOW_URL'] = '';
$arFields['CLIENT_IMAGE_URL'] = '';
$arFields['CLIENT_LEGEND'] = '';
$arFields['CLIENT_COMPANY_TITLE'] = '';
$arFields['CLIENT_COMPANY_SHOW_URL'] = '';
$arFields['CLIENT_COMMUNICATION_VALUE'] = '';

$comm = is_array($arFields['COMMUNICATIONS'])
	&& isset($arFields['COMMUNICATIONS'][0])
	? $arFields['COMMUNICATIONS'][0] : null;

if($comm)
{
	$arFields['CLIENT_COMMUNICATION_VALUE'] = isset($comm['VALUE']) ? $comm['VALUE'] : '';

	$commOwnerTypeID = isset($comm['ENTITY_TYPE_ID']) ? intval($comm['ENTITY_TYPE_ID']) : 0;
	$commOwnerID = isset($comm['ENTITY_ID']) ? intval($comm['ENTITY_ID']) : 0;

	if($commOwnerTypeID === CCrmOwnerType::Company)
	{
		$dbRes = CCrmCompany::GetListEx(
			array(),
			array('=ID' => $commOwnerID),
			false,
			false,
			array('TITLE', 'LOGO')
		);
		$arCompany = $dbRes ? $dbRes->Fetch() : null;
		if($arCompany)
		{
			$arFields['CLIENT_TITLE'] = isset($arCompany['TITLE']) ? $arCompany['TITLE'] : '';
			$arFields['CLIENT_SHOW_URL'] = CComponentEngine::MakePathFromTemplate(
				$arParams['COMPANY_SHOW_URL_TEMPLATE'],
				array('company_id' => $commOwnerID)
			);

			$arFields['CLIENT_IMAGE_URL'] = SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_company_big.png?ver=1';
			$imageID = isset($arCompany['LOGO']) ? intval($arCompany['LOGO']) : 0;
			if($imageID > 0)
			{
				$imageInfo = CFile::ResizeImageGet(
					$imageID, array('width' => 55, 'height' => 55), BX_RESIZE_IMAGE_EXACT);
				if($imageInfo && isset($imageInfo['src']))
				{
					$arFields['CLIENT_IMAGE_URL'] = $imageInfo['src'];
				}
			}

			$arMultiFields = array();
			$dbMultiFields = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $commOwnerID)
			);
			if($dbMultiFields)
			{
				while($multiFields = $dbMultiFields->Fetch())
				{
					$arMultiFields[$multiFields['TYPE_ID']][] = array('VALUE' => $multiFields['VALUE'], 'VALUE_TYPE' => $multiFields['VALUE_TYPE']);
				}
			}

			$arFields['CLIENT_CALLTO'] = CCrmMobileHelper::PrepareCalltoParams(
				array(
					'COMMUNICATION_LIST_URL_TEMPLATE' => $arParams['COMMUNICATION_LIST_URL_TEMPLATE'],
					'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
					'ENTITY_ID' => $commOwnerID,
					'FM' => $arMultiFields
				)
			);

			$arFields['CLIENT_MAILTO'] = CCrmMobileHelper::PrepareMailtoParams(
				array(
					'COMMUNICATION_LIST_URL_TEMPLATE' => $arParams['COMMUNICATION_LIST_URL_TEMPLATE'],
					'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
					'ENTITY_ID' => $commOwnerID,
					'FM' => $arMultiFields
				)
			);
		}
	}
	elseif($commOwnerTypeID === CCrmOwnerType::Contact)
	{
		$dbRes = CCrmContact::GetListEx(
			array(),
			array('=ID' => $commOwnerID),
			false,
			false,
			array('NAME', 'LAST_NAME', 'SECOND_NAME', 'PHOTO', 'POST', 'COMPANY_ID', 'COMPANY_TITLE')
		);
		$arContact = $dbRes ? $dbRes->Fetch() : null;
		if($arContact)
		{
			$arFields['CLIENT_TITLE'] = CUser::FormatName(
				$arParams['NAME_TEMPLATE'],
				array(
					'LOGIN' => '',
					'NAME' => isset($arContact['NAME']) ? $arContact['NAME'] : '',
					'LAST_NAME' => isset($arContact['LAST_NAME']) ? $arContact['LAST_NAME'] : '',
					'SECOND_NAME' => isset($arContact['SECOND_NAME']) ? $arContact['SECOND_NAME'] : ''
				),
				false, false
			);

			$arFields['CLIENT_SHOW_URL'] = CComponentEngine::MakePathFromTemplate(
				$arParams['CONTACT_SHOW_URL_TEMPLATE'],
				array('contact_id' => $commOwnerID)
			);

			$arFields['CLIENT_IMAGE_URL'] = SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_contact_big.png?ver=1';
			$imageID = isset($arContact['PHOTO']) ? intval($arContact['PHOTO']) : 0;
			if($imageID > 0)
			{
				$imageInfo = CFile::ResizeImageGet(
					$imageID, array('width' => 55, 'height' => 55), BX_RESIZE_IMAGE_EXACT);
				if($imageInfo && isset($imageInfo['src']))
				{
					$arFields['CLIENT_IMAGE_URL'] = $imageInfo['src'];
				}
			}

			$arFields['CLIENT_LEGEND'] = isset($arContact['POST']) ? $arContact['POST'] : '';
			$company = isset($arContact['COMPANY_ID']) ? intval($arContact['COMPANY_ID']) : 0;
			if($company > 0)
			{
				$arFields['CLIENT_COMPANY_TITLE'] = isset($arContact['COMPANY_TITLE']) ? $arContact['COMPANY_TITLE'] : '';
				$arFields['CLIENT_COMPANY_SHOW_URL'] = CComponentEngine::MakePathFromTemplate(
					$arParams['COMPANY_SHOW_URL_TEMPLATE'],
					array('company_id' => $company)
				);
			}

			$arMultiFields = array();
			$dbMultiFields = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $commOwnerID)
			);
			if($dbMultiFields)
			{
				while($multiFields = $dbMultiFields->Fetch())
				{
					$arMultiFields[$multiFields['TYPE_ID']][] = array('VALUE' => $multiFields['VALUE'], 'VALUE_TYPE' => $multiFields['VALUE_TYPE']);
				}
			}

			$arFields['CLIENT_CALLTO'] = CCrmMobileHelper::PrepareCalltoParams(
				array(
					'COMMUNICATION_LIST_URL_TEMPLATE' => $arParams['COMMUNICATION_LIST_URL_TEMPLATE'],
					'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
					'ENTITY_ID' => $commOwnerID,
					'FM' => $arMultiFields
				)
			);

			$arFields['CLIENT_MAILTO'] = CCrmMobileHelper::PrepareMailtoParams(
				array(
					'COMMUNICATION_LIST_URL_TEMPLATE' => $arParams['COMMUNICATION_LIST_URL_TEMPLATE'],
					'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
					'ENTITY_ID' => $commOwnerID,
					'FM' => $arMultiFields
				)
			);
		}
	}
	elseif($commOwnerTypeID === CCrmOwnerType::Lead)
	{
		$dbRes = CCrmLead::GetListEx(
			array(),
			array('=ID' => $commOwnerID),
			false,
			false,
			array('NAME', 'LAST_NAME', 'SECOND_NAME', 'POST')
		);
		$arLead = $dbRes ? $dbRes->Fetch() : null;
		if($arLead)
		{
			$arFields['CLIENT_TITLE'] = CUser::FormatName(
				$arParams['NAME_TEMPLATE'],
				array(
					'LOGIN' => '',
					'NAME' => isset($arLead['NAME']) ? $arLead['NAME'] : '',
					'LAST_NAME' => isset($arLead['LAST_NAME']) ? $arLead['LAST_NAME'] : '',
					'SECOND_NAME' => isset($arLead['SECOND_NAME']) ? $arLead['SECOND_NAME'] : ''
				),
				false, false
			);

			$arFields['CLIENT_SHOW_URL'] = CComponentEngine::MakePathFromTemplate(
				$arParams['LEAD_SHOW_URL_TEMPLATE'],
				array('lead_id' => $commOwnerID)
			);

			$arFields['CLIENT_IMAGE_URL'] = SITE_DIR.'bitrix/templates/mobile_app/images/crm/no_lead_big.png?ver=1';
			$arFields['CLIENT_LEGEND'] = isset($arLead['POST']) ? $arLead['POST'] : '';

			$arMultiFields = array();
			$dbMultiFields = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $commOwnerID)
			);

			if($dbMultiFields)
			{
				while($multiFields = $dbMultiFields->Fetch())
				{
					$arMultiFields[$multiFields['TYPE_ID']][] = array('VALUE' => $multiFields['VALUE'], 'VALUE_TYPE' => $multiFields['VALUE_TYPE']);
				}
			}

			$arFields['CLIENT_CALLTO'] = CCrmMobileHelper::PrepareCalltoParams(
				array(
					'COMMUNICATION_LIST_URL_TEMPLATE' => $arParams['COMMUNICATION_LIST_URL_TEMPLATE'],
					'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
					'ENTITY_ID' => $commOwnerID,
					'FM' => $arMultiFields
				)
			);

			$arFields['CLIENT_MAILTO'] = CCrmMobileHelper::PrepareMailtoParams(
				array(
					'COMMUNICATION_LIST_URL_TEMPLATE' => $arParams['COMMUNICATION_LIST_URL_TEMPLATE'],
					'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
					'ENTITY_ID' => $commOwnerID,
					'FM' => $arMultiFields
				)
			);
		}
	}
}

$storageTypeID = $arFields['STORAGE_TYPE_ID'];
$arFields['FILES'] = array();
$arFields['WEBDAV_ELEMENTS'] = array();

CCrmActivity::PrepareStorageElementIDs($arFields);
CCrmActivity::PrepareStorageElementInfo($arFields);

$arFields['EDIT_URL'] = $arParams['ACTIVITY_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::makePathFromTemplate(
		$arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
		array('activity_id' => $entityID)
	) : '';

$arResult['ENTITY'] = &$arFields;
unset($arFields);

$sid = bitrix_sessid();
$serviceURLTemplate = ($arParams["SERVICE_URL_TEMPLATE"]
	? $arParams["SERVICE_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.activity.edit/ajax.php?site_id=#SITE#&sessid=#SID#'
);
$arResult['SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => $sid)
);

$this->IncludeComponentTemplate();
