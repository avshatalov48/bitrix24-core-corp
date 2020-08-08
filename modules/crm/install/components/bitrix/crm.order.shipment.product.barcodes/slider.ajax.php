<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

$siteID = isset($_REQUEST['site'])? mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if($siteID !== '')
{
	define('SITE_ID', $siteID);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('crm')
	|| !CCrmSecurityHelper::IsAuthorized()
	|| !check_bitrix_sessid())
{
	die();
}

global $APPLICATION;

$template = $_REQUEST['template'] <> '' ? trim($_REQUEST['template']) : 'withmarkingcodes';
$basketId = (int)$_REQUEST['basketId'] > 0 ? (int)$_REQUEST['basketId'] : 0;
$storeId = (int)$_REQUEST['storeId'] > 0 ? (int)$_REQUEST['storeId'] : 0;
$additionalCssPath = isset($_REQUEST['additionalCssPath']) ? (string)$_REQUEST['additionalCssPath'] : '';

$APPLICATION->IncludeComponent('bitrix:crm.order.shipment.product.barcodes',
	$template,
	[
		'BASKET_ID' => $basketId,
		'STORE_ID' => $storeId,
		'ADDITIONAL_CSS_PATH' => $additionalCssPath
	],
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
die();