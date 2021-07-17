<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/timeline.bundle.css',
	'js' => 'dist/timeline.bundle.js',
	'rel' => [
		'main.core.events',
		'currency',
		'ui.notification',
		'ui.vue',
		'main.core',
		'pull.client',
	],
	'skip_core' => false,
];