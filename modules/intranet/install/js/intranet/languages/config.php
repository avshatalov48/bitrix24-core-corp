<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Loader;
use \Bitrix\Intranet\Public\Provider\Portal\LanguageProvider;

$languages = [];

if (Loader::includeModule('intranet'))
{
	$languages = (new LanguageProvider())->getPublicArray();
}

return [
	'css' => 'dist/languages.bundle.css',
	'js' => 'dist/languages.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'languages' => $languages
	]
];