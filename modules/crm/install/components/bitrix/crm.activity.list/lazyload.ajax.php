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

global $APPLICATION;
Header('Content-Type: text/html; charset='.LANG_CHARSET);
$APPLICATION->ShowAjaxHead();

if(\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
{
	$APPLICATION->IncludeComponent(
		'bitrix:app.placement',
		'menu',
		array(
			'PLACEMENT' => "CRM_ACTIVITY_LIST_MENU",
			"PLACEMENT_OPTIONS" => array(),
			'INTERFACE_EVENT' => 'onCrmActivityMenuInterfaceInit',
			'MENU_EVENT_MODULE' => 'crm',
			'MENU_EVENT' => 'onCrmActivityListItemBuildMenu',
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
}

$componentData = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
$componentParams = isset($componentData['params']) && is_array($componentData['params']) ? $componentData['params'] : array();

//Sanitaizing of Bindings (Only one binding is allowed in current context)
$bindings = isset($componentParams['BINDINGS']) && is_array($componentParams['BINDINGS']) ? $componentParams['BINDINGS'] : array();
if(empty($bindings))
{
	die();
}

$binding = $bindings[0];
if(!is_array($binding))
{
	die();
}

$ownerTypeName = isset($binding['TYPE_NAME']) ? $binding['TYPE_NAME'] : '';
$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
$ownerID = isset($binding['ID']) ? (int)$binding['ID'] : 0;
if($ownerTypeID === CCrmOwnerType::Undefined || $ownerID <= 0)
{
	die();
}

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmActivity::CheckReadPermission($ownerTypeID, $ownerID, $userPermissions))
{
	die();
}

$componentParams['OWNER'] = $bindings[0];
$componentParams['BINDINGS'] = $componentData['params']['BINDINGS'] = $bindings;

//Sanitaizing of Permission Type
$permissionType = isset($componentParams['PERMISSION_TYPE']) ? strtoupper($componentParams['PERMISSION_TYPE']) : 'READ';
if($permissionType !== 'READ' && !CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID, $userPermissions))
{
	$componentParams['PERMISSION_TYPE'] = $componentData['params']['PERMISSION_TYPE'] = 'READ';
}

//For custom reload with params
$ajaxLoaderParams = array(
	'url' => '/bitrix/components/bitrix/crm.activity.list/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
	'method' => 'POST',
	'dataType' => 'ajax',
	'data' => array('PARAMS' => $componentData)
);

$componentParams['ENABLE_CONTROL_PANEL'] = false;

//Force AJAX mode
$componentParams['AJAX_MODE'] = 'Y';
$componentParams['AJAX_OPTION_JUMP'] = 'N';
$componentParams['AJAX_OPTION_HISTORY'] = 'N';
$componentParams['AJAX_LOADER'] = $ajaxLoaderParams;

$APPLICATION->IncludeComponent('bitrix:crm.activity.list',
	isset($componentData['template']) ? $componentData['template'] : '',
	$componentParams,
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
die();