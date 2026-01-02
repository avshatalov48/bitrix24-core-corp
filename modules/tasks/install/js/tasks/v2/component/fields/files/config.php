<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/files.bundle.css',
	'js' => 'dist/files.bundle.js',
	'rel' => [
		'ui.vue3.directives.hint',
		'tasks.v2.component.elements.hint',
		'main.core',
		'ui.vue3.vuex',
		'tasks.v2.component.elements.bottom-sheet',
		'tasks.v2.component.drop-zone',
		'ui.icon-set.api.vue',
		'ui.uploader.core',
		'ui.icon-set.animated',
		'tasks.v2.component.elements.chip',
		'tasks.v2.const',
		'tasks.v2.lib.field-highlighter',
		'tasks.v2.lib.analytics',
		'ui.vue3.components.popup',
		'ui.vue3.components.button',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'disk.uploader.user-field-widget',
		'tasks.v2.provider.service.file-service',
	],
	'skip_core' => false,
];
