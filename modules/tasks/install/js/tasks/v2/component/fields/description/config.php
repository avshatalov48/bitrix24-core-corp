<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/description.bundle.css',
	'js' => 'dist/description.bundle.js',
	'rel' => [
		'ui.vue3.components.button',
		'ui.uploader.core',
		'ui.lexical.core',
		'tasks.v2.core',
		'main.core.events',
		'ui.vue3.directives.hint',
		'tasks.v2.component.elements.hint',
		'ui.bbcode.formatter.html-formatter',
		'ui.icon-set.api.vue',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'disk.uploader.user-field-widget',
		'tasks.v2.component.elements.bottom-sheet',
		'tasks.v2.component.drop-zone',
		'ui.vue3.components.popup',
		'ui.vue3.vuex',
		'main.core',
		'ui.text-editor',
		'tasks.v2.model.tasks',
		'tasks.v2.const',
		'tasks.v2.provider.service.task-service',
		'tasks.v2.provider.service.file-service',
	],
	'skip_core' => false,
];
