<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/sms-message.bundle.css',
	'js' => 'dist/sms-message.bundle.js',
	'rel' => [
		'main.core.events',
		'main.core',
		'main.popup',
		'salescenter.manager',
		'ui.vue',
	],
	'skip_core' => false,
];