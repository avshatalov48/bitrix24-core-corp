<?php

define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID']) ? $_REQUEST['SITE_ID'] : '';
$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if (!empty($siteId) && is_string($siteId))
{
	define('SITE_ID', $siteId);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);

if (!check_bitrix_sessid() || !($request->isPost()))
{
	die();
}

$params['ACCOUNT_NUMBER'] = $request->get('accountNumber');
if (empty($params['ACCOUNT_NUMBER']))
{
	die();
}

$params['PAY_SYSTEM_ID'] = (int)$request->get('paySystemId');
if (!isset($params['PAY_SYSTEM_ID']) || ($params['PAY_SYSTEM_ID'] <= 0))
{
	die();
}

$params['IS_AJAX_PAY'] = "Y";

$params['HASH'] = $request->get('hash');
	
CBitrixComponent::includeComponentClass("bitrix:crm.invoice.payment.client");

$orderPayment = new CrmInvoicePaymentClientComponent();
$orderPayment->initComponent('bitrix:crm.invoice.payment.client');
$orderPayment->includeComponent($params["TEMPLATE_PATH"], $params, null);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>