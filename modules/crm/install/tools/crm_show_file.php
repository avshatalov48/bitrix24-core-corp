<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

$authToken = isset($_REQUEST['auth']) ? $_REQUEST['auth'] : '';
if($authToken !== '')
{
	define('NOT_CHECK_PERMISSIONS', true);
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$errors = array();
if(CModule::IncludeModule('crm'))
{
	$options = array();
	if($authToken !== '')
	{
		$options['oauth_token'] = $authToken;
	}

	if (isset($_REQUEST['preview']) && strtoupper($_REQUEST['preview']) == 'Y')
		$options['preview'] = true;

	CCrmFileProxy::WriteDiskFileToResponse(
		isset($_REQUEST['ownerTypeId']) ? (int)$_REQUEST['ownerTypeId'] : CCrmOwnerType::Undefined,
		isset($_REQUEST['ownerId']) ? (int)$_REQUEST['ownerId'] : 0,
		isset($_REQUEST['fileId']) ? (int)$_REQUEST['fileId'] : 0,
		$errors,
		$options
	);
}
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");
if(!empty($errors))
{
	foreach($errors as $error)
	{
		echo $error;
	}
}
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog.php");