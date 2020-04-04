<?php
define("STOP_STATISTICS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(!\Bitrix\Main\Loader::includeModule('disk'))
{
	die;
}

if(empty($_GET['action']))
{
	die;
}

$controller = new \Bitrix\Disk\DownloadController();
$controller
	->setActionName($_GET['action'])
	->exec()
;