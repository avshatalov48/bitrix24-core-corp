<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/user-custom-tag-selector.bundle.css',
	'js' => 'dist/user-custom-tag-selector.bundle.js',
	'rel' => [
		'main.core',
		'ui.entity-selector',
		'ui.vue3.components.menu',
		'ui.icon-set.api.vue',
		'ui.icon-set.api.core',
		'ui.icon-set.outline',
		'ui.tooltip',
		'tasks.v2.component.elements.user-avatar',
		'tasks.v2.lib.entity-selector-dialog',
		'tasks.v2.provider.service.user-service',
		'ui.loader',
	],
	'skip_core' => false,
];
