<?

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('DisableEventsCheck', true);
define('NO_AGENT_CHECK', true);

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmOrderShipmentListEndResponse'))
{
	function __CrmOrderShipmentListEndResponse($result)
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

if ($action === 'SAVE_PROGRESS' && check_bitrix_sessid())
{
	CUtil::JSPostUnescape();
	$ID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0;
	$typeName = isset($_REQUEST['TYPE']) ? $_REQUEST['TYPE'] : '';
	$statusId = isset($_REQUEST['VALUE']) ? $_REQUEST['VALUE'] : '';

	if($statusId === '' || $ID <= 0  || $typeName !== CCrmOwnerType::OrderShipmentName)
		__CrmOrderShipmentListEndResponse(array('ERROR' => 'Invalid data.'));

	if (!\Bitrix\Crm\Order\Permissions\Shipment::checkUpdatePermission($ID, $userPerms))
		__CrmOrderShipmentListEndResponse(array('ERROR' => 'Access denied.'));

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
		__CrmOrderShipmentListEndResponse(array('ERROR' => implode(',<br>\n', $result->getErrorMessages())));
	}

	__CrmOrderShipmentListEndResponse(array('TYPE' => CCrmOwnerType::OrderShipmentName, 'ID' => $ID, 'VALUE' => $statusId));
}
elseif ($action === 'GET_ROW_COUNT')
{
	$result = '';

	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmPerms::IsAccessEnabled($userPerms))
	{
		__CrmOrderShipmentListEndResponse(array('ERROR' => 'Access denied.'));
	}

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';

	if(!($gridID !== ''
		&& isset($_SESSION['CRM_GRID_DATA'])
		&& isset($_SESSION['CRM_GRID_DATA'][$gridID])
		&& is_array($_SESSION['CRM_GRID_DATA'][$gridID])))
	{
		__CrmOrderShipmentListEndResponse(array('DATA' => array('TEXT' => '')));
	}

	$gridData = $_SESSION['CRM_GRID_DATA'][$gridID];

	$filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();

	CBitrixComponent::includeComponentClass("bitrix:crm.order.shipment.list");
	$checkObj = new CCrmOrderShipmentListComponent();
	$result = $checkObj->getCount($filter);
	$text = '';
	if(is_numeric($result))
	{
		$text = GetMessage('CRM_ORDER_SHIPMENT_LIST_ROW_COUNT', array('#ROW_COUNT#' => $result));
		if($text === '')
		{
			$text = $result;
		}
	}
	__CrmOrderShipmentListEndResponse(array('DATA' => array('TEXT' => $text)));
}
?>