<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'dist/qr.bundle.js',
	],
	'css' => [
		'dist/style.bundle.css',
	],
	'skip_core' => false,
	'rel' => [
		'main.qrcode',
		'main.core',
		'main.popup',
		'ui.notification',
	]
];
