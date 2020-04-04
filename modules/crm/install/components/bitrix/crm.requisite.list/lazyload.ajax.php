<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

$siteID = isset($_REQUEST['site']) ? substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
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

$componentData = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
$componentParams = isset($componentData['params']) && is_array($componentData['params']) ? $componentData['params'] : array();

//Security check
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$filter = isset($componentParams['INTERNAL_FILTER']) && is_array($componentParams['INTERNAL_FILTER'])
	? $componentParams['INTERNAL_FILTER'] : array();
$entityTypeId = isset($filter['ENTITY_TYPE_ID']) ? $filter['ENTITY_TYPE_ID'] : 0;
$entityTypeId = isset($filter['=ENTITY_TYPE_ID']) ? $filter['=ENTITY_TYPE_ID'] : 0;
$entityId = isset($filter['ENTITY_ID']) ? $filter['ENTITY_ID'] : 0;
$entityId = isset($filter['=ENTITY_ID']) ? $filter['=ENTITY_ID'] : 0;
$requisite = new \Bitrix\Crm\EntityRequisite();
if (!$requisite->validateEntityExists($entityTypeId, $entityId)
	|| !$requisite->validateEntityReadPermission($entityTypeId, $entityId))
{
	die();
}

//For custom reload with params
$ajaxLoaderParams = array(
	'url' => '/bitrix/components/bitrix/crm.requisite.list/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
	'method' => 'POST',
	'dataType' => 'ajax',
	'data' => array('PARAMS' => $componentData)
);

global $APPLICATION;
Header('Content-Type: text/html; charset='.LANG_CHARSET);
$APPLICATION->ShowAjaxHead();
//Force AJAX mode
$componentParams['AJAX_MODE'] = 'Y';
$componentParams['AJAX_OPTION_JUMP'] = 'N';
$componentParams['AJAX_OPTION_HISTORY'] = 'N';
$componentParams['AJAX_LOADER'] = $ajaxLoaderParams;

//Enable sanitaizing
$componentParams['IS_EXTERNAL_CONTEXT'] = 'Y';

//Fix boolean params
if(isset($componentParams['ENABLE_TOOLBAR']))
{
	$componentParams['ENABLE_TOOLBAR'] = $componentParams['ENABLE_TOOLBAR'] === 'Y'
		|| $componentParams['ENABLE_TOOLBAR'] === 'true';
}

$APPLICATION->IncludeComponent('bitrix:crm.requisite.list',
	isset($componentData['template']) ? $componentData['template'] : '',
	$componentParams,
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
die();