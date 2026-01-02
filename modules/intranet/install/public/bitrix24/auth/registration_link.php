<?php
define("NEED_AUTH", false);
define("NOT_CHECK_PERMISSIONS", true);
define("EXTRANET_NO_REDIRECT", true);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/login/components/bitrix/system.auth.registration/.default/template.php");

global $APPLICATION;
$APPLICATION->SetTitle(GetMessage("AUTH_REGISTER"));

$APPLICATION->IncludeComponent(
	'bitrix:intranet.auth.registration',
	'',
);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
