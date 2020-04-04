<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$arParams['ACTIVITY_CREATE_URL_TEMPLATE'] =  isset($arParams['ACTIVITY_CREATE_URL_TEMPLATE']) ? $arParams['ACTIVITY_CREATE_URL_TEMPLATE'] : '';
$arParams['ACTIVITY_EDIT_URL_TEMPLATE'] =  isset($arParams['ACTIVITY_EDIT_URL_TEMPLATE']) ? $arParams['ACTIVITY_EDIT_URL_TEMPLATE'] : '';
$arParams['ACTIVITY_SHOW_URL_TEMPLATE'] =  isset($arParams['ACTIVITY_SHOW_URL_TEMPLATE']) ? $arParams['ACTIVITY_SHOW_URL_TEMPLATE'] : '';
$arParams['COMMUNICATION_SELECTOR_URL_TEMPLATE'] = isset($arParams['COMMUNICATION_SELECTOR_URL_TEMPLATE']) ? $arParams['COMMUNICATION_SELECTOR_URL_TEMPLATE'] : '';
$arParams['DEAL_SELECTOR_URL_TEMPLATE'] = isset($arParams['DEAL_SELECTOR_URL_TEMPLATE']) ? $arParams['DEAL_SELECTOR_URL_TEMPLATE'] : '';
$arParams['USER_EMAIL_CONFIGURATOR_URL'] = isset($arParams['USER_EMAIL_CONFIGURATOR_URL']) ? $arParams['USER_EMAIL_CONFIGURATOR_URL'] : '';
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array('#NOBR#','#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']);

$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'mobile_crm_activity_edit';
}
$arResult['UID'] = $arParams['UID'] = $uid;
$currentUserID = $arResult['USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$userPerms = CCrmPerms::GetCurrentUserPermissions();

$typeID = CCrmActivityType::Undefined;
$ownerID = 0;
$ownerTypeID = CCrmOwnerType::Undefined;

$entityID = $arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($entityID <= 0 && isset($_REQUEST['activity_id']))
{
	$entityID = $arParams['ENTITY_ID'] = intval($_REQUEST['activity_id']);
}
$arResult['ENTITY_ID'] = $entityID;
$arFields = array();
if($entityID > 0)
{
	$arResult['MODE'] = 'UPDATE';
	$arFields = CCrmActivity::GetByID($entityID, false);
	if(!is_array($arFields))
	{
		ShowError(GetMessage('CRM_ACTIVITY_EDIT_NOT_FOUND'));
		return;
	}

	$typeID = isset($arFields['TYPE_ID']) ? intval($arFields['TYPE_ID']) : 0;
	$ownerID = isset($arFields['OWNER_ID']) ? intval($arFields['OWNER_ID']) : 0;
	$ownerTypeID = isset($arFields['OWNER_TYPE_ID']) ? intval($arFields['OWNER_TYPE_ID']) : CCrmOwnerType::Undefined;
}
else
{
	$arResult['MODE'] = 'CREATE';
	$typeID = $arParams['TYPE_ID'] = isset($arParams['TYPE_ID']) ? intval($arParams['TYPE_ID']) : CCrmActivityType::Undefined;
	if($typeID <= 0 && isset($_REQUEST['type_id']))
	{
		$typeID = $arParams['TYPE_ID'] = intval($_REQUEST['type_id']);
	}

	$ownerID = $arParams['OWNER_ID'] = isset($arParams['OWNER_ID']) ? intval($arParams['OWNER_ID']) : 0;
	if($ownerID <= 0 && isset($_REQUEST['owner_id']))
	{
		$ownerID = $arParams['OWNER_ID'] = intval($_REQUEST['owner_id']);
	}

	$ownerTypeName = $arParams['OWNER_TYPE'] = isset($arParams['OWNER_TYPE']) ? $arParams['OWNER_TYPE'] : '';
	if($ownerTypeName === '' && isset($_REQUEST['owner_type']))
	{
		$ownerTypeName = $arParams['OWNER_TYPE'] = $_REQUEST['owner_type'];
	}
	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
}

if(!CCrmActivityType::IsDefined($typeID))
{
	ShowError(GetMessage('CRM_ACTIVITY_EDIT_TYPE_IS_NOT_SUPPORTED', array('#TYPE#' => $typeID)));
	return;
}

if($ownerID > 0 && $ownerTypeID !== CCrmOwnerType::Undefined)
{
	if(!CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID, $userPerms))
	{
		ShowError(GetMessage('CRM_PERMISSION_DENIED'));
		return;
	}
}
elseif(!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['TYPE_ID'] = $typeID;
$arResult['OWNER_ID'] = $ownerID;
$arResult['OWNER_TYPE_ID'] = $ownerTypeID;
$arResult['OWNER_TYPE_NAME'] = $ownerTypeName = $ownerTypeID !== CCrmOwnerType::Undefined ? CCrmOwnerType::ResolveName($ownerTypeID) : '';
$arResult['OWNER_TITLE'] = ($ownerTypeID !== CCrmOwnerType::Undefined && $ownerID > 0) ? CCrmOwnerType::GetCaption($ownerTypeID, $ownerID) : '';

$arResult['CAN_CHANGE_OWNER'] = $ownerTypeID !== CCrmOwnerType::Deal;

if($entityID > 0)
{
	$storageTypeID = isset($arFields['STORAGE_TYPE_ID'])
		? intval($arFields['STORAGE_TYPE_ID']) : \Bitrix\Crm\Integration\StorageType::Undefined;

	CCrmActivity::PrepareStorageElementIDs($arFields);
	CCrmActivity::PrepareStorageElementInfo($arFields);

	$arFields['START_TIME_STAMP'] = isset($arFields['START_TIME']) ? MakeTimeStamp($arFields['START_TIME']) : 0;
	$arFields['END_TIME_STAMP'] = isset($arFields['END_TIME']) ? MakeTimeStamp($arFields['END_TIME']) : 0;

	$arFields['NOTIFY_TYPE'] = isset($arFields['NOTIFY_TYPE']) ? intval($arFields['NOTIFY_TYPE']) : CCrmActivityNotifyType::None;
	$arFields['NOTIFY_VALUE'] = isset($arFields['NOTIFY_VALUE']) ? intval($arFields['NOTIFY_VALUE']) : 0;
}
else
{
	$arFields['ID'] = 0;
	$arFields['START_TIME_STAMP'] = $arFields['END_TIME_STAMP'] = time() + CTimeZone::GetOffset();
	$arFields['START_TIME'] = $arFields['END_TIME'] = ConvertTimeStamp($arFields['START_TIME_STAMP'], 'FULL', SITE_ID);

	$arFields['NOTIFY_TYPE'] = CCrmActivityNotifyType::None;
	$arFields['NOTIFY_VALUE'] = 0;
	$arFields['RESPONSIBLE_ID'] = $arResult['USER_ID'];
	if($arFields['RESPONSIBLE_ID'] > 0)
	{
		$dbUser = CUser::GetList(
			($by='id'),
			($order='asc'),
			array('ID'=> $arFields['RESPONSIBLE_ID']),
			array(
				'FIELDS'=> array(
					'ID',
					'LOGIN',
					'EMAIL',
					'NAME',
					'LAST_NAME',
					'SECOND_NAME'
				)
			)
		);
		$user = $dbUser->Fetch();
		if($user)
		{
			$arFields['RESPONSIBLE_LOGIN'] = $user['LOGIN'];
			$arFields['RESPONSIBLE_NAME'] = $user['NAME'];
			$arFields['RESPONSIBLE_LAST_NAME'] = $user['LAST_NAME'];
			$arFields['RESPONSIBLE_SECOND_NAME'] = $user['SECOND_NAME'];
		}
	}

	if($ownerID > 0 && $ownerTypeID !== CCrmOwnerType::Undefined)
	{
		// Prepare default communication
		$commType = '';
		if($typeID === CCrmActivityType::Call)
		{
			$commType = 'PHONE';
		}
		elseif($typeID === CCrmActivityType::Email)
		{
			$commType = 'EMAIL';
		}
		elseif($typeID === CCrmActivityType::Meeting)
		{
			$commType = 'PERSON';
		}

		if($commType === 'PERSON')
		{
			if($ownerTypeID !== CCrmOwnerType::Deal)
			{
				$arFields['COMMUNICATIONS'] = array(
					array(
						'TYPE' => '',
						'VALUE' => '',
						'ENTITY_TYPE_ID' => $ownerTypeID,
						'ENTITY_ID' => $ownerID
					)
				);
			}
			else
			{
				$comms = array();
				$commKeys = isset($_REQUEST['comm']) ? is_array($_REQUEST['comm']) : array();
				if(!empty($commKeys))
				{
					foreach($commKeys as $commKey)
					{
						$commInfo = explode('_', $commKey);
						if(count($commInfo) < 2)
						{
							continue;
						}

						$entityTypeName = strtoupper($commInfo[0]);
						$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
						$entityID = intval($commInfo[1]);

						if(!CCrmOwnerType::IsDefined($entityTypeID)
							|| $entityID <= 0
							|| !CCrmActivity::CheckUpdatePermission($entityTypeID, $entityID, $userPerms))
						{
							continue;
						}

						$comms[] = array(
							'TYPE' => '',
							'VALUE' => '',
							'ENTITY_TYPE_ID' => $entityTypeID,
							'ENTITY_ID' => $entityID
						);
					}
				}

				if(empty($comms))
				{
					$dbDeal = CCrmDeal::GetListEx(
						array(),
						array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('CONTACT_ID', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME', 'COMPANY_ID', 'COMPANY_TITLE')
					);

					$deal = $dbDeal->Fetch();
					if(is_array($deal))
					{
						$contactID = isset($deal['CONTACT_ID']) ? intval($deal['CONTACT_ID']) : 0;
						$companyID = isset($deal['COMPANY_ID']) ? intval($deal['COMPANY_ID']) : 0;

						if($contactID > 0 && CCrmActivity::CheckUpdatePermission(CCrmOwnerType::Contact, $contactID, $userPerms))
						{
							$comms[] = array(
								'TYPE' => '',
								'VALUE' => '',
								'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
								'ENTITY_ID' => $contactID,
								'ENTITY_SETTINGS' => array(
									'NAME' => isset($deal['CONTACT_NAME']) ? $deal['CONTACT_NAME'] : '',
									'SECOND_NAME' => isset($deal['CONTACT_SECOND_NAME']) ? $deal['CONTACT_SECOND_NAME'] : '',
									'LAST_NAME' => isset($deal['CONTACT_LAST_NAME']) ? $deal['CONTACT_LAST_NAME'] : ''
								)
							);
						}

						if(empty($comms) && $companyID > 0 && CCrmActivity::CheckUpdatePermission(CCrmOwnerType::Company, $companyID, $userPerms))
						{
							$comms[] = array(
								'TYPE' => '',
								'VALUE' => '',
								'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
								'ENTITY_ID' => $companyID,
								'ENTITY_SETTINGS' => array(
									'COMPANY_TITLE' => isset($deal['COMPANY_TITLE']) ? $deal['COMPANY_TITLE'] : ''
								)
							);
						}
					}
				}

				$arFields['COMMUNICATIONS'] = &$comms;
				unset($comms);
			}
		}
		else
		{
			if($ownerTypeID !== CCrmOwnerType::Deal)
			{
				$commValue = CCrmActivity::GetDefaultCommunicationValue($ownerTypeID, $ownerID, $commType);
				if($commValue !== '')
				{
					$arFields['COMMUNICATIONS'] = array(
						array(
							'TYPE' => $commType,
							'VALUE' => $commValue,
							'ENTITY_TYPE_ID' => $ownerTypeID,
							'ENTITY_ID' => $ownerID
						)
					);
				}
			}
			else
			{
				$comms = array();
				$commKeys = isset($_REQUEST['comm']) && is_array($_REQUEST['comm']) ? $_REQUEST['comm'] : array();
				if(!empty($commKeys))
				{
					foreach($commKeys as $commKey)
					{
						$commInfo = explode('_', $commKey);
						if(count($commInfo) < 2)
						{
							continue;
						}

						$entityTypeName = strtoupper($commInfo[0]);
						$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
						$entityID = intval($commInfo[1]);

						if(!CCrmOwnerType::IsDefined($entityTypeID)
							|| $entityID <= 0
							|| !CCrmActivity::CheckUpdatePermission($entityTypeID, $entityID, $userPerms))
						{
							continue;
						}

						$commValue = CCrmActivity::GetDefaultCommunicationValue($entityTypeID, $entityID, $commType);
						if($commValue !== '')
						{
							$comms[] = array(
								'TYPE' => $commType,
								'VALUE' => $commValue,
								'ENTITY_TYPE_ID' => $entityTypeID,
								'ENTITY_ID' => $entityID
							);
						}
					}
				}

				if(empty($comms))
				{
					$dbDeal = CCrmDeal::GetListEx(
						array(),
						array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('CONTACT_ID', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME', 'COMPANY_ID', 'COMPANY_TITLE')
					);

					$deal = $dbDeal->Fetch();
					if(is_array($deal))
					{
						$contactID = isset($deal['CONTACT_ID']) ? intval($deal['CONTACT_ID']) : 0;
						$companyID = isset($deal['COMPANY_ID']) ? intval($deal['COMPANY_ID']) : 0;

						$comms = array();
						if($contactID > 0 && CCrmActivity::CheckUpdatePermission(CCrmOwnerType::Contact, $contactID, $userPerms))
						{
							$commValue = CCrmActivity::GetDefaultCommunicationValue(CCrmOwnerType::Contact, $contactID, $commType);
							if($commValue !== '')
							{
								$comms[] = array(
									'TYPE' => $commType,
									'VALUE' => $commValue,
									'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
									'ENTITY_ID' => $contactID,
									'ENTITY_SETTINGS' => array(
										'NAME' => isset($deal['CONTACT_NAME']) ? $deal['CONTACT_NAME'] : '',
										'SECOND_NAME' => isset($deal['CONTACT_SECOND_NAME']) ? $deal['CONTACT_SECOND_NAME'] : '',
										'LAST_NAME' => isset($deal['CONTACT_LAST_NAME']) ? $deal['CONTACT_LAST_NAME'] : ''
									)
								);
							}
						}

						if(empty($comms) && $companyID > 0 && CCrmActivity::CheckUpdatePermission(CCrmOwnerType::Company, $companyID, $userPerms))
						{
							$commValue = CCrmActivity::GetDefaultCommunicationValue(CCrmOwnerType::Company, $companyID, $commType);
							if($commValue !== '')
							{
								$comms[] = array(
									'TYPE' => $commType,
									'VALUE' => $commValue,
									'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
									'ENTITY_ID' => $companyID,
									'ENTITY_SETTINGS' => array(
										'COMPANY_TITLE' => isset($deal['COMPANY_TITLE']) ? $deal['COMPANY_TITLE'] : ''
									)
								);
							}
						}
					}
				}

				$arFields['COMMUNICATIONS'] = &$comms;
				unset($comms);
			}
		}
	}
}

CCrmMobileHelper::PrepareActivityItem(
	$arFields,
	$arParams,
	array(
		'ENABLE_COMMUNICATIONS' => true,
		'ENABLE_FILES' => true
	)
);

//Trim seconds
$arFields['START_TIME'] = CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', $arFields['START_TIME_STAMP']));
$arFields['END_TIME'] = CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', $arFields['END_TIME_STAMP']));

$arResult['ENTITY'] = $arFields;
unset($arFields);

if($typeID === CCrmActivityType::Call || $typeID === CCrmActivityType::Meeting)
{
	$arResult['NOTIFY_TYPES'] = CCrmActivityNotifyType::PrepareListItems();
}
elseif($typeID === CCrmActivityType::Email)
{
	$arResult['CRM_EMAIL'] = CCrmMailHelper::ExtractEmail(COption::GetOptionString('crm', 'mail', ''));

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

	$dbUser = CUser::GetList(
		($by = 'id'),
		($order = 'asc'),
		array('ID_EQUAL_EXACT' => $currentUserID),
		array('FIELDS' => array('LOGIN', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'EMAIL', 'PERSONAL_PHOTO'))
	);
	$user = $dbUser->Fetch();
	if(!is_array($user))
	{
		$arResult['USER_EMAIL'] = '';
		$arResult['USER_FULL_NAME'] = '';
		$arResult['USER_PHOTO_URL'] = '';
	}
	else
	{
		//EMAILS
		$arResult['USER_EMAIL'] = isset($user['EMAIL']) ? $user['EMAIL'] : '';

		//USER FULL NAME
		$arResult['USER_FULL_NAME'] =
			CUser::FormatName(
				$arParams['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($user['LOGIN']) ? $user['LOGIN'] : '',
					'NAME' => isset($user['NAME']) ? $user['NAME'] : '',
					'SECOND_NAME' => isset($user['SECOND_NAME']) ? $user['SECOND_NAME'] : '',
					'LAST_NAME' => isset($user['LAST_NAME']) ? $user['LAST_NAME'] : ''
				),
				true, false
			);
		//USER PHOTO
		$userPhotoInfo = isset($user['PERSONAL_PHOTO'])
			? CFile::ResizeImageGet(
				$user['PERSONAL_PHOTO'],
				array('width' => 55, 'height' => 55),
				BX_RESIZE_IMAGE_EXACT
			) : null;
		$arResult['USER_PHOTO_URL'] = is_array($userPhotoInfo) && isset($userPhotoInfo['src'])
			? $userPhotoInfo['src'] : '';
	}

	$arResult['USER_ACTUAL_NAME'] = $arResult['USER_LAST_USED_NAME'] !== ''
		? $arResult['USER_LAST_USED_NAME'] : $arResult['USER_FULL_NAME'];

	$arResult['USER_ACTUAL_EMAIL'] = $arResult['USER_LAST_USED_EMAIL'] !== ''
		? $arResult['USER_LAST_USED_EMAIL']
		: ($arResult['CRM_EMAIL'] != '' ? $arResult['CRM_EMAIL'] : $arResult['USER_EMAIL']);

	$arResult['USER_ACTUAL_ADDRESSER'] = "{$arResult['USER_ACTUAL_NAME']}<{$arResult['USER_ACTUAL_EMAIL']}>";
}

// CONTEXT_ID -->
$contextID = isset($arParams['CONTEXT_ID']) ? $arParams['CONTEXT_ID'] : '';
if($contextID === '' && isset($_REQUEST['context_id']))
{
	$contextID = $_REQUEST['context_id'];
}
if($contextID === '')
{
	$contextID = "{$uid}_{$entityID}";
}
$arResult['CONTEXT_ID'] = $arParams['CONTEXT_ID'] = $contextID;
//<-- CONTEXT_ID

$communicationType = '';
if($typeID === CCrmActivityType::Call)
{
	$communicationType = 'PHONE';
}
elseif($typeID === CCrmActivityType::Email)
{
	$communicationType = 'EMAIL';
}
elseif($typeID === CCrmActivityType::Meeting)
{
	$communicationType = 'PERSON';
}
$arResult['COMMUNICATION_TYPE'] = $communicationType;

$arResult['COMMUNICATION_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['COMMUNICATION_SELECTOR_URL_TEMPLATE'],
	array(
		'context_id' => $contextID,
		'type' => strtolower($communicationType),
		'owner_id' => $ownerID,
		'owner_type' => strtolower(CCrmOwnerType::ResolveName($ownerTypeID))
	)
);

$arResult['DEAL_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['DEAL_SELECTOR_URL_TEMPLATE'],
	array('context_id' => $contextID)
);

$sid = bitrix_sessid();
$serviceURLTemplate = ($arParams["SERVICE_URL_TEMPLATE"]
	? $arParams["SERVICE_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.activity.edit/ajax.php?site_id=#SITE#&sessid=#SID#');
$arResult['SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => $sid)
);

$arResult['USER_EMAIL_CONFIGURATOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['USER_EMAIL_CONFIGURATOR_URL_TEMPLATE']
);

if($typeID === CCrmActivityType::Call)
{
	$this->IncludeComponentTemplate('call');
}
elseif($typeID === CCrmActivityType::Meeting)
{
	$this->IncludeComponentTemplate('meeting');
}
elseif($typeID === CCrmActivityType::Email)
{
	$this->IncludeComponentTemplate('email');
}

