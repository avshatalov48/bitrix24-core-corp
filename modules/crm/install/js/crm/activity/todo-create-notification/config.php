<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/todo-create-notification.bundle.css',
	'js' => 'dist/todo-create-notification.bundle.js',
	'rel' => [
		'main.core',
		'main.core.events',
		'main.popup',
		'crm.activity.todo-editor-v2',
		'crm.activity.todo-notification-skip',
		'crm.activity.todo-notification-skip-menu',
		'crm_common',
	],
	'skip_core' => false,
];
