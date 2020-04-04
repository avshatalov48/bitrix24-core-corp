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

	//By default treat field as dynamic (for backward compatibility)
	$options['is_dynamic'] = !isset($_REQUEST['dynamic']) || strtoupper($_REQUEST['dynamic']) !== 'N';
	CCrmFileProxy::WriteFileToResponse(
		CCrmOwnerType::Lead,
		isset($_REQUEST['ownerId']) ? intval($_REQUEST['ownerId']) : 0,
		isset($_REQUEST['fieldName']) ? $_REQUEST['fieldName'] : '',
		isset($_REQUEST['fileId']) ? intval($_REQUEST['fileId']) : 0,
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
?>
