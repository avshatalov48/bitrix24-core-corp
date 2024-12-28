<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/timeline.bundle.css',
	'js' => 'dist/timeline.bundle.js',
	'rel' => [
		'rest.client',
		'ui.analytics',
		'main.date',
		'ui.buttons',
		'ui.vue3',
		'main.loader',
		'crm.field.color-selector',
		'ui.vue3.directives.hint',
		'main.popup',
		'ui.label',
		'ui.hint',
		'ui.cnt',
		'ui.notification',
		'crm.timeline.item',
		'main.core',
		'crm.timeline.tools',
		'main.core.events',
	],
	'skip_core' => false,
];
