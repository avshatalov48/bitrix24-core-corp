<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/audio-player.bundle.css',
	'js' => 'dist/audio-player.bundle.js',
	'rel' => [
		'main.popup',
		'ui.vue3',
		'ui.vue3.components.audioplayer',
		'main.core',
	],
	'skip_core' => false,
];