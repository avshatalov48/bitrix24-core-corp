<?php
define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

global $USER, $APPLICATION;
if(!$USER->IsAuthorized())
{
	$httpAuth = $USER->LoginByHttpAuth();
	if($httpAuth !== null)
	{
		$APPLICATION->SetAuthResult($httpAuth);
	}
}

if (Bitrix\Main\Loader::includeModule('dav'))
{

	$handler = new \Bitrix\Dav\Profile\RequestHandler();
	$handler->process();
}
