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
CBitrixComponent::includeComponentClass('bitrix:crm.order.list');
global $DB, $APPLICATION;
if(!function_exists('__CrmOrderListEndResponse'))
{
	function __CrmOrderListEndResponse($result)
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

	if(!\Bitrix\Crm\Order\Permissions\Order::checkReadPermission(0, $userPerms))
	{
		__CrmOrderListEndResponse(array('ERROR' => 'Access denied.'));
	}

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

	$data = array();
	$search = trim($_REQUEST['VALUE']);
	if (!empty($search))
	{
		$multi = isset($_REQUEST['MULTI']) && $_REQUEST['MULTI'] == 'Y' ? true : false;
		$filter = array();
		if (is_numeric($search))
		{
			$filter['ID'] = (int)$search;
			$filter['%ACCOUNT_NUMBER'] = $search;
			$filter['LOGIC'] = 'OR';
		}
		else if (preg_match('/(.*)\[(\d+?)\]/iu', $search, $arMatches))
		{
			$filter['ID'] = (int)$arMatches[2];
			$filter['%ACCOUNT_NUMBER'] = trim($arMatches[1]);
			$filter['LOGIC'] = 'OR';
		}
		else
		{
			$filter['%ACCOUNT_NUMBER'] = $search;
		}

		$select = array('ID', 'ACCOUNT_NUMBER');
		$order = array('ID' => 'DESC');
		$resultDB = \Bitrix\Crm\Order\Order::getList(array(
			'select' =>  $select,
			'filter' => $filter,
			'limit' => $nPageTop,
			'order' => $order
		));

		while ($result = $resultDB->fetch())
		{
			$data[] = array(
				'id' => $result['SID'],
				'title' => $result['ACCOUNT_NUMBER'],
				'desc' => $result['ACCOUNT_NUMBER'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_order_details'),
					array(
						'order_id' => $arRes['ID']
					)
				),
				'type'  => 'order',
			);
		}
	}

	__CrmOrderListEndResponse($data);
}
elseif ($action === 'REBUILD_SEARCH_CONTENT')
{
	/** @var \Bitrix\Crm\Agent\Search\OrderSearchContentRebuildAgent $agent */
	$agent = \Bitrix\Crm\Agent\Search\OrderSearchContentRebuildAgent::getInstance();
	$isAgentEnabled = $agent->isEnabled();
	if ($isAgentEnabled)
	{
		if (!$agent->isActive())
		{
			$agent->enable(false);
			$isAgentEnabled = false;
		}
	}
	if(!$isAgentEnabled)
	{
		__CrmOrderListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmOrderListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'SAVE_PROGRESS' && check_bitrix_sessid())
{
	$ID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0;
	$typeName = isset($_REQUEST['TYPE']) ? $_REQUEST['TYPE'] : '';
	$statusId = isset($_REQUEST['VALUE']) ? $_REQUEST['VALUE'] : '';
	$reasonCanceled = isset($_REQUEST['REASON_CANCELED']) ? $_REQUEST['REASON_CANCELED'] : '';

	if($statusId === '' || $ID <= 0  || $typeName !== CCrmOwnerType::OrderName)
		__CrmOrderListEndResponse(array('ERROR' => 'Invalid data.'));

	if (!\Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($ID, $userPerms))
		__CrmOrderListEndResponse(array('ERROR' => 'Access denied.'));

	$res = \Bitrix\Crm\Order\Manager::setOrderStatus($ID, $statusId, $reasonCanceled);

	if(!$res->isSuccess())
	{
		$errorData = array('ERROR' => implode(',<br>\n',$res->getErrorMessages()));
		$data = $res->getData();
		if (!empty($data['PREVIOUS_STATUS_ID']))
		{
			$errorData['STATUS_ID'] = $data['PREVIOUS_STATUS_ID'];
		}
		if (\Bitrix\Crm\Order\OrderStatus::getSemanticID($statusId) == \Bitrix\Crm\PhaseSemantics::FAILURE)
		{
			$errorData['ERROR_TITLE'] = GetMessage('CRM_ORDER_LIST_ERROR_CANCEL_ORDER_TITLE');
		}
		__CrmOrderListEndResponse($errorData);
	}

	__CrmOrderListEndResponse(array('TYPE' => CCrmOwnerType::OrderName, 'ID' => $ID, 'VALUE' => $statusId));
}
elseif ($action === 'GET_ROW_COUNT')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!\Bitrix\Crm\Order\Permissions\Order::checkReadPermission(0, $userPerms))
	{
		__CrmOrderListEndResponse(array('ERROR' => 'Access denied.'));
	}

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
	if(!($gridID !== ''
		&& isset($_SESSION['CRM_GRID_DATA'])
		&& isset($_SESSION['CRM_GRID_DATA'][$gridID])
		&& is_array($_SESSION['CRM_GRID_DATA'][$gridID])))
	{
		__CrmOrderListEndResponse(array('DATA' => array('TEXT' => '')));
	}

	$gridData = $_SESSION['CRM_GRID_DATA'][$gridID];
	$filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
	$runtime = [];
	$isFilteredByCheckPrinted = \CCrmOrderListComponent::isFilteredByRuntimeField(
		\CCrmOrderListComponent::RUNTIME_ORDER_CHECK_PRINTED,
		$filter
	);
	if ($isFilteredByCheckPrinted)
	{
		$runtime[] = \CCrmOrderListComponent::getCheckPrintedRuntime();
	}

	$result = Bitrix\Crm\Order\Order::getList(array(
		'filter' => $filter,
		'select' => ['ID'],
		'runtime' => $runtime,
		'count_total' => true,
	));

	$text = '';
	if($result->fetch())
	{
		$text = GetMessage('CRM_ORDER_LIST_ROW_COUNT', array('#ROW_COUNT#' => $result->getCount()));
		if($text === '')
		{
			$text = $result;
		}
	}
	__CrmOrderListEndResponse(array('DATA' => array('TEXT' => $text)));
}
?>
