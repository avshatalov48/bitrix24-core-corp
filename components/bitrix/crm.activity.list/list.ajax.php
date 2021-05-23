<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('DisableEventsCheck', true);

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
/**
 * AGENTS ARE REQUIRED FOR FOLLOWING ACTIONS:
 * 	REBUILD SEARCH INDEX
 * 	BUILD TIMELINE
 */
define(
	'NO_AGENT_CHECK',
	!in_array($action, array('REBUILD_SEARCH_CONTENT', 'BUILD_TIMELINE'), true)
);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmActivityListEndResponse'))
{
	function __CrmActivityListEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
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
	__CrmActivityListEndResponse(array('ERROR' => 'Could not include crm module.'));
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	__CrmActivityListEndResponse(array('ERROR' => 'Access denied.'));
}

if ($action === 'GET_ROW_COUNT')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmPerms::IsAccessEnabled($userPerms))
	{
		__CrmActivityListEndResponse(array('ERROR' => 'Access denied.'));
	}

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
	if(!($gridID !== ''
		&& isset($_SESSION['CRM_GRID_DATA'])
		&& isset($_SESSION['CRM_GRID_DATA'][$gridID])
		&& is_array($_SESSION['CRM_GRID_DATA'][$gridID])))
	{
		__CrmActivityListEndResponse(array('DATA' => array('TEXT' => '')));
	}

	$gridData = $_SESSION['CRM_GRID_DATA'][$gridID];
	$filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
	$result = CCrmActivity::GetList(array(), $filter, array(), false, array(), array());

	$text = '';
	if(is_numeric($result))
	{
		$text = GetMessage('CRM_ACTIVITY_LIST_ROW_COUNT', array('#ROW_COUNT#' => $result));
		if($text === '')
		{
			$text = $result;
		}
	}
	__CrmActivityListEndResponse(array('DATA' => array('TEXT' => $text)));
}
elseif ($action === 'REBUILD_SEARCH_CONTENT')
{
	$agent = \Bitrix\Crm\Agent\Search\ActivitySearchContentRebuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isActive())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmActivityListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmActivityListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'BUILD_TIMELINE')
{
	$agent = \Bitrix\Crm\Agent\Timeline\ActivityTimelineBuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isActive())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmActivityListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmActivityListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'REBUILD_ACT_STATISTICS')
{
	//~CRM_REBUILD_ACTIVITY_STATISTICS
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmPerms::IsAdmin())
	{
		__CrmActivityListEndResponse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_REBUILD_ACTIVITY_STATISTICS', 'N') !== 'Y')
	{
		__CrmActivityListEndResponse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_ACTIVITY_LIST_REBUILD_ACT_STATISTICS_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_ACTIVITY_STATISTICS_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData, ['allowed_classes' => false]) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = CCrmActivity::GetList(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmActivity::GetList(
		array('ID' => 'ASC'),
		$filter,
		false,
		array('nTopCount' => 20),
		array('ID')
	);

	$itemIDs = array();
	$itemQty = 0;
	if(is_object($dbResult))
	{
		while($fields = $dbResult->Fetch())
		{
			$itemIDs[] = (int)$fields['ID'];
			$itemQty++;
		}
	}

	if($itemQty > 0)
	{
		\Bitrix\Crm\Statistics\ActivityStatisticEntry::rebuild($itemIDs);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_REBUILD_ACTIVITY_STATISTICS_PROGRESS', serialize($progressData));
		__CrmActivityListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_ACTIVITY_LIST_REBUILD_ACT_STATISTICS_PROGRESS_SUMMARY',
					array(
						'#PROCESSED_ITEMS#' => $processedItemQty,
						'#TOTAL_ITEMS#' => $totalItemQty
					)
				)
			)
		);
	}
	else
	{
		COption::RemoveOption('crm', '~CRM_REBUILD_ACTIVITY_STATISTICS');
		COption::RemoveOption('crm', '~CRM_REBUILD_ACTIVITY_STATISTICS_PROGRESS');
		__CrmActivityListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_ACTIVITY_LIST_REBUILD_ACT_STATISTICS_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
?>