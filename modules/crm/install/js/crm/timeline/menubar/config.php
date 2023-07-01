<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/toolbar.bundle.css',
	'js' => 'dist/toolbar.bundle.js',
	'rel' => [
		'main.core.events',
		'calendar.sharing.interface',
		'crm_common',
		'crm.activity.todo-editor',
	],
];
