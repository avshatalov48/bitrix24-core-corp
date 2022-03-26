<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'script.css',
	'js' => 'script.js',
	'rel' => [
		'ui.popupcomponentsmaker',
		'main.popup',
		'main.qrcode',
		'main.core',
		'main.core.events',
		'ui.qrauthorization',
	],
	'skip_core' => false,
];