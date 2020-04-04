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

$params = isset($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? strtoupper($params['ENTITY_TYPE_NAME']) : '';
if($entityTypeName === '')
{
	die();
}

$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
if($entityTypeID === CCrmOwnerType::Undefined)
{
	die();
}

$entityID = isset($params['ENTITY_ID']) ? $params['ENTITY_ID'] : 0;
if($entityID <= 0)
{
	die();
}

$permissionEntityType = isset($params['PERMISSION_ENTITY_TYPE']) ? strtoupper($params['PERMISSION_ENTITY_TYPE']) : '';
if($permissionEntityType === '')
{
	$permissionEntityType = $entityTypeName;
}

$userPermissions =  CCrmPerms::GetCurrentUserPermissions();
if(!CCrmAuthorizationHelper::CheckReadPermission($permissionEntityType, $entityID, $userPermissions))
{
	die();
}

$formID = isset($params['FORM_ID']) ? $params['FORM_ID'] : '';
$pathToUserProfile = isset($params['PATH_TO_USER_PROFILE']) ? $params['PATH_TO_USER_PROFILE'] : '';
$postFormUri = isset($params['POST_FORM_URI']) ? $params['POST_FORM_URI'] : '';
if($postFormUri !== '')
{
	$tabKey = $formID !== '' ? "{$formID}_active_tab" : 'active_tab';
	$tabID = isset($params['TAB_ID']) ? $params['TAB_ID'] : '';
	$postFormUri = CHTTP::urlAddParams($postFormUri, array($tabKey => $tabID));
}
$actionUri = isset($params['ACTION_URI']) ? $params['ACTION_URI'] : '';

global $APPLICATION;
Header('Content-Type: text/html; charset='.LANG_CHARSET);
$APPLICATION->ShowAjaxHead();
$APPLICATION->IncludeComponent('bitrix:crm.entity.livefeed',
	'',
	array(
		'DATE_TIME_FORMAT' => (LANGUAGE_ID == 'en' ? "j F Y g:i a" : (LANGUAGE_ID == 'de' ? "j. F Y, G:i" : "j F Y G:i")),
		'CAN_EDIT' => CCrmAuthorizationHelper::CheckUpdatePermission($permissionEntityType, $entityID, $userPermissions),
		'ENTITY_TYPE_ID' => $entityTypeID,
		'ENTITY_ID' => $entityID,
		'PERMISSION_ENTITY_TYPE' => $permissionEntityType,
		'POST_FORM_URI' => $postFormUri,
		'ACTION_URI' => $actionUri,
		'FORM_ID' => $formID,
		'PATH_TO_USER_PROFILE' => $pathToUserProfile,
		'LAZYLOAD' => 'Y'
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
die();