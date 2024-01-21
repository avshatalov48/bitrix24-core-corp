<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/call.bundle.css',
	'js' => 'dist/call.bundle.js',
	'rel' => [
		'ui.notification',
		'crm.ai.slider',
		'crm.ai.textbox',
		'crm.audio-player',
		'main.core',
	],
	'skip_core' => false,
];