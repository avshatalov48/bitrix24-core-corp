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
		'immobile.chat.application.core',
		'main.date',
		'mobile.pull.client',
		'im.model',
		'im.provider.rest',
		'im.lib.localstorage',
		'im.lib.timer',
		'pull.component.status',
		'ui.vue',
		'im.lib.logger',
		'im.const',
		'im.lib.utils',
		'im.component.dialog',
		'im.view.quotepanel',
		'main.core.events',
		'ui.vue.vuex',
		'ui.vue.components.smiles',
		'im.mixin',
	],
	'skip_core' => true,
];