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

if (!CModule::IncludeModule('crm')
	|| !CModule::IncludeModule('sale')
	|| !CCrmSecurityHelper::IsAuthorized()
	|| !check_bitrix_sessid())
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
			'PLACEMENT' => "CRM_ORDER_LIST_MENU",
			"PLACEMENT_OPTIONS" => array(),
			'INTERFACE_EVENT' => 'onCrmOrderMenuInterfaceInit',
			'MENU_EVENT_MODULE' => 'crm',
			'MENU_EVENT' => 'onCrmOrderListItemBuildMenu',
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
}

$componentData = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
$componentParams = isset($componentData['params']) && is_array($componentData['params']) ? $componentData['params'] : array();

//Security check
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$filter = isset($componentParams['INTERNAL_FILTER']) && is_array($componentParams['INTERNAL_FILTER'])
	? $componentParams['INTERNAL_FILTER'] : array();

//ASSOCIATED_CONTACT_ID - new multiple binding
//CONTACT_ID - old sigle binding
$contactID = isset($filter['ASSOCIATED_CONTACT_ID'])
	? $filter['ASSOCIATED_CONTACT_ID']
	: (isset($filter['CONTACT_ID']) ? $filter['CONTACT_ID'] : 0);
$companyID = isset($filter['COMPANY_ID']) ? $filter['COMPANY_ID'] : 0;
$leadID = isset($filter['LEAD_ID']) ? $filter['LEAD_ID'] : 0;
$quoteID = isset($filter['QUOTE_ID']) ? $filter['QUOTE_ID'] : 0;
$dealID = isset($filter['ASSOCIATED_DEAL_ID'])
	? $filter['ASSOCIATED_DEAL_ID']
	: (isset($filter['DEAL_ID']) ? $filter['DEAL_ID'] : 0);

$isPermitted = false;
if($contactID > 0)
{
	$isPermitted = CCrmContact::CheckReadPermission($contactID, $userPermissions);
}
elseif($companyID > 0)
{
	$isPermitted = CCrmCompany::CheckReadPermission($companyID, $userPermissions);
}
elseif($leadID > 0)
{
	$isPermitted = CCrmLead::CheckReadPermission($leadID, $userPermissions);
}
elseif($quoteID > 0)
{
	$isPermitted = CCrmQuote::CheckReadPermission($quoteID, $userPermissions);
}
elseif($dealID > 0)
{
	$isPermitted = CCrmDeal::CheckReadPermission($dealID, $userPermissions);
}

if(!$isPermitted)
{
	die();
}
//For custom reload with params
$ajaxLoaderParams = array(
	'url' => '/bitrix/components/bitrix/crm.order.list/lazyload.ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
	'method' => 'POST',
	'dataType' => 'ajax',
	'data' => array('PARAMS' => $componentData)
);

//Force AJAX mode
$componentParams['AJAX_MODE'] = 'Y';
$componentParams['AJAX_OPTION_JUMP'] = 'N';
$componentParams['AJAX_OPTION_HISTORY'] = 'N';
$componentParams['AJAX_LOADER'] = $ajaxLoaderParams;

//Enable sanitaizing
$componentParams['IS_EXTERNAL_CONTEXT'] = 'Y';

$APPLICATION->IncludeComponent('bitrix:crm.order.list',
	isset($componentData['template']) ? $componentData['template'] : '',
	$componentParams,
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
die();