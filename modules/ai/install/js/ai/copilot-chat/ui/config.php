<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/copilot-chat.bundle.css',
	'js' => 'dist/copilot-chat.bundle.js',
	'rel' => [
		'ui.icon-set.actions',
		'ai.speech-converter',
		'main.core.events',
		'ui.icon-set.api.core',
		'ui.vue3',
		'ui.icon-set.main',
		'main.date',
		'main.popup',
		'ui.icon-set.api.vue',
		'ui.bbcode.formatter.html-formatter',
		'main.loader',
		'main.core',
	],
	'skip_core' => false,
];
