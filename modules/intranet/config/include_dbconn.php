<?php

if (isset($portalLangs) && is_array($portalLangs) && !empty($portalLangs))
{
	define('INTRANET_LANGUAGE_ID_CHANGE_AVAILABLE', true);

	if (!preg_match("~^/bitrix/admin/~i", $_SERVER["REQUEST_URI"]))
	{
		if (isset($_GET['user_lang']) && in_array($_GET['user_lang'], $portalLangs))
		{
			setcookie("USER_LANG", $_GET['user_lang'], time() + 9999999, "/");
			define("LANGUAGE_ID", $_GET['user_lang']);
		}
		elseif (isset($_COOKIE['USER_LANG']) && in_array($_COOKIE['USER_LANG'], $portalLangs))
		{
			define("LANGUAGE_ID", $_COOKIE['USER_LANG']);
		}
	}
}