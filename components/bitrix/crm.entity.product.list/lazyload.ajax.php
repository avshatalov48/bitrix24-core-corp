<?php

use Bitrix\Crm;
use Bitrix\Main\Loader;

const NO_KEEP_STATISTIC = 'Y';
const NO_AGENT_STATISTIC = 'Y';
const NO_AGENT_CHECK = true;
const PUBLIC_AJAX_MODE = true;
const DisableEventsCheck = true;

if (isset($_REQUEST['site']) && !empty($_REQUEST['site']))
{
	if (!is_string($_REQUEST['site']))
	{
		die();
	}
	if (preg_match('/^[a-z][a-z0-9_]$/i', $_REQUEST['site']))
	{
		define('SITE_ID', $_REQUEST['site']);
	}
	else
	{
		die();
	}
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!Loader::includeModule('crm') || !check_bitrix_sessid())
{
	die();
}

if (!CCrmSecurityHelper::IsAuthorized())
{
	die();
}

/** @global \CMain $APPLICATION */
global $APPLICATION;
Header('Content-Type: text/html; charset='.LANG_CHARSET);
$APPLICATION->ShowAjaxHead();

$componentData = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : [];
$componentParams = [];
if (isset($componentData['signedParameters']))
{
	$componentParams = \CCrmInstantEditorHelper::unsignComponentParams(
		(string)$componentData['signedParameters'],
		'crm.entity.product.list'
	);
	if (is_null($componentParams))
	{
		die();
	}
}
elseif (isset($componentData['params']) && is_array($componentData['params']))
{
	ShowError('Component params must be signed');
	die();
}

// Security check
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$filter = isset($componentParams['INTERNAL_FILTER']) && is_array($componentParams['INTERNAL_FILTER'])
	? $componentParams['INTERNAL_FILTER'] : [];

//
// For custom reload with params
$ajaxLoaderParams = [
	'url' => Crm\Component\EntityDetails\ProductList::getLoaderUrl(
		[
			'site' => SITE_ID,
		],
		bitrix_sessid_get()
	),
	'method' => 'POST',
	'dataType' => 'ajax',
	'data' => [
		'PARAMS' => $componentData,
	],
];

// Force AJAX mode
$componentParams['AJAX_MODE'] = 'Y';
$componentParams['AJAX_OPTION_JUMP'] = 'N';
$componentParams['AJAX_OPTION_HISTORY'] = 'N';
$componentParams['AJAX_LOADER'] = $ajaxLoaderParams;

// Enable sanitizing
$componentParams['IS_EXTERNAL_CONTEXT'] = 'Y';

$APPLICATION->IncludeComponent(
	'bitrix:crm.entity.product.list',
	$componentData['template'] ?? '',
	$componentParams,
	false,
	[
		'HIDE_ICONS' => 'Y',
		'ACTIVE_COMPONENT' => 'Y'
	]
);

CMain::FinalActions();
