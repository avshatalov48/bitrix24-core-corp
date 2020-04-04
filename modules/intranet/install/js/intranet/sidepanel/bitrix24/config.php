<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' =>[
		'/bitrix/js/intranet/sidepanel/bitrix24/src/slider.js',
	],
	'rel' => [
		'sidepanel',
		'intranet.sidepanel.bindings',
		'intranet.sidepanel.external',
	],
	'skip_core' => true,
];