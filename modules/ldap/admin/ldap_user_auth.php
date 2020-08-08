<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!isset($_REQUEST["ldap_user_id"]) || mb_strlen($_REQUEST["ldap_user_id"]) != 32)
	LocalRedirect("/");

IncludeModuleLangFile(__FILE__);

$bCgi = (mb_stristr(php_sapi_name(), "cgi") !== false);

if ($USER->IsAuthorized())
{
	if (isset($_REQUEST["back_url"]) && $_REQUEST["back_url"] <> '')
		LocalRedirect($_REQUEST["back_url"]);
	else
		LocalRedirect("/");
}
elseif (!$bCgi)
{
	$USER->RequiredHTTPAuthBasic($Realm = "Bitrix");
	echo GetMessage("LDAP_ENTER_LOGIN_AND_PASS");
	die();
}
else
{
	$APPLICATION->AuthForm(GetMessage("LDAP_ENTER_LOGIN_AND_PASS2"));
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>