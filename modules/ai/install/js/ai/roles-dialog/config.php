<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/roles-dialog.bundle.css',
	'js' => 'dist/roles-dialog.bundle.js',
	'rel' => [
		'ai.engine',
		'ui.notification',
		'main.popup',
		'ui.vue3.components.hint',
		'ui.label',
		'ui.icon-set.animated',
		'main.core.events',
		'ui.vue3.pinia',
		'ui.icon-set.api.vue',
		'ui.icon-set.api.core',
		'main.core',
	],
	'skip_core' => false,
];