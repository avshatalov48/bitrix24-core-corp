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
	'css' => 'dist/copilot-promo-popup.bundle.css',
	'js' => 'dist/copilot-promo-popup.bundle.js',
	'rel' => [
		'ui.promo-video-popup',
		'ui.buttons',
		'ui.icon-set.api.core',
		'ui.icon-set.main',
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'isWestZone' => $isWestZone,
	]
];
