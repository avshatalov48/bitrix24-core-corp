<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
global $APPLICATION;

$APPLICATION->SetPageProperty("BodyClass", "flexible-mode--linear-blue--v2");
$APPLICATION->AddHeadString('<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">');

if (\Bitrix\Main\Loader::includeModule('calendar'))
{
	$APPLICATION->IncludeComponent(
		'bitrix:calendar.pub.sharing',
		"",
		[
			'HASH' => $_REQUEST['hash'] ?? '',
		]
	);
}