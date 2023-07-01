<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/popup-uninstall.bundle.css',
	'js' => 'dist/popup-uninstall.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'market.uninstall-store',
		'ui.vue3.pinia',
	],
	'skip_core' => true,
];