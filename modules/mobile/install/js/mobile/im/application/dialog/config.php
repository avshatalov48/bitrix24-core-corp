<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'./dist/dialog.bundle.js',
	],
	'css' =>[
		'./dist/dialog.bundle.css',
	],
	'rel' => [
		'main.polyfill.core',
		'mobile.im.application.core',
		'main.date',
		'mobile.pull.client',
		'im.model',
		'im.provider.rest',
		'im.lib.localstorage',
		'im.lib.timer',
		'pull.component.status',
		'im.lib.logger',
		'im.const',
		'im.lib.utils',
		'im.view.dialog',
		'im.view.quotepanel',
		'ui.vue.vuex',
		'ui.vue',
	],
	'skip_core' => true,
];