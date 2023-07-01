<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Text\Emoji;

/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

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

$arParams['CACHE_TIME'] = (int)($arParams['CACHE_TIME'] ?? 3600);

$arResult['POST_FORM_URI'] = isset($arParams['POST_FORM_URI']) ? $arParams['POST_FORM_URI'] : '';

$entityTypeID = isset($arParams['ENTITY_TYPE_ID']) ? intval($arParams['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
if(CCrmOwnerType::IsDefined($entityTypeID))
{
	$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
}
else
{
	$entityTypeName = isset($arParams['ENTITY_TYPE_NAME']) ? $arParams['ENTITY_TYPE_NAME'] : '';
	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);

}
$entityID = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;

$arResult['ENABLE_LIVE_FEED_EXTENDED_MODE'] = true;
$arResult['LIVE_FEED_ENTITY_TYPE'] = CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
$arResult['ENTITY_TYPE_NAME'] = $entityTypeName;
$arResult['ENTITY_TYPE_ID'] = $entityTypeID;
$arResult['ENTITY_ID'] = $entityID;

$permissionEntityType = isset($arParams['PERMISSION_ENTITY_TYPE']) ? $arParams['PERMISSION_ENTITY_TYPE'] : '';
if($permissionEntityType === '')
{
	$permissionEntityType = $entityTypeName;
}

$userID = CCrmSecurityHelper::GetCurrentUserID();
$arResult['USER_ID'] = $userID;

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if($entityTypeName !== '' && !CCrmAuthorizationHelper::CheckUpdatePermission($permissionEntityType, $entityID, $userPerms))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}
$arResult['PERMISSIONS'] = $userPerms;

$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'crm_sl_event_edit';
}
$arResult['UID'] =$arParams['UID'] = $uid;
$arResult['MESSAGE_TITLE_FIELD_NAME'] = $arParams['UID'] = $uid;
$cacheManager = $GLOBALS['CACHE_MANAGER'];

$allowLiveFeedToAll = $defaultLiveFeedToAll = false;

$arParams['USE_CUT'] = isset($arParams['USE_CUT']) && $arParams['USE_CUT'] === 'Y' ? 'Y' : 'N';
$arParams["POST_PROPERTY"] = (is_array($arParams["POST_PROPERTY"]) ? $arParams["POST_PROPERTY"] : array($arParams["POST_PROPERTY"]));
$arParams["POST_PROPERTY"][] = "UF_SONET_LOG_URL_PRV";

global $USER_FIELD_MANAGER;
$sonetFields = $USER_FIELD_MANAGER->GetUserFields('SONET_LOG', 0);
$arResult['WEB_DAV_FILE_FIELD_NAME'] = 'UF_SONET_LOG_DOC';
$arResult['WEB_DAV_FILE_FIELD'] = isset($sonetFields['UF_SONET_LOG_DOC']) ? $sonetFields['UF_SONET_LOG_DOC'] : null;

$arResult["POST_PROPERTIES"] = array("SHOW" => "N", "DATA" => array());

$arResult['EVENT'] = array('MESSAGE'=> '', 'TITLE'=> '');
$arResult['ENTITY_DATA'] = array();
$arResult['ENABLE_TITLE'] = false;
$arResult['FEED_DESTINATION'] = array(
	'LAST' => array(
		'CONTACTS' => array(),
		'COMPANIES' => array(),
		'LEADS' => array(),
		'DEALS' => array(),
		'CRMEMAILS' => array()
	),
	'SELECTED' => array()
);

$arResult['ERROR_MESSAGES'] = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	if(isset($_POST['save']) && $_POST['save'] === 'Y')
	{
		$res = CCrmLiveFeedComponent::ProcessLogEventEditPOST($_POST, $entityTypeID, $entityID, $arResult, $arParams["POST_PROPERTY"]);
		if(
			!is_array($res)
			&& intval($res) > 0
		)
		{
			LocalRedirect($APPLICATION->GetCurPage());
		}
		else
		{
			foreach($res as $error)
			{
				$arResult['ERROR_MESSAGES'][] = $error;
			}
		}
	}
}

foreach ($arParams["POST_PROPERTY"] as $FIELD_NAME)
{
	$arPostField = $sonetFields[$FIELD_NAME];
	if (!!$arPostField)
	{
		if (
			!empty($arResult["ERROR_MESSAGES"])
			&& !empty($_POST[$FIELD_NAME])
		)
		{
			$arPostField["VALUE"] = $_POST[$FIELD_NAME];
		}

		$arPostField["~EDIT_FORM_LABEL"] = ($arPostField["EDIT_FORM_LABEL"] !== "" ? $arPostField["EDIT_FORM_LABEL"] : $arPostField["FIELD_NAME"]);
		$arPostField["EDIT_FORM_LABEL"] = htmlspecialcharsEx($arPostField["~EDIT_FORM_LABEL"]);
		$arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME] = $arPostField;
		$arResult["POST_PROPERTIES"]["SHOW"] = "Y";
	}
}

// ALLOWED SONET GROUPS -->
$isManagedCacheEnabled = defined('BX_COMP_MANAGED_CACHE');
$cacheTtl = $isManagedCacheEnabled ? 3153600 : 3600 * 4;
$eventDestCacheID = 'crm_sl_event_dest_'.SITE_ID.'_'.$userID;
$eventDestCacheDir = '/crm/sl_event/dest/'.SITE_ID.'/'.$userID;
$cache = new CPHPCache;
if($cache->InitCache($arParams['CACHE_TIME'], $eventDestCacheID, $eventDestCacheDir))
{
	$arResult['FEED_DESTINATION']['SONETGROUPS'] = $cache->GetVars();
}
else
{
	$cache->StartDataCache();
	$arResult['FEED_DESTINATION']['SONETGROUPS'] = CSocNetLogDestination::GetSocnetGroup();
	if($isManagedCacheEnabled)
	{
		$cacheManager->StartTagCache($eventDestCacheDir);
		foreach($arResult['FEED_DESTINATION']['SONETGROUPS'] as $val)
		{
			$cacheManager->RegisterTag("sonet_features_G_{$val['entityId']}");
			$cacheManager->RegisterTag("sonet_group_{$val['entityId']}");
		}
		$cacheManager->RegisterTag("sonet_user2group_U{$userID}");
		$cacheManager->EndTagCache();
	}
	$cache->EndDataCache($arResult['FEED_DESTINATION']['SONETGROUPS']);
}

// <-- ALLOWED SONET GROUPS

if(CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
{
	foreach($arResult['FEED_DESTINATION']['SONETGROUPS'] as $k => $val)
	{
		$arResult['FEED_DESTINATION']['SELECTED'][$k] = 'sonetgroups';
	}

	if(empty($arResult['FEED_DESTINATION']['SELECTED']))
	{
		ShowError(GetMessage('CRM_SL_EVENT_NOT_AVAIBLE'));
		return;
	}
}
elseif($defaultLiveFeedToAll)
{
	$arResult['FEED_DESTINATION']['SELECTED']['UA'] = 'groups';
}

$arResult['FEED_DESTINATION']['HIDDEN_GROUPS'] = array();
$hiddenGroups = array();
if(!empty($arResult['FEED_DESTINATION']['SELECTED']))
{
	foreach($arResult['FEED_DESTINATION']['SELECTED'] as $groupID => $value)
	{
		if($value === 'sonetgroups' && empty($arResult['FEED_DESTINATION']['SONETGROUPS'][$groupID]))
		{
			$hiddenGroups[] = mb_substr($groupID, 2);
		}
	}
}

if(!empty($hiddenGroups))
{
	$rsGroup = CSocNetGroup::GetList(
		array(),
		array('ID' => $hiddenGroups),
		false,
		false,
		array('ID', 'NAME')
	);
	while($arGroup = $rsGroup->Fetch())
	{
		$arResult['FEED_DESTINATION']['HIDDEN_GROUPS'][$arGroup['ID']] = array('ID' => $arGroup['ID'], 'NAME' => Emoji::decode($arGroup['NAME']));
	}

	if(!CSocNetUser::IsCurrentUserModuleAdmin() && $userID > 0)
	{
		$arGroupID = array();
		if(!empty($GLOBALS['SONET_GROUPS_ID_AVAILABLE']))
			$arGroupID = $GLOBALS['SONET_GROUPS_ID_AVAILABLE'];
		else
		{
			// get tagged cached available groups and intersect
			$cache = new CPHPCache;
			$groupCacheID = $userID;
			$groupCacheDir = "/sonet/groups_available/{$userID}/";

			if($cache->InitCache($arParams['CACHE_TIME'], $groupCacheID, $groupCacheDir))
			{
				$arCacheVars = $cache->GetVars();
				$arGroupID = $arCacheVars['arGroupID'];
			}
			else
			{
				$cache->StartDataCache($arParams['CACHE_TIME'], $groupCacheID, $groupCacheDir);
				if($isManagedCacheEnabled)
				{
					$cacheManager->StartTagCache($groupCacheDir);
					$cacheManager->RegisterTag("sonet_user2group_U{$userID}");
					$cacheManager->RegisterTag('sonet_group');
				}

				$rsGroup = CSocNetGroup::GetList(
					array(),
					array('CHECK_PERMISSIONS' => $userID),
					false,
					false,
					array('ID')
				);
				while($arGroup = $rsGroup->Fetch())
					$arGroupID[] = $arGroup['ID'];

				$arCacheData = array(
					'arGroupID' => $arGroupID
				);
				if($isManagedCacheEnabled)
					$cacheManager->EndTagCache();
				$cache->EndDataCache($arCacheData);
			}

			$GLOBALS['SONET_GROUPS_ID_AVAILABLE'] = $arGroupID;
		}

		foreach($arResult['FEED_DESTINATION']['HIDDEN_GROUPS'] as $group_code => $arBlogSPerm)
		{
			if(!in_array($group_code, $arGroupID))
			{
				$arResult['FEED_DESTINATION']['HIDDEN_GROUPS'][$group_code]['NAME'] = GetMessage('CRM_SL_EVENT_EDIT_HIDDEN_GROUP');
			}
		}
	}
}

// intranet structure
$arStructure = CSocNetLogDestination::GetStucture(array('LAZY_LOAD' => true));
$arResult['FEED_DESTINATION']['DEPARTMENT'] = $arStructure['department'];
$arResult['FEED_DESTINATION']['DEPARTMENT_RELATION'] = $arStructure['department_relation'];
$arResult['FEED_DESTINATION']['DEPARTMENT_RELATION_HEAD'] = $arStructure['department_relation_head'];

// users

if(CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
{
	$arResult['FEED_DESTINATION']['EXTRANET_USER'] = 'Y';
	$arResult['FEED_DESTINATION']['USERS'] = CSocNetLogDestination::GetExtranetUser();
}
else
{
	$arResult['FEED_DESTINATION']['EXTRANET_USER'] = 'N';
}

$dataAdditional = array();
$arResult['FEED_DESTINATION']['DEST_SORT'] = CSocNetLogDestination::getDestinationSort(array(
	"DEST_CONTEXT" => "CRM_POST",
	"ALLOW_EMAIL_INVITATION" => true
), $dataAdditional);

CSocNetLogDestination::fillLastDestination(
	$arResult['FEED_DESTINATION']['DEST_SORT'],
	$arResult['FEED_DESTINATION']['LAST'],
	array(
		"CRM" => "Y",
		"CRMEMAILS" => "Y",
		"DATA_ADDITIONAL" => $dataAdditional
	)
);

$arDestUser = array();
if(!empty($arResult["FEED_DESTINATION"]['LAST']['USERS']))
{
	foreach ($arResult["FEED_DESTINATION"]['LAST']['USERS'] as $value)
	{
		$arDestUser[] = str_replace('U', '', $value);
	}
}

if(!empty($arResult['FEED_DESTINATION']['SELECTED']))
{
	foreach ($arResult['FEED_DESTINATION']['SELECTED'] as $key => $value)
	{
		if (preg_match('/^U(\d+)$/', $key, $matches))
		{
			$arDestUser[] = intval($matches[1]);
		}
	}
}

$arResult["FEED_DESTINATION"]['USERS'] = CSocNetLogDestination::GetUsers(array(
	'id' => $arDestUser,
	'CRM_ENTITY' => 'Y'
));
CSocNetLogDestination::fillCrmEmails($arResult["FEED_DESTINATION"]);

$lastContactIds = array();
$lastCompanyIds = array();
$lastLeadIds = array();
$lastDealIds = array();

$arLastContacts = array();
$arLastCompanies = array();
$arLastLeads = array();
$arLastDeals = array();

// get last contacts
if (!empty($arResult['FEED_DESTINATION']['LAST']['CONTACTS']))
{
	foreach ($arResult['FEED_DESTINATION']['LAST']['CONTACTS'] as $key => $val)
	{
		if (preg_match('/^CRMCONTACT(\d+)$/i', $val, $matches))
		{
			$lastContactIds[] = $matches[1];
		}
	}
}

if (!empty($lastContactIds))
{
	$dbLastContacts = CCrmContact::GetListEx(
		$arOrder = array(),
		$arFilter = array('ID' => $lastContactIds, '@CATEGORY_ID' => 0,),
		$arGroupBy = false,
		$arNavStartParams = array('nTopCount' => 20),
		$arSelectFields = array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'DATE_CREATE')
	);

	while ($arContact = $dbLastContacts->fetch())
	{
		$arLastContacts['CRMCONTACT'.$arContact['ID']] = prepareCrmEntity('contact', $arContact);
	}
}

// get last companies
if (!empty($arResult['FEED_DESTINATION']['LAST']['COMPANIES']))
{
	foreach ($arResult['FEED_DESTINATION']['LAST']['COMPANIES'] as $key => $val)
	{
		if (preg_match('/^CRMCOMPANY(\d+)$/i', $val, $matches))
		{
			$lastCompanyIds[] = $matches[1];
		}
	}
}

if (!empty($lastCompanyIds))
{
	$dbLastCompanies = CCrmCompany::GetListEx(
		$arOrder = array(),
		$arFilter = array('ID' => $lastCompanyIds, '@CATEGORY_ID' => 0,),
		$arGroupBy = false,
		$arNavStartParams = array('nTopCount' => 20),
		$arSelectFields = array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO', 'DATE_CREATE')
	);

	while ($arCompany = $dbLastCompanies->fetch())
	{
		$arLastCompanies['CRMCOMPANY'.$arCompany['ID']] = prepareCrmEntity('company', $arCompany);
	}
}

// get last leads
if (!empty($arResult['FEED_DESTINATION']['LAST']['LEADS']))
{
	foreach ($arResult['FEED_DESTINATION']['LAST']['LEADS'] as $key => $val)
	{
		if (preg_match('/^CRMLEAD(\d+)$/i', $val, $matches))
		{
			$lastLeadIds[] = $matches[1];
		}
	}
}

if (!empty($lastLeadIds))
{
	$dbLastLeads = CCrmLead::GetListEx(
		$arOrder = array(),
		$arFilter = array('ID' => $lastLeadIds),
		$arGroupBy = false,
		$arNavStartParams = array('nTopCount' => 20),
		$arSelectFields = array('ID', 'TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID', 'DATE_CREATE')
	);

	while ($arLead = $dbLastLeads->fetch())
	{
		$arLastLeads['CRMLEAD'.$arLead['ID']] = prepareCrmEntity('lead', $arLead);
	}
}

// get last deals
if (!empty($arResult['FEED_DESTINATION']['LAST']['DEALS']))
{
	foreach ($arResult['FEED_DESTINATION']['LAST']['DEALS'] as $key => $val)
	{
		if (preg_match('/^CRMDEAL(\d+)$/i', $val, $matches))
		{
			$lastDealIds[] = $matches[1];
		}
	}
}

if (!empty($lastDealIds))
{
	$dbLastDeals = CCrmDeal::GetListEx(
		$arOrder = array(),
		$arFilter = array('ID' => $lastDealIds),
		$arGroupBy = false,
		$arNavStartParams = array('nTopCount' => 20),
		$arSelectFields = array('ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME', 'DATE_CREATE')
	);

	while ($arDeal = $dbLastDeals->fetch())
	{
		$arLastDeals['CRMDEAL'.$arDeal['ID']] = prepareCrmEntity('deal', $arDeal);
	}
}

function prepareCrmEntity($type, $data)
{
	static $arCompanyTypeList, $arCompanyIndustryList;

	if(is_null($arCompanyTypeList))
	{
		$arCompanyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
	}

	if(is_null($arCompanyIndustryList))
	{
		$arCompanyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');
	}

	switch ($type)
	{
		case 'contact':
			$result = array(
				'id'         => 'CRMCONTACT'.$data['ID'],
				'entityType' => 'contacts',
				'entityId'   => $data['ID'],
				'name'       => htmlspecialcharsbx(
					CCrmContact::PrepareFormattedName(
						array(
							'HONORIFIC'   => isset($data['HONORIFIC']) ? $data['HONORIFIC'] : '',
							'NAME'        => isset($data['NAME']) ? $data['NAME'] : '',
							'SECOND_NAME' => isset($data['SECOND_NAME']) ? $data['SECOND_NAME'] : '',
							'LAST_NAME'   => isset($data['LAST_NAME']) ? $data['LAST_NAME'] : ''
						)
					)
				),
				'desc' => htmlspecialcharsbx($data['COMPANY_TITLE'])
			);
			if (array_key_exists('DATE_CREATE', $data))
				$result['date'] = MakeTimeStamp($data['DATE_CREATE']);
			if (!empty($data['PHOTO']) && intval($data['PHOTO']) > 0)
			{
				$arImg = CFile::ResizeImageGet($data['PHOTO'], array('width' => 100, 'height' => 100), BX_RESIZE_IMAGE_EXACT);
				$result['avatar'] = $arImg['src'];
			}
			break;
		case 'company':
			$arDesc = Array();
			if (isset($arCompanyTypeList[$data['COMPANY_TYPE']]))
				$arDesc[] = $arCompanyTypeList[$data['COMPANY_TYPE']];
			if (isset($arCompanyIndustryList[$data['INDUSTRY']]))
				$arDesc[] = $arCompanyIndustryList[$data['INDUSTRY']];

			$result = array(
				'id'         => 'CRMCOMPANY'.$data['ID'],
				'entityId'   => $data['ID'],
				'entityType' => 'companies',
				'name'       => htmlspecialcharsbx(str_replace(array(';', ','), ' ', $data['TITLE'])),
				'desc'       => htmlspecialcharsbx(implode(', ', $arDesc))
			);
			if (array_key_exists('DATE_CREATE', $data))
				$result['date'] = MakeTimeStamp($data['DATE_CREATE']);

			if (!empty($data['LOGO']) && intval($data['LOGO']) > 0)
			{
				$arImg = CFile::ResizeImageGet($data['LOGO'], array('width' => 100, 'height' => 100), BX_RESIZE_IMAGE_EXACT);
				$result['avatar'] = $arImg['src'];
			}
			break;
		case 'lead':
			$result = array(
				'id'         => 'CRMLEAD'.$data['ID'],
				'entityId'   => $data['ID'],
				'entityType' => 'leads',
				'name'       => htmlspecialcharsbx($data['TITLE']),
				'desc'       => htmlspecialcharsbx(
					CCrmLead::PrepareFormattedName(
						array(
							'HONORIFIC'   => isset($data['HONORIFIC']) ? $data['HONORIFIC'] : '',
							'NAME'        => isset($data['NAME']) ? $data['NAME'] : '',
							'SECOND_NAME' => isset($data['SECOND_NAME']) ? $data['SECOND_NAME'] : '',
							'LAST_NAME'   => isset($data['LAST_NAME']) ? $data['LAST_NAME'] : ''
						)
					)
				)
			);
			if (array_key_exists('DATE_CREATE', $data))
				$result['date'] = MakeTimeStamp($data['DATE_CREATE']);
			break;
		case 'deal':
			$arDesc = array();
			if ($data['COMPANY_TITLE'] != '')
				$arDesc[] = $data['COMPANY_TITLE'];

			$arDesc[] = CCrmContact::PrepareFormattedName(
				array(
					'HONORIFIC'   => isset($data['CONTACT_HONORIFIC']) ? $data['CONTACT_HONORIFIC'] : '',
					'NAME'        => isset($data['CONTACT_NAME']) ? $data['CONTACT_NAME'] : '',
					'SECOND_NAME' => isset($data['CONTACT_SECOND_NAME']) ? $data['CONTACT_SECOND_NAME'] : '',
					'LAST_NAME'   => isset($data['CONTACT_LAST_NAME']) ? $data['CONTACT_LAST_NAME'] : ''
				)
			);

			$result = array(
				'id'         => 'CRMDEAL'.$data['ID'],
				'entityId'   => $data['ID'],
				'entityType' => 'deals',
				'name'       => htmlspecialcharsbx($data['TITLE']),
				'desc'       => htmlspecialcharsbx(implode(', ', $arDesc))
			);
			if (array_key_exists('DATE_CREATE', $data))
				$result['date'] = MakeTimeStamp($data['DATE_CREATE']);
			break;
	}

	return $result;
}

$arResult['FEED_DESTINATION']['CONTACTS'] = $arLastContacts;
$arResult['FEED_DESTINATION']['COMPANIES'] = $arLastCompanies;
$arResult['FEED_DESTINATION']['LEADS'] = $arLastLeads;
$arResult['FEED_DESTINATION']['DEALS'] = $arLastDeals;

if (empty($arResult['FEED_DESTINATION']['LAST']['CONTACTS']))
{
	$arResult['FEED_DESTINATION']['LAST']['CONTACTS'] = array();
	foreach ($arLastContacts as $key => $value)
	{
		$arResult['FEED_DESTINATION']['LAST']['CONTACTS'][$key] = $key;
	}
}

if (empty($arResult['FEED_DESTINATION']['LAST']['COMPANIES']))
{
	$arResult['FEED_DESTINATION']['LAST']['COMPANIES'] = array();
	foreach ($arLastCompanies as $key => $value)
	{
		$arResult['FEED_DESTINATION']['LAST']['COMPANIES'][$key] = $key;
	}
}

if (empty($arResult['FEED_DESTINATION']['LAST']['LEADS']))
{
	$arResult['FEED_DESTINATION']['LAST']['LEADS'] = array();
	foreach ($arLastLeads as $key => $value)
	{
		$arResult['FEED_DESTINATION']['LAST']['LEADS'][$key] = $key;
	}
}

if (empty($arResult['FEED_DESTINATION']['LAST']['DEALS']))
{
	$arResult['FEED_DESTINATION']['LAST']['DEALS'] = array();
	foreach ($arLastDeals as $key => $value)
	{
		$arResult['FEED_DESTINATION']['LAST']['DEALS'][$key] = $key;
	}
}

$arResult['FEED_DESTINATION']['LAST']['CRM'] = array_merge(
	$arResult['FEED_DESTINATION']['LAST']['CONTACTS'],
	$arResult['FEED_DESTINATION']['LAST']['COMPANIES'],
	$arResult['FEED_DESTINATION']['LAST']['LEADS'],
	$arResult['FEED_DESTINATION']['LAST']['DEALS']
);

$types = array(
	'CONTACT' => 'contacts',
	'COMPANY' => 'companies',
	'LEAD' => 'leads',
	'DEAL' => 'deals'
);

if(!empty($arResult['ENTITY_DATA']))
{
	foreach($arResult['ENTITY_DATA'] as &$entityData)
	{
		$entityTypeName = CCrmOwnerType::ResolveName(CCrmLiveFeedEntity::ResolveEntityTypeID($entityData['ENTITY_TYPE']));
		$entityID = $entityData['ENTITY_ID'];
		$arResult['FEED_DESTINATION']['SELECTED']['CRM'.$entityTypeName.$entityID] = $types[$entityTypeName];
	}
	unset($entityData);
}

if ($entityTypeName && $entityID)
{
	if (isset($types[$entityTypeName]))
	{
		if(empty($arResult['ENTITY_DATA']))
		{
			$arResult['FEED_DESTINATION']['SELECTED']['CRM'.$entityTypeName.$entityID] = $types[$entityTypeName];
		}

		if (!isset($arResult['FEED_DESTINATION'][mb_strtoupper($types[$entityTypeName])]['CRM'.$entityTypeName.$entityID]))
		{
			switch ($entityTypeName)
			{
				case 'CONTACT':
					$dbEntity = CCrmContact::GetListEx(
						array(), array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'), false, false,
						array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO')
					);
					break;
				case 'COMPANY':
					$dbEntity = CCrmCompany::GetListEx(
						array(), array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'), false, false,
						array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO')
					);
					break;
				case 'LEAD':
					$dbEntity = CCrmLead::GetListEx(
						array(), array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'), false, false,
						array('ID', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID')
					);
					break;
				case 'DEAL':
					$dbEntity = CCrmDeal::GetListEx(
						array(), array('ID' => $entityID, 'CHECK_PERMISSIONS' => 'N'), false, false,
						array('ID', 'TITLE', 'STAGE_ID')
					);
					break;
				default:
					$dbEntity = null;
			}

			if ($dbEntity && ($arEntity = $dbEntity->fetch()))
				$arResult['FEED_DESTINATION'][mb_strtoupper($types[$entityTypeName])]['CRM'.$entityTypeName.$arEntity['ID']] = prepareCrmEntity(mb_strtolower($entityTypeName), $arEntity);
		}
	}
}

$arResult['FEED_DESTINATION']['DENY_TOALL'] = !$allowLiveFeedToAll;

$this->IncludeComponentTemplate();

