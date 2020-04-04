<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmRequisiteListEndResponse'))
{
	function __CrmRequisiteListEndResponse($result)
	{
		global $APPLICATION;

		$APPLICATION->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');

		die();
	}
}

if (!CModule::IncludeModule('crm'))
{
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	return;
}

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';

if ($action === 'GET_ROW_COUNT')
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
	if(!($gridID !== ''
		&& isset($_SESSION['CRM_GRID_DATA'])
		&& isset($_SESSION['CRM_GRID_DATA'][$gridID])
		&& is_array($_SESSION['CRM_GRID_DATA'][$gridID])))
	{
		__CrmRequisiteListEndResponse(array('DATA' => array('TEXT' => '')));
	}

	$gridData = $_SESSION['CRM_GRID_DATA'][$gridID];
	$filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();

	// check permissions
	$entityTypeId = isset($filter['ENTITY_TYPE_ID']) ? (int)$filter['ENTITY_TYPE_ID'] : 0;
	$entityTypeId = isset($filter['=ENTITY_TYPE_ID']) ? (int)$filter['=ENTITY_TYPE_ID'] : 0;
	$entityId = isset($filter['ENTITY_ID']) ? (int)$filter['ENTITY_ID'] : 0;
	$entityId = isset($filter['=ENTITY_ID']) ? (int)$filter['=ENTITY_ID'] : 0;
	if ($entityTypeId !== \CCrmOwnerType::Company && $entityTypeId !== \CCrmOwnerType::Contact)
	{
		__CrmRequisiteListEndResponse(array('ERROR' => 'Incorrect entity type.'));
	}
	if ($entityTypeId === \CCrmOwnerType::Company)
	{
		if ($entityId <= 0 || !\CCrmCompany::Exists($entityId))
		{
			__CrmRequisiteListEndResponse(array('ERROR' => 'Company not found.'));
		}
		else
		{
			if (!\CCrmCompany::CheckReadPermission($entityId, $userPerms))
				__CrmRequisiteListEndResponse(array('ERROR' => 'Access denied.'));
		}
	}
	else if ($entityTypeId === \CCrmOwnerType::Contact)
	{
		if ($entityId <= 0 || !\CCrmContact::Exists($entityId))
		{
			__CrmRequisiteListEndResponse(array('ERROR' => 'Contact not found.'));
		}
		else
		{
			if (!\CCrmContact::CheckReadPermission($entityId, $userPerms))
				__CrmRequisiteListEndResponse(array('ERROR' => 'Access denied.'));
		}
	}

	$requisite = new \Bitrix\Crm\EntityRequisite();
	$count = $requisite->getCountByFilter(array($filter));
	__CrmRequisiteListEndResponse(
		array('DATA' => array('TEXT' => GetMessage('CRM_REQUISITE_LIST_ROW_COUNT', array('#ROW_COUNT#' => $count))))
	);
}
