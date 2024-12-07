<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\AI\Facade\Bitrix24;
use Bitrix\AI\Facade\Intranet;
use Bitrix\Main\Loader;

$isWestZone = false;

if (Loader::includeModule('ai'))
{
	if (Loader::includeModule('bitrix24'))
	{
		$isWestZone = Bitrix24::isWestZone();
	}
	elseif (Loader::includeModule('intranet'))
	{
		$isWestZone = Intranet::isWestZone();
	}
}


return [
	'css' => 'dist/copilot-banner.bundle.css',
	'js' => 'dist/copilot-banner.bundle.js',
	'rel' => [
		'main.popup',
		'main.core',
		'ui.icon-set.api.core',
		'ui.hint',
		'main.core.events',
	],
	'skip_core' => false,
	'settings' => [
		'isWestZone' => $isWestZone,
	]
];
