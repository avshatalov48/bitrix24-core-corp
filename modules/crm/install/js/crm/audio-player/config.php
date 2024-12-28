<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/audio-player.bundle.css',
	'js' => 'dist/audio-player.bundle.js',
	'rel' => [
		'ui.vue3.components.audioplayer',
		'main.core',
		'ui.vue3',
	],
	'skip_core' => false,
];