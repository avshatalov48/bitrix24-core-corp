<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/task-compact-card.bundle.css',
	'js' => 'dist/task-compact-card.bundle.js',
	'rel' => [
		'ui.vue3',
		'ui.vue3.mixins.loc-mixin',
		'main.core.events',
		'main.popup',
		'ui.vue3.vuex',
		'ui.notification-manager',
		'ui.uploader.tile-widget',
		'ui.icon-set.api.vue',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'tasks.v2.component.add-task-button',
		'tasks.v2.component.fields.title',
		'tasks.v2.component.fields.importance',
		'tasks.v2.component.fields.description',
		'tasks.v2.component.elements.field-list',
		'tasks.v2.component.drop-zone',
		'tasks.v2.component.fields.responsible',
		'tasks.v2.component.fields.deadline',
		'tasks.v2.component.fields.check-list',
		'tasks.v2.component.fields.files',
		'tasks.v2.component.fields.group',
		'tasks.v2.provider.service.task-service',
		'tasks.v2.provider.service.check-list-service',
		'main.core',
		'ui.vue3.components.button',
		'tasks.v2.core',
		'tasks.v2.const',
		'tasks.v2.component.elements.hint',
		'tasks.v2.lib.field-highlighter',
		'tasks.v2.lib.analytics',
		'tasks.v2.lib.aha-moments',
		'tasks.v2.provider.service.file-service',
	],
	'skip_core' => false,
];
