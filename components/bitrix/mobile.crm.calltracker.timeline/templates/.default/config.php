<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'script.css',
	'js' => 'script.js',
	'rel' => [
		'main.loader',
		'main.polyfill.intersectionobserver',
		'ui.vue.components.audioplayer',
		'ui.vue',
		'main.core',
		'mobile.utils',
		'main.core.events',
	],
	'skip_core' => false,
];