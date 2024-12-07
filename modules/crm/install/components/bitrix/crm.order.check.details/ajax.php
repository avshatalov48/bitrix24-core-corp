<?php

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Crm\Order;
use Bitrix\Sale\Cashbox;

if (!CModule::IncludeModule('crm'))
{
	return;
}

if (!CModule::IncludeModule('sale'))
{
	return;
}
/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'GET_CHECK_DATA'
 * 'SAVE_CHECK'
 */
global $DB, $APPLICATION, $USER_FIELD_MANAGER;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if (!function_exists('__CrmOrderCheckDetailsEndJsonResponse'))
{
	function __CrmOrderCheckDetailsEndJsonResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();

		Header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);

		if (!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}

		if (!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}

		require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');

		die();
	}
}

$currentUser = CCrmSecurityHelper::GetCurrentUser();
if (!$currentUser || !$currentUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$currentUserPermissions =  CCrmPerms::GetCurrentUserPermissions();

$action = $_POST['ACTION'] ?? '';
if ($action === '' && isset($_POST['MODE']))
{
	$action = $_POST['MODE'];
}

if ($action === '')
{
	__CrmOrderCheckDetailsEndJsonResponse(array('ERROR'=>'ACTION IS NOT DEFINED!'));
}
elseif ($action === 'SAVE_CHECK')
{
	$orderId = (int)($_POST['ORDER_ID'] ?? 0);
	$entityType = $_POST['MAIN']['TYPE'];
	$entityId = (int)($_POST['MAIN']['VALUE'] ?? 0);
	$checkType = $_POST['TYPE'];
	$additionData = $_POST['ADDITION'] ?? null;

	$order = Order\Order::load($orderId);
	if ($order === null || $entityId <= 0)
	{
		__CrmOrderCheckDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_ORDER_NOT_FOUND')));
	}

	if (!\Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($orderId))
	{
		__CrmOrderCheckDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_ORDER_ACCESS_DENIED')));
	}

	$paymentCollection = $order->getPaymentCollection();
	$entities = array();
	if ($entityType === Cashbox\Check::SUPPORTED_ENTITY_TYPE_PAYMENT)
	{
		$entities[] = $paymentCollection->getItemById($entityId);
	}

	$shipmentCollection = $order->getShipmentCollection();
	if ($entityType === Cashbox\Check::SUPPORTED_ENTITY_TYPE_SHIPMENT)
	{
		$entities[] = $shipmentCollection->getItemById($entityId);
	}

	$relatedEntities = array();
	if (is_array($additionData))
	{
		foreach ($additionData as $entity)
		{
			if ($entity['TYPE'] === Cashbox\Check::SUPPORTED_ENTITY_TYPE_PAYMENT)
			{
				$relatedEntities[$entity['PAYMENT_TYPE']][] = $paymentCollection->getItemById($entity['VALUE']);
			}
			elseif ($entity['TYPE'] === Cashbox\Check::SUPPORTED_ENTITY_TYPE_SHIPMENT)
			{
				$relatedEntities[Cashbox\Check::SHIPMENT_TYPE_NONE][] = $shipmentCollection->getItemById($entity['VALUE']);
			}
		}
	}

	if (!Cashbox\Manager::isSupportedFFD105())
	{
		foreach ($relatedEntities as $type => $entityList)
		{
			foreach ($entityList as $item)
			{
				$entities[] = $item;
			}
		}

		$relatedEntities = array();
	}

	$addResult = Cashbox\CheckManager::addByType($entities, $checkType, $relatedEntities);
	if (!$addResult->isSuccess())
	{
		__CrmOrderCheckDetailsEndJsonResponse(array('ERROR' => implode("\n", $addResult->getErrorMessages())));
	}

	__CrmOrderCheckDetailsEndJsonResponse(array('ID' => $addResult->getId()));
}
elseif($action === 'GET_CHECK_DATA')
{
	$result = array();

	$orderId = (int)($_POST['ORDER_ID'] ?? 0);
	if ($orderId <= 0)
	{
		__CrmOrderCheckDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_ORDER_NOT_FOUND')));
	}
	$entityType = ($_POST['MAIN']['TYPE'] === Cashbox\Check::SUPPORTED_ENTITY_TYPE_SHIPMENT) ? Cashbox\Check::SUPPORTED_ENTITY_TYPE_SHIPMENT : Cashbox\Check::SUPPORTED_ENTITY_TYPE_PAYMENT;
	$entityId = (int)($_POST['MAIN']['VALUE'] ?? 0);
	$checkType = $_POST['TYPE'];
	if ($orderId > 0)
	{
		$order = Order\Order::load($orderId);
		if ($order === null)
		{
			__CrmOrderCheckDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_ORDER_NOT_FOUND')));
		}

		if (!\Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($orderId))
		{
			__CrmOrderCheckDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_ORDER_ACCESS_DENIED')));
		}

		if ($entityType === Cashbox\Check::SUPPORTED_ENTITY_TYPE_SHIPMENT)
		{
			$collection = $order->getShipmentCollection();
		}
		else
		{
			$collection = $order->getPaymentCollection();
		}

		$item = $collection->getItemById($entityId);

		if (empty($item))
		{
			__CrmOrderCheckDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_ORDER_ACCESS_DENIED')));
		}

		CBitrixComponent::includeComponentClass("bitrix:crm.order.check.details");
		$orderCheck = new CCrmOrderCheckDetailsComponent();
		$result = $orderCheck->prepareEditData($entityId, $entityType, $checkType);
	}
	
	__CrmOrderCheckDetailsEndJsonResponse(array('DATA' => $result));
}
