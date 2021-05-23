<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'./dist/body.bundle.js',
	],
	'css' => [
		'./dist/body.bundle.css',
	],
	'rel' => [
		'im.view.element.media',
		'im.view.element.attach',
		'im.view.element.keyboard',
		'im.view.element.chatteaser',
		'ui.vue.components.reaction',
		'ui.vue',
		'ui.vue.vuex',
		'im.model',
		'im.const',
		'im.lib.utils',
		'main.core',
	],
	'skip_core' => false,
];