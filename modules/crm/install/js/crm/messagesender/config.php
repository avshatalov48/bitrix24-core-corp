<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/messagesender.bundle.css',
	'js' => 'dist/messagesender.bundle.js',
	'rel' => [
		'main.core.events',
		'main.core',
		'crm.data-structures',
		'crm_common',
	],
	'skip_core' => false,
];
