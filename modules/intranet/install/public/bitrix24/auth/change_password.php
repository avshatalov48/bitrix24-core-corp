<?
define("NEED_AUTH", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

if ($_REQUEST["change_password"] === "yes")
{
	$APPLICATION->IncludeComponent(
		"bitrix:b24network.account.password.edit",
		"",
		Array(
			"CHECKWORD" => $_REQUEST["USER_CHECKWORD"],
			"LOGIN" => $_REQUEST["USER_LOGIN"],
		)
	);
}
else
{
	LocalRedirect("/");
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");