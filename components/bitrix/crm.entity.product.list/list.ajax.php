<?php

use Bitrix\Main\Component\ParameterSigner;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;

const NO_KEEP_STATISTIC = 'Y';
const NO_AGENT_STATISTIC = 'Y';
const NO_AGENT_CHECK = true;
const PUBLIC_AJAX_MODE = true;
const DisableEventsCheck = true;

$siteID = isset($_REQUEST['site']) ? mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if ($siteID !== '')
{
	define('SITE_ID', $siteID);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
Header('Content-Type: text/html; charset='.LANG_CHARSET);

if (!Loader::includeModule('crm') || !check_bitrix_sessid())
{
	die();
}

if (!CCrmSecurityHelper::IsAuthorized())
{
	die();
}

global $APPLICATION;
$APPLICATION->ShowAjaxHead();

$componentName = 'bitrix:crm.entity.product.list';
$request = Context::getCurrent()->getRequest();

$params = [];

if ($request->get('signedParameters'))
{
	$params = ParameterSigner::unsignParameters($componentName, $request->get('signedParameters'));
}

$params['PRODUCTS'] = $request->get('products');
$params['LOCATION_ID'] = $request->get('locationId');
if ($request->get('currencyId'))
{
	$params['CURRENCY_ID'] = $request->get('currencyId');
}

$APPLICATION->IncludeComponent(
	$componentName,
	'.default',
	$params,
	null,
	[
		'HIDE_ICONS' => 'Y'
	]
);

CMain::FinalActions();
