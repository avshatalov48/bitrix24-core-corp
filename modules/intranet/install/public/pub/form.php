<?php

define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

$widgetUserLangPath = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/lang/';
if (
	isset($_GET['widget_user_lang'])
	&&
	preg_match("/^[a-z]{2,2}$/", $_GET['widget_user_lang'])
	&&
	@is_dir($widgetUserLangPath . $_GET['widget_user_lang'])
)
{
	setcookie("WIDGET_USER_LANG", $_GET['widget_user_lang'], time()+9999999, "/");
	define("LANGUAGE_ID", $_GET['widget_user_lang']);
}
elseif (
	isset($_COOKIE['WIDGET_USER_LANG'])
	&&
	preg_match("/^[a-z]{2,2}$/", $_COOKIE['WIDGET_USER_LANG'])
	&&
	@is_dir($widgetUserLangPath . $_COOKIE['WIDGET_USER_LANG'])
)
{
	define("LANGUAGE_ID", $_COOKIE['WIDGET_USER_LANG']);
}

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';

$APPLICATION->SetPageProperty("BodyClass", "flexible-middle-width");
$APPLICATION->AddHeadString('<meta name="viewport" content="width=device-width, initial-scale=1.0">');

$APPLICATION->IncludeComponent(
	"bitrix:crm.webform.fill",
	"",
	array(),
	null,
	array("HIDE_ICONS" => "Y")
);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';