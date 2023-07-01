<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/uninstall-store.bundle.css',
	'js' => 'dist/uninstall-store.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'ui.vue3.pinia',
		'main.core.events',
	],
	'skip_core' => true,
];