<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/participant-list.bundle.css',
	'js' => 'dist/participant-list.bundle.js',
	'rel' => [
		'main.core',
		'ui.entity-selector',
		'ui.vue3.components.menu',
		'ui.vue3.components.popup',
		'ui.icon-set.api.vue',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'tasks.v2.component.elements.add-background',
		'tasks.v2.component.elements.field-add',
		'tasks.v2.lib.user-selector-dialog',
		'tasks.v2.lib.height-transition',
		'tasks.v2.lib.href-click',
		'tasks.v2.provider.service.user-service',
		'tasks.v2.component.elements.user-avatar-list',
		'tasks.v2.core',
		'tasks.v2.const',
	],
	'skip_core' => false,
];
