<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/timeline.bundle.css',
	'js' => 'dist/timeline.bundle.js',
	'rel' => [
		'ui.notification',
		'currency',
		'ui.design-tokens',
		'pull.client',
		'crm.timeline.tools',
		'main.core.events',
		'crm.timeline.item',
		'ui.vue',
		'main.core',
	],
	'skip_core' => false,
];
