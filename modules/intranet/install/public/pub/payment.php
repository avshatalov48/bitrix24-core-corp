<?
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

if ((isset($_REQUEST['paySystemId']) || isset($_REQUEST['invoice_id'])) && (!isset($_REQUEST['initiate_pay']) || $_REQUEST['initiate_pay'] != 'Y'))
{
	require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

	global $APPLICATION;

	if (isset($_REQUEST['paySystemId']))
	{
		include ($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.invoice.payment.client/ajax.php');
	}
	else
	{
		$APPLICATION->IncludeComponent(
			'bitrix:crm.invoice.payment',
			'',
			array(
				'ORDER_ID' => $_REQUEST['invoice_id'],
				'HASH' =>  $_REQUEST['hash'],
				'PUBLIC_LINK_MODE' => 'Y'
			)
		);
	}

	require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
}
else
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

	global $APPLICATION;
	$APPLICATION->IncludeComponent("bitrix:crm.invoice.payment.client", ".default", array(
		'ACCOUNT_NUMBER' => base64_decode($_REQUEST['account_number']),
		'HASH' => $_REQUEST['hash'],
		'PAY_SYSTEM_ID' => array_key_exists('paySystemId', $_REQUEST) ? $_REQUEST['paySystemId'] : 0
	));

	define('SKIP_TEMPLATE_B24_SIGN', \Bitrix\Main\Config\Option::get('crm', 'invoice_enable_public_b24_sign', 'Y') != 'Y');
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
}


