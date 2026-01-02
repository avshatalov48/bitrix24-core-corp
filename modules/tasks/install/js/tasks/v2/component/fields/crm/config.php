<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$crmIntegration = $GLOBALS['USER_FIELD_MANAGER']->getUserFields('TASKS_TASK')['UF_CRM_TASK']['SETTINGS'];

return [
	'css' => 'dist/crm.bundle.css',
	'js' => 'dist/crm.bundle.js',
	'rel' => [
		'ui.system.skeleton.vue',
		'ui.icon-set.api.vue',
		'tasks.v2.component.elements.add-background',
		'tasks.v2.component.elements.field-add',
		'ui.vue3.components.rich-loc',
		'ui.system.typography.vue',
		'ui.system.menu.vue',
		'tasks.v2.component.elements.hover-pill',
		'tasks.v2.lib.href-click',
		'main.core',
		'tasks.v2.core',
		'tasks.v2.provider.service.crm-service',
		'tasks.v2.provider.service.task-service',
		'tasks.v2.lib.entity-selector-dialog',
		'ui.system.chip.vue',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'tasks.v2.const',
		'tasks.v2.lib.field-highlighter',
	],
	'skip_core' => false,
	'settings' => [
		'crmIntegration' => $crmIntegration,
	],
];
