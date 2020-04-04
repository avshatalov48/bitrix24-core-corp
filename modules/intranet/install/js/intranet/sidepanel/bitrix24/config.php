<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' =>[
		'/bitrix/js/intranet/sidepanel/bitrix24/slider.js',
	],
	'css' =>[
		'/bitrix/js/intranet/sidepanel/bitrix24/slider.css',
	],
	'rel' => [
		'sidepanel',
		'intranet.sidepanel.bindings',
		'intranet.sidepanel.external',
	],
];