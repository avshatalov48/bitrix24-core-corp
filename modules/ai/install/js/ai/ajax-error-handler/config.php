<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use \Bitrix\AI\Facade\Bitrix24;

$isCloud = false;

if (Loader::includeModule('ai') && Bitrix24::shouldUseB24())
{
	$isCloud = true;
}

return [
	'css' => 'dist/ajax-error-handler.bundle.css',
	'js' => 'dist/ajax-error-handler.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'isCloud' => $isCloud,
	],
];
