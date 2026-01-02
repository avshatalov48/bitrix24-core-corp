<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/check-list.bundle.css',
	'js' => 'dist/check-list.bundle.js',
	'rel' => [
		'main.popup',
		'ui.entity-selector',
		'ui.icon-set.animated',
		'tasks.v2.component.elements.participant-list',
		'tasks.v2.lib.entity-selector-dialog',
		'ui.vue3.components.button',
		'ui.vue3.components.popup',
		'tasks.v2.component.elements.bottom-sheet',
		'ui.vue3.components.menu',
		'tasks.v2.component.elements.user-checkbox',
		'tasks.v2.component.elements.progress-bar',
		'ui.vue3.vuex',
		'tasks.v2.provider.service.check-list-service',
		'ui.system.skeleton.vue',
		'disk.uploader.user-field-widget',
		'tasks.v2.component.elements.growing-text-area',
		'tasks.v2.component.elements.user-avatar-list',
		'ui.icon-set.actions',
		'ui.vue3.directives.hint',
		'ui.icon-set.api.vue',
		'tasks.v2.core',
		'tasks.v2.component.elements.hint',
		'main.core.events',
		'ui.notification',
		'main.core',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'tasks.v2.lib.field-highlighter',
		'tasks.v2.component.elements.chip',
		'tasks.v2.provider.service.file-service',
		'tasks.v2.const',
	],
	'skip_core' => false,
];
