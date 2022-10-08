<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'src/notifications.widget.css',
	'js' => 'src/notifications.widget.js',
	'rel' => [
		'ui.design-tokens',
		'ui.fonts.opensans',
		'main.qrcode',
	],
	'skip_core' => true,
];