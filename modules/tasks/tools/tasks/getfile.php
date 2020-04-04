<?php

/**
 * This script requests BASIC authorization
 */

$_GET['tid'] = null;
$_GET['fid'] = null;
$_GET['TASK_ID'] = null;

if (isset($_GET['fileid']) && $_GET['fileid'])
	$_GET['fid'] = (int) $_GET['fileid'];

if (isset($_GET['taskid']) && $_GET['taskid'])
	$_GET['TASK_ID'] = (int) $_GET['taskid'];

define('NOT_CHECK_PERMISSIONS', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

// Not authorized?
if ( ! (
	isset($USER)
	&& is_object($USER)
	&& method_exists($USER, 'getid')
	&& $USER->getId()
))
{
	// Request basic authorization
	CHTTP::SetAuthHeader(false);
	die();
}

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/tasks.task.detail/show_file.php");
