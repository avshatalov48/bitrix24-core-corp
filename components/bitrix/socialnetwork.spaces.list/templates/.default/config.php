<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'script.css',
	'js' => 'script.js',
	'rel' => [
		'ui.vue3',
		'main.popup',
		'main.date',
		'ui.avatar-editor',
		'main.loader',
		'ui.vue3.vuex',
		'main.core',
		'main.core.events',
		'pull.client',
	],
	'skip_core' => false,
];