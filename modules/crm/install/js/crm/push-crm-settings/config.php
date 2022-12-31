<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/push-crm-settings.bundle.css',
	'js' => 'dist/push-crm-settings.bundle.js',
	'rel' => [
		'main.core.events',
		'crm.activity.todo-notification-skip-menu',
		'main.popup',
		'crm.kanban.sort',
		'crm.kanban.restriction',
		'main.core',
	],
	'skip_core' => false,
];