<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/add-task-button.bundle.css',
	'js' => 'dist/add-task-button.bundle.js',
	'rel' => [
		'main.core',
		'ui.vue3.components.button',
		'tasks.v2.component.elements.hint',
		'tasks.v2.const',
		'tasks.v2.lib.field-highlighter',
		'tasks.v2.provider.service.file-service',
	],
	'skip_core' => false,
];
