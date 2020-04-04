<?php

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
if (!CModule::IncludeModule('crm') && !CModule::IncludeModule('sale'))
{
	return;
}
/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'SAVE'
 */
global $DB, $APPLICATION;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
if(!function_exists('__CrmOrderProductDetailsEndJsonResponse'))
{
	function __CrmOrderProductDetailsEndJsonResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
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
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$currentUserPermissions =  CCrmPerms::GetCurrentUserPermissions();

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if($action === '' && isset($_POST['MODE']))
{
	$action = $_POST['MODE'];
}
if($action === '')
{
	__CrmOrderProductDetailsEndJsonResponse(array('ERROR'=>'ACTION IS NOT DEFINED!'));
}

if($action === 'SAVE')
{
	$ID = isset($_POST['ACTION_ENTITY_ID']) ? max((int)$_POST['ACTION_ENTITY_ID'], 0) : 0;
	if ($ID > 0 && !\Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($ID, $currentUserPermissions))
	{
		__CrmOrderProductDetailsEndJsonResponse(array('ERROR' => 'ACCESS_DENIED'));
	}

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();

	if (empty($_POST['NAME']))
	{
		__CrmOrderProductDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_PRODUCT_NAME_EMPTY')));
	}
	if (empty($_POST['QUANTITY']))
	{
		__CrmOrderProductDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_PRODUCT_QUANTITY_EMPTY')));
	}
	if (empty($_POST['PRICE']))
	{
		__CrmOrderProductDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_PRODUCT_PRICE_EMPTY')));
	}
	if (empty($_POST['CURRENCY']))
	{
		__CrmOrderProductDetailsEndJsonResponse(array('ERROR' => GetMessage('CRM_PRODUCT_CURRENCY_EMPTY')));
	}

	$fields = array_intersect_key($_POST, array_flip(\Bitrix\Crm\Order\BasketItem::getAvailableFields()));
	if ((int)($_POST['MEASURE']) > 0)
	{
		$measures = \Bitrix\Crm\Measure::getMeasures(100);
		if (isset($measures[(int)$_POST['MEASURE']]))
		{
			$measure = $measures[(int)$_POST['MEASURE']];
			$fields['MEASURE_NAME'] = $measure['SYMBOL'];
			$fields['MEASURE_CODE'] = $measure['CODE'];
		}
	}
	if (isset($_POST['PROPERTY']) && is_array($_POST['PROPERTY']))
	{
		$fields['PROPS'] = $_POST['PROPERTY'];
	}
	$productId = max((int)$_POST['CODE'], 1);
	$fields['PRODUCT_ID'] = $productId;
	$fields['ORDER_ID'] = $ID;

	__CrmOrderProductDetailsEndJsonResponse(array('ENTITY_DATA' => $fields, 'ENTITY_ID' => $productId));
}
if($action === 'GET_FORMATTED_SUM')
{
	$sum = isset($_POST['SUM']) ? $_POST['SUM'] : 0.0;
	$currencyID = isset($_POST['CURRENCY_ID']) ? $_POST['CURRENCY_ID'] : '';
	if($currencyID === '')
	{
		$currencyID = CCrmCurrency::GetBaseCurrencyID();
	}

	__CrmOrderProductDetailsEndJsonResponse(
		array(
			'FORMATTED_SUM' => CCrmCurrency::MoneyToString($sum, $currencyID, '#'),
			'FORMATTED_SUM_WITH_CURRENCY' => CCrmCurrency::MoneyToString($sum, $currencyID, '')
		)
	);
}