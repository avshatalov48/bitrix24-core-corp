<?php
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

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('crm') || !CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid())
{
	die();
}

$componentData = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : [];
$componentParams = [];
if (isset($componentData['signedParameters']))
{
	$componentParams = \CCrmInstantEditorHelper::unsignComponentParams(
		(string)$componentData['signedParameters'],
		'crm.order.check.list'
	);
	if (is_null($componentParams))
	{
		ShowError('Wrong component signed parameters');
		die();
	}
}
elseif (isset($componentData['params']) && is_array($componentData['params']))
{
	ShowError('Component params must be signed');
	die();
}

//For custom reload with params
$ajaxLoaderParams = array(
	'url' => '/bitrix/components/bitrix/crm.order.check.list/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
	'method' => 'POST',
	'dataType' => 'ajax',
	'data' => array('PARAMS' => $componentData)
);

global $APPLICATION;
Header('Content-Type: text/html; charset='.LANG_CHARSET);
$APPLICATION->ShowAjaxHead();

$componentParams['ENABLE_CONTROL_PANEL'] = false;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$errors = [];
if ($action === 'delete')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
	if (!\Bitrix\Main\Loader::includeModule('sale'))
	{
		$errors[] = \Bitrix\Main\Localization\Loc::getMessage('CRM_MODULE_NOT_INSTALLED_SALE');
	}

	$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;

	if ($id > 0)
	{
		$check = \Bitrix\Sale\Cashbox\Internals\CashboxCheckTable::getRowById($id);
		if ($check['STATUS'] == 'E' || $check['STATUS'] == 'N' || $check['STATUS'] == 'P')
		{
			\Bitrix\Sale\Cashbox\CheckManager::delete($id);
		}
	}
}

//Force AJAX mode
$componentParams['AJAX_MODE'] = 'Y';
$componentParams['AJAX_OPTION_JUMP'] = 'N';
$componentParams['AJAX_OPTION_HISTORY'] = 'N';
$componentParams['AJAX_LOADER'] = $ajaxLoaderParams;
$componentParams['EXTERNAL_ERRORS'] = $errors;

//Fix boolean params
if(isset($componentParams['ENABLE_TOOLBAR']))
{
	$componentParams['ENABLE_TOOLBAR'] =
		$componentParams['ENABLE_TOOLBAR'] === 'Y'
		|| $componentParams['ENABLE_TOOLBAR'] === 'true'
		|| $componentParams['ENABLE_TOOLBAR'] === true
	;
}

$APPLICATION->IncludeComponent('bitrix:crm.order.check.list',
	isset($componentData['template']) ? $componentData['template'] : '',
	$componentParams,
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
die();