$langs = [
	'ru', // Russian
	'en', // English
	'de', // German
	'ua', // Ukrainian
	'la', // Spanish
	'br', // Portuguese
	'fr', // French
	'sc', // Chinese Simplified
	'tc', // Chinese Traditional
	'pl', // Polish
	'it', // Italian
	'tr', // Turkish
	'ja', // Japanese
	'vn', // Vietnamese
	'id', // Indonesian
	'ms', // Malay
	'th', // Thai
	'ar', // Arabic
	'kz', // Kazakh
];

if (!preg_match("~^/bitrix/admin/~i", $_SERVER["REQUEST_URI"]))
{
	if (isset($_GET['user_lang']) && in_array($_GET['user_lang'], $langs))
	{
		setcookie("USER_LANG", $_GET['user_lang'], time()+9999999, "/");
		define("LANGUAGE_ID", $_GET['user_lang']);
	}
	elseif (isset($_COOKIE['USER_LANG']) && in_array($_COOKIE['USER_LANG'], $langs))
	{
		define("LANGUAGE_ID", $_COOKIE['USER_LANG']);
	}
}
