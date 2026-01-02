<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/date-plan.bundle.css',
	'js' => 'dist/date-plan.bundle.js',
	'rel' => [
		'tasks.v2.component.elements.field-list',
		'tasks.v2.component.elements.hover-pill',
		'tasks.v2.component.elements.field-add',
		'ui.system.chip.vue',
		'ui.icon-set.api.core',
		'main.core',
		'main.date',
		'ui.date-picker',
		'ui.vue3.vuex',
		'ui.vue3.components.button',
		'ui.system.input.vue',
		'ui.vue3.components.menu',
		'ui.icon-set.api.vue',
		'ui.icon-set.outline',
		'tasks.v2.const',
		'tasks.v2.component.elements.bottom-sheet',
		'tasks.v2.lib.field-highlighter',
		'tasks.v2.lib.calendar',
		'tasks.v2.lib.timezone',
		'tasks.v2.provider.service.task-service',
		'ui.system.typography.vue',
		'ui.switcher',
		'ui.vue3.components.switcher',
		'ui.vue3.directives.hint',
		'tasks.v2.component.elements.hint',
	],
	'skip_core' => false,
];
