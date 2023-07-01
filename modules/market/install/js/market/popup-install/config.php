<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/popup-install.bundle.css',
	'js' => 'dist/popup-install.bundle.js',
	'rel' => [
		'main.polyfill.core',
		'market.install-store',
		'ui.vue3.pinia',
	],
	'skip_core' => true,
];