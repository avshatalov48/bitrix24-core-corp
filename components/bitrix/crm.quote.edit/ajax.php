<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}
/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'ENABLE_SONET_SUBSCRIPTION'
 * 'GET_WEBDAV_ELEMENT_INFO'
 * 'GET_SECONDARY_ENTITY_INFOS'
 */
global $DB, $APPLICATION;
if(!function_exists('__CrmQuotelEditEndJsonResonse'))
{
	function __CrmQuotelEditEndJsonResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

if (!CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
$APPLICATION->RestartBuffer();
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if($action == '')
{
	__CrmQuotelEditEndJsonResonse(array('ERROR' => 'INVALID DATA!'));
}

if($action === 'ENABLE_SONET_SUBSCRIPTION')
{
	$userID = CCrmSecurityHelper::GetCurrentUserID();
	$entityTypeName = isset($_POST['ENTITY_TYPE'])? mb_strtoupper($_POST['ENTITY_TYPE']) : '';
	$entityID = isset($_POST['ENTITY_ID']) ? intval($_POST['ENTITY_ID']) : 0;
	if($userID > 0 && $entityTypeName === CCrmOwnerType::QuoteName && $entityID > 0 && CCrmQuote::CheckReadPermission($entityID))
	{

		$isEnabled = CCrmSonetSubscription::IsRelationRegistered(
			CCrmOwnerType::Quote,
			$entityID,
			CCrmSonetSubscriptionType::Observation,
			$userID
		);

		$enable = isset($_POST['ENABLE']) && mb_strtoupper($_POST['ENABLE']) === 'Y' ;

		if ($isEnabled !== $enable && \Bitrix\Crm\Settings\Crm::isLiveFeedRecordsGenerationEnabled())
		{
			if ($enable)
			{
				CCrmSonetSubscription::RegisterSubscription(CCrmOwnerType::Quote, $entityID, CCrmSonetSubscriptionType::Observation, $userID);
			}
			else
			{
				CCrmSonetSubscription::UnRegisterSubscription(CCrmOwnerType::Quote, $entityID, CCrmSonetSubscriptionType::Observation, $userID);
			}
		}
	}
}
elseif($action == 'GET_WEBDAV_ELEMENT_INFO')
{
	$elementID = isset($_POST['ELEMENT_ID']) ? intval($_POST['ELEMENT_ID']) : 0;

	if($elementID <= 0)
	{
		__CrmQuotelEditEndJsonResonse(array('ERROR' => 'Invalid data'));
	}

	__CrmQuotelEditEndJsonResonse(
		array(
			'DATA' => array(
				'ELEMENT_ID' => $elementID,
				'INFO' => \Bitrix\Crm\Integration\StorageManager::getFileInfo(
					$elementID,
					\Bitrix\Crm\Integration\StorageType::WebDav
				)
			)
		)
	);
}
elseif($action === 'GET_SECONDARY_ENTITY_INFOS')
{
	$userID = CCrmSecurityHelper::GetCurrentUserID();
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	if($userID <= 0 || !CCrmQuote::CheckReadPermission(0, $userPermissions))
	{
		__CrmQuotelEditEndJsonResonse(array('ERROR' => 'Access denied.'));
	}


	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();

	$ownerTypeName = isset($params['OWNER_TYPE_NAME']) ? $params['OWNER_TYPE_NAME'] : '';
	if($ownerTypeName === '')
	{
		__CrmQuotelEditEndJsonResonse(array('ERROR' => 'Owner type is not specified.'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if($ownerTypeID === CCrmOwnerType::Undefined)
	{
		__CrmQuotelEditEndJsonResonse(array('ERROR' => 'Undefined owner type is specified.'));
	}

	if($ownerTypeID !== CCrmOwnerType::Quote)
	{
		$typeDescr = CCrmOwnerType::GetDescription($ownerTypeID);
		__CrmQuotelEditEndJsonResonse(array('ERROR' => "Type '{$typeDescr}' is not supported in current context."));
	}

	$primaryTypeName = isset($params['PRIMARY_TYPE_NAME']) ? $params['PRIMARY_TYPE_NAME'] : '';
	if($primaryTypeName === '')
	{
		__CrmQuotelEditEndJsonResonse(array('ERROR' => 'Primary type is not specified.'));
	}

	$primaryTypeID = CCrmOwnerType::ResolveID($primaryTypeName);
	if($primaryTypeID !== CCrmOwnerType::Company)
	{
		__CrmQuotelEditEndJsonResonse(array('ERROR' => 'Primary type is not supported in current context.'));
	}

	$primaryID = isset($params['PRIMARY_ID']) ? (int)$params['PRIMARY_ID'] : 0;
	if($primaryID <= 0)
	{
		__CrmQuotelEditEndJsonResonse(array('ERROR' => 'Primary ID is not specified.'));
	}

	$secondaryTypeName = isset($params['SECONDARY_TYPE_NAME']) ? $params['SECONDARY_TYPE_NAME'] : '';
	if($secondaryTypeName === '')
	{
		__CrmQuotelEditEndJsonResonse(array('ERROR' => 'Secondary type is not specified.'));
	}

	$secondaryTypeID = CCrmOwnerType::ResolveID($secondaryTypeName);
	if($secondaryTypeID !== CCrmOwnerType::Contact)
	{
		__CrmQuotelEditEndJsonResonse(array('ERROR' => 'Secondary type is not supported in current context.'));
	}

	$dbResult = CCrmQuote::GetList(
		array('ID' => 'DESC'),
		array(
			'=COMPANY_ID' => $primaryID,
			'=ASSIGNED_BY_ID' => $userID,
			'CHECK_PERMISSIONS' => 'N'
		),
		false,
		array('nTopCount' => 5),
		array('ID')
	);

	$ownerIDs = array();
	while($ary = $dbResult->Fetch())
	{
		$ownerIDs[] = (int)$ary['ID'];
	}

	$secondaryIDs = array();
	foreach($ownerIDs as $ownerID)
	{
		$entityIDs = \Bitrix\Crm\Binding\QuoteContactTable::getQuoteContactIDs($ownerID);
		foreach($entityIDs as $entityID)
		{
			if(CCrmContact::CheckReadPermission($entityID, $userPermissions))
			{
				$secondaryIDs[] = $entityID;
			}
		}

		if(!empty($secondaryIDs))
		{
			break;
		}
	}

	if(empty($secondaryIDs))
	{
		$secondaryIDs = \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($primaryID);
	}

	$secondaryInfos = array();
	foreach($secondaryIDs as $entityID)
	{
		if(!CCrmContact::CheckReadPermission($entityID, $userPermissions))
		{
			continue;
		}

		$secondaryInfos[]  = CCrmEntitySelectorHelper::PrepareEntityInfo(
			CCrmOwnerType::ContactName,
			$entityID,
			array(
				'ENTITY_EDITOR_FORMAT' => true,
				'REQUIRE_REQUISITE_DATA' => true,
				'REQUIRE_MULTIFIELDS' => true,
				'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
			)
		);
	}

	__CrmQuotelEditEndJsonResonse(array('ENTITY_INFOS' => $secondaryInfos));
}
__CrmQuotelEditEndJsonResonse(array());
