<?php
use Bitrix\Disk\Uf\LocalDocumentController;

define("STOP_STATISTICS", true);
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID'])? $_REQUEST['SITE_ID'] : '';
$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if(!empty($siteId) && is_string($siteId))
{
	define('SITE_ID', $siteId);
}

$whileListDownloadActions = [
	'downloadFile',
	'download',
	'downloadArchive',
	'downloadArchiveByEntity',
	'show',
	'showView',
	'showVersionView',
	'showViewHtml',
	'showPreview',
	'transformOnOpen',
	'showTransformationInfo',
];
if(isset($_GET['action']) && in_array($_GET['action'], $whileListDownloadActions, true))
{
    define('BX_SECURITY_SESSION_READONLY', true);
}

if (!defined("BX_FORCE_DISABLE_SEPARATED_SESSION_MODE"))
{
	if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('%Bitrix24.Disk/([0-9.]+)%i', $_SERVER['HTTP_USER_AGENT']))
	{
		define("BX_FORCE_DISABLE_SEPARATED_SESSION_MODE", true);
	}
}

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

if(!\Bitrix\Main\Loader::includeModule('disk'))
{
	die;
}

if(!empty($_GET['document_action']) && !empty($_GET['service']))
{
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
		$docController = new \Bitrix\Disk\Uf\DocumentController();
		$docController
			->setActionName($_GET['document_action'])
			->setDocumentHandlerName($_GET['service'])
			->exec();
	}
}

$oauthToken = $_GET['auth'] ?? null;
if($oauthToken && \Bitrix\Main\Loader::includeModule('rest'))
{
	$authResult = null;
	if(\CRestUtil::checkAuth(
		$oauthToken,
		\Bitrix\Disk\Driver::INTERNAL_MODULE_ID,
		$authResult
	))
	{
	    \CRestUtil::makeAuth($authResult);
	}
}

$ufController = new Bitrix\Disk\Uf\Controller();
$ufController
	->setActionName($_GET['action'] ?? '')
	->exec();
