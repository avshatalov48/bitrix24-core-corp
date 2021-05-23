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
	!in_array($action, array('REBUILD_SEARCH_CONTENT', 'BUILD_TIMELINE', 'BUILD_RECURRING_TIMELINE', 'REFRESH_ACCOUNTING'), true)
);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmDealListEndResonse'))
{
	function __CrmDealListEndResonse($result)
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
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	return;
}

global $APPLICATION;

if (isset($_REQUEST['MODE']) && $_REQUEST['MODE'] === 'SEARCH')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmDeal::CheckReadPermission(0, $userPerms))
	{
		__CrmDealListEndResonse(array('ERROR' => 'Access denied.'));
	}

	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();

	// Limit count of items to be found
	$nPageTop = 50;		// 50 items by default
	if (isset($_REQUEST['LIMIT_COUNT']) && ($_REQUEST['LIMIT_COUNT'] >= 0))
	{
		$rawNPageTop = (int) $_REQUEST['LIMIT_COUNT'];
		if ($rawNPageTop === 0)
			$nPageTop = false;		// don't limit
		elseif ($rawNPageTop > 0)
			$nPageTop = $rawNPageTop;
	}

	$arData = array();
	$search = trim($_REQUEST['VALUE']);
	if (!empty($search))
	{
		$multi = isset($_REQUEST['MULTI']) && $_REQUEST['MULTI'] == 'Y' ? true : false;
		$arFilter = array();
		if (is_numeric($search))
		{
			$arFilter['ID'] = (int)$search;
			$arFilter['%TITLE'] = $search;
			$arFilter['LOGIC'] = 'OR';
		}
		else if (preg_match('/(.*)\[(\d+?)\]/i' . BX_UTF_PCRE_MODIFIER, $search, $arMatches))
		{
			$arFilter['ID'] = (int)$arMatches[2];
			$arFilter['%TITLE'] = trim($arMatches[1]);
			$arFilter['LOGIC'] = 'OR';
		}
		else
			$arFilter['%TITLE'] = $search;

		$arDealStageList = CCrmStatus::GetStatusListEx('DEAL_STAGE');
		$arSelect = array('ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME');
		$arOrder = array('TITLE' => 'ASC');
		$obRes = CCrmDeal::GetList($arOrder, $arFilter, $arSelect, $nPageTop);
		$arFiles = array();
		while ($arRes = $obRes->Fetch())
		{
			$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
			$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '') . $arRes['CONTACT_FULL_NAME'];

			$arData[] =
				array(
					'id' => $multi ? 'D_' . $arRes['ID'] : $arRes['ID'],
					'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_deal_show'),
						array(
							'deal_id' => $arRes['ID']
						)
					),
					'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
					'desc' => $clientTitle,
					'type' => 'deal'
				);
		}
	}

	__CrmDealListEndResonse($arData);
}
elseif ($action === 'REBUILD_SEARCH_CONTENT')
{
	$agent = \Bitrix\Crm\Agent\Search\DealSearchContentRebuildAgent::getInstance();
	if(!$agent->isEnabled())
	{
		__CrmDealListEndResonse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmDealListEndResonse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS']
		)
	);
}
elseif ($action === 'REFRESH_ACCOUNTING')
{
	$agent = \Bitrix\Crm\Agent\Accounting\DealAccountSyncAgent::getInstance();
	if(!$agent->isEnabled())
	{
		__CrmDealListEndResonse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmDealListEndResonse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'BUILD_TIMELINE')
{
	$agent = \Bitrix\Crm\Agent\Timeline\DealTimelineBuildAgent::getInstance();
	if(!$agent->isEnabled())
	{
		__CrmDealListEndResonse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmDealListEndResonse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'BUILD_RECURRING_TIMELINE')
{
	$agent = \Bitrix\Crm\Agent\Timeline\RecurringDealTimelineBuildAgent::getInstance();
	if(!$agent->isEnabled())
	{
		__CrmDealListEndResonse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmDealListEndResonse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'SAVE_PROGRESS')
{
	CUtil::JSPostUnescape();
	$ID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0;
	$typeName = isset($_REQUEST['TYPE']) ? $_REQUEST['TYPE'] : '';
	$statusId = isset($_REQUEST['VALUE']) ? $_REQUEST['VALUE'] : '';

	if($statusId === '' || $ID <= 0  || $typeName !== CCrmOwnerType::OrderShipmentName)
		__CrmDealListEndResonse(array('ERROR' => 'Invalid data.'));

	if (!\Bitrix\Crm\Order\Permissions\Shipment::checkUpdatePermission($ID, $userPerms))
		__CrmDealListEndResonse(array('ERROR' => 'Access denied.'));

	$shipment = \Bitrix\Crm\Order\Manager::getShipmentObject($ID);
	$result = $shipment->setField('STATUS_ID', $statusId);
	if ($result->isSuccess())
	{
		/** @var \Bitrix\Crm\Order\ShipmentCollection $shipmentCollection */
		$shipmentCollection = $shipment->getCollection();
		if ($shipmentCollection)
		{
			$order = $shipmentCollection->getOrder();
			if ($order)
			{
				$result = $order->save();
			}
		}
	}

	if (!$result->isSuccess())
	{
		__CrmDealListEndResonse(array('ERROR' => implode(',<br>\n', $result->getErrorMessages())));
	}

	__CrmDealListEndResonse(array('TYPE' => CCrmOwnerType::OrderShipmentName, 'ID' => $ID, 'VALUE' => $statusId));
}
elseif ($action === 'GET_ROW_COUNT')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmDeal::CheckReadPermission(0, $userPerms))
	{
		__CrmDealListEndResonse(array('ERROR' => 'Access denied.'));
	}

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
	if(!($gridID !== ''
		&& isset($_SESSION['CRM_GRID_DATA'])
		&& isset($_SESSION['CRM_GRID_DATA'][$gridID])
		&& is_array($_SESSION['CRM_GRID_DATA'][$gridID])))
	{
		__CrmDealListEndResonse(array('DATA' => array('TEXT' => '')));
	}

	$gridData = $_SESSION['CRM_GRID_DATA'][$gridID];
	$filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
	if ($filter['IS_RECURRING'] === 'Y' || $filter['=IS_RECURRING'] === 'Y')
	{
		$options['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'RECURRING';
	}
	else
	{
		$options = array();
	}

	$result = CCrmDeal::GetListEx(array(), $filter, array(), false, array(), $options);

	$text = '';
	if(is_numeric($result))
	{
		$text = GetMessage('CRM_DEAL_LIST_ROW_COUNT', array('#ROW_COUNT#' => $result));
		if($text === '')
		{
			$text = $result;
		}
	}
	__CrmDealListEndResonse(array('DATA' => array('TEXT' => $text)));
}
?>
