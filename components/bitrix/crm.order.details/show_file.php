<?php

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

$authToken = $_REQUEST['auth'] ?? '';
if ($authToken !== '')
{
	define('NOT_CHECK_PERMISSIONS', true);
}

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$errors = [];

if (CModule::IncludeModule('crm'))
{
	$options = [];
	if ($authToken !== '')
	{
		$options['oauth_token'] = $authToken;
	}

	//By default treat field as dynamic (for backward compatibility)
	$options['is_dynamic'] = !isset($_REQUEST['dynamic']) || mb_strtoupper($_REQUEST['dynamic']) !== 'N';
	if (isset($_REQUEST['owner_token']))
	{
		$options['owner_token'] = $_REQUEST['owner_token'];
	}

	CCrmFileProxy::WriteFileToResponse(
		CCrmOwnerType::Order,
		(int)($_REQUEST['ownerId'] ?? 0),
		(string)($_REQUEST['fieldName'] ?? ''),
		(int)($_REQUEST['fileId'] ?? 0),
		$errors,
		$options
	);
}

require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_after.php");

if (!empty($errors))
{
	foreach ($errors as $error)
	{
		echo $error;
	}
}

require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/epilog.php");
