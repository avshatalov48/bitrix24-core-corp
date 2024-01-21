<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/timeline.bundle.css',
	'js' => 'dist/timeline.bundle.js',
	'rel' => [
		'ui.cnt',
		'rest.client',
		'ui.label',
		'main.date',
		'main.popup',
		'ui.buttons',
		'ui.hint',
		'main.core.events',
		'main.loader',
		'ui.vue3',
		'ui.notification',
		'crm.datetime',
		'main.core',
		'crm.timeline.item',
		'crm.timeline.tools',
	],
	'skip_core' => false,
];
