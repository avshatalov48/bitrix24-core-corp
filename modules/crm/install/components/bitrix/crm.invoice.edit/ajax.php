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
 * 'GET_DEFAULT_SECONDARY_ENTITIES'
 */
global $DB, $APPLICATION;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
if(!function_exists('__CrmInvoicelEditEndJsonResonse'))
{
	function __CrmInvoicelEditEndJsonResonse($result)
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


CUtil::JSPostUnescape();
$APPLICATION->RestartBuffer();
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if($action === '')
{
	__CrmInvoicelEditEndJsonResonse(array('ERROR'=>'ACTION IS NOT DEFINED!'));
}

if($action === 'GET_SECONDARY_ENTITY_INFOS')
{
	$userID = CCrmSecurityHelper::GetCurrentUserID();
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	if($userID <= 0 || !CCrmInvoice::CheckReadPermission(0, $userPermissions))
	{
		__CrmInvoicelEditEndJsonResonse(array('ERROR' => 'Access denied.'));
	}


	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();

	$ownerTypeName = isset($params['OWNER_TYPE_NAME']) ? $params['OWNER_TYPE_NAME'] : '';
	if($ownerTypeName === '')
	{
		__CrmInvoicelEditEndJsonResonse(array('ERROR' => 'Owner type is not specified.'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if($ownerTypeID === CCrmOwnerType::Undefined)
	{
		__CrmInvoicelEditEndJsonResonse(array('ERROR' => 'Undefined owner type is specified.'));
	}

	if($ownerTypeID !== CCrmOwnerType::Invoice)
	{
		$typeDescr = CCrmOwnerType::GetDescription($ownerTypeID);
		__CrmInvoicelEditEndJsonResonse(array('ERROR' => "Type '{$typeDescr}' is not supported in current context."));
	}

	$primaryTypeName = isset($params['PRIMARY_TYPE_NAME']) ? $params['PRIMARY_TYPE_NAME'] : '';
	if($primaryTypeName === '')
	{
		__CrmInvoicelEditEndJsonResonse(array('ERROR' => 'Primary type is not specified.'));
	}

	$primaryTypeID = CCrmOwnerType::ResolveID($primaryTypeName);
	if($primaryTypeID !== CCrmOwnerType::Company)
	{
		__CrmInvoicelEditEndJsonResonse(array('ERROR' => 'Primary type is not supported in current context.'));
	}

	$primaryID = isset($params['PRIMARY_ID']) ? (int)$params['PRIMARY_ID'] : 0;
	if($primaryID <= 0)
	{
		__CrmInvoicelEditEndJsonResonse(array('ERROR' => 'Primary ID is not specified.'));
	}

	$secondaryTypeName = isset($params['SECONDARY_TYPE_NAME']) ? $params['SECONDARY_TYPE_NAME'] : '';
	if($secondaryTypeName === '')
	{
		__CrmInvoicelEditEndJsonResonse(array('ERROR' => 'Secondary type is not specified.'));
	}

	$secondaryTypeID = CCrmOwnerType::ResolveID($secondaryTypeName);
	if($secondaryTypeID !== CCrmOwnerType::Contact)
	{
		__CrmInvoicelEditEndJsonResonse(array('ERROR' => 'Secondary type is not supported in current context.'));
	}

	$dbResult = CCrmInvoice::GetList(
		array('ID' => 'DESC'),
		array(
			'=UF_COMPANY_ID' => $primaryID,
			'>UF_CONTACT_ID' => 0,
			'=RESPONSIBLE_ID' => $userID,
			'CHECK_PERMISSIONS' => 'N'
		),
		false,
		array('nTopCount' => 5),
		array('ID', 'UF_CONTACT_ID')
	);

	$secondaryIDs = array();
	while($ary = $dbResult->Fetch())
	{
		$secondaryIDs[] = (int)$ary['UF_CONTACT_ID'];
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

	__CrmInvoicelEditEndJsonResonse(array('ENTITY_INFOS' => $secondaryInfos));
}
