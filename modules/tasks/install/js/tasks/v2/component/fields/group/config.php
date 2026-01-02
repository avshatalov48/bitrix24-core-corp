<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/group.bundle.css',
	'js' => 'dist/group.bundle.js',
	'rel' => [
		'tasks.v2.component.elements.hover-pill',
		'tasks.v2.component.elements.field-add',
		'tasks.v2.lib.height-transition',
		'ui.vue3.components.menu',
		'ui.icon-set.crm',
		'tasks.v2.component.elements.user-custom-tag-selector',
		'ui.icon-set.api.vue',
		'tasks.v2.lib.color',
		'ui.vue3.directives.hint',
		'ui.vue3.components.popup',
		'ui.vue3.components.button',
		'tasks.v2.lib.open-group',
		'tasks.v2.provider.service.group-service',
		'tasks.v2.provider.service.user-service',
		'tasks.v2.lib.entity-selector-dialog',
		'main.core',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'tasks.v2.core',
		'tasks.v2.const',
		'tasks.v2.component.elements.chip',
		'tasks.v2.component.elements.hint',
		'tasks.v2.lib.field-highlighter',
		'tasks.v2.lib.analytics',
		'tasks.v2.provider.service.task-service',
	],
	'skip_core' => false,
];
