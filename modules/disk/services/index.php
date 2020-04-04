<?php
define("NOT_CHECK_PERMISSIONS", true);
define("EXTRANET_NO_REDIRECT", true);
define("STOP_STATISTICS", true);
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);

if(isset($_GET['action']) && ($_GET['action'] === 'showFile' || $_GET['action'] === 'downloadFile' || $_GET['action'] === 'showPreview'))
{
    define('BX_SECURITY_SESSION_READONLY', true);
}

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
$httpRequest = \Bitrix\Main\Context::getCurrent()->getRequest();
if(!$httpRequest->getQuery('action'))
{
	die;
}

if(strtolower($httpRequest->getQuery('action')) === strtolower('downloadTestZipArchive'))
{
	$controller = new \Bitrix\Disk\ZipNginx\TestDownloadController;
	$controller
		->setActionName('downloadTestZipArchive')
		->exec()
	;

	\CMain::finalActions();
	die;
}


$oauthToken = $httpRequest->getQuery('auth');
if($oauthToken && \Bitrix\Main\Loader::includeModule('rest'))
{
	$authResult = null;
	if(\CrestUtil::checkAuth(
		$oauthToken,
		array(\Bitrix\Disk\Driver::INTERNAL_MODULE_ID),
		$authResult
	))
	{
	    \CRestUtil::makeAuth($authResult);
	}
}

$controller = new \Bitrix\Disk\DownloadController();
$controller
	->setActionName($httpRequest->getQuery('action'))
	->exec()
;
