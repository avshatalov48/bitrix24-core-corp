<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$errors = array();
if(CModule::IncludeModule('crm'))
{
	CCrmFileProxy::WriteEventFileToResponse(
		isset($_REQUEST['eventId']) ? intval($_REQUEST['eventId']) : 0,
		isset($_REQUEST['fileId']) ? intval($_REQUEST['fileId']) : 0,
		$errors
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
