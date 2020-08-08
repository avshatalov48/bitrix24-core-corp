<?php

$siteId = '';
if (isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);
}

if (!$siteId)
{
	define('SITE_ID', $siteId);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');


$APPLICATION->RestartBuffer();
?>
	<!DOCTYPE html>
	<html>
<head>
	<?$APPLICATION->ShowHead(); ?>
</head>
<body style="padding: 25px">
<?
$APPLICATION->includeComponent(
	'bitrix:tasks.automation', '',
	array(
		'TASK_ID' => isset($_REQUEST['task_id']) ? (int)$_REQUEST['task_id'] : 0,
		'PROJECT_ID' => isset($_REQUEST['project_id']) ? (int)$_REQUEST['project_id'] : 0,
		'VIEW_TYPE' => isset($_REQUEST['view']) ? (string)$_REQUEST['view'] : null
	)
);
?>
</body>
	</html><?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');