<?php
define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

if (Bitrix\Main\Loader::includeModule('dav'))
{

	$handler = new \Bitrix\Dav\Profile\RequestHandler();
	$handler->process();
}
