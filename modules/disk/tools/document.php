<?php
use Bitrix\Disk\Document\DocumentController;
use Bitrix\Disk\Document\LocalDocumentController;

define("STOP_STATISTICS", true);
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID'])? $_REQUEST['SITE_ID'] : '';
$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if(!empty($siteId) && is_string($siteId))
{
	define('SITE_ID', $siteId);
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(!\Bitrix\Main\Loader::includeModule('disk'))
{
	die;
}

if(empty($_GET['document_action']) || empty($_GET['service']))
{
	die;
}

if(LocalDocumentController::isLocalService($_GET['service']))
{
	$docController = new LocalDocumentController;
	$docController
		->setActionName(empty($_GET['primaryAction'])? $_GET['document_action'] : $_GET['primaryAction'])
		->exec()
	;
}
else
{
	$docController = new DocumentController;
	$docController
		->setActionName($_GET['document_action'])
		->setDocumentHandlerName($_GET['service'])
		->exec()
	;
}

