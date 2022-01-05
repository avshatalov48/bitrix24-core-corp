<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'src/notifications.widget.css',
	'js' => 'src/notifications.widget.js',
	'rel' => [
		'main.qrcode',
	],
	'skip_core' => true,
];