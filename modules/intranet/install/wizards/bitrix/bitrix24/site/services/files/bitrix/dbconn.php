<?
$arLang = array("ru", "en", "de", "ua", "la", "tc", "sc", "br", "fr", "pl", "tr");
if (isset($_GET['user_lang']) && in_array($_GET['user_lang'], $arLang))
{
	setcookie("USER_LANG", $_GET['user_lang'], time()+9999999, "/");
	define("LANGUAGE_ID", $_GET['user_lang']);
}
elseif (isset($_COOKIE['USER_LANG']) && in_array($_COOKIE['USER_LANG'], $arLang))
	define("LANGUAGE_ID", $_COOKIE['USER_LANG']);
?>