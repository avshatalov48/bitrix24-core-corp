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
		'ui.avatar-editor',
		'main.core.events',
		'main.core',
		'ui.qrauthorization',
		'main.loader',
	],
	'skip_core' => false,
];