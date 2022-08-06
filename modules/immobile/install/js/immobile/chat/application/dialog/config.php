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
		'pull.component.status',
		'ui.fonts.opensans',
		'ui.vue',
		'im.component.dialog',
		'im.view.quotepanel',
		'ui.vue.vuex',
		'ui.vue.components.smiles',
		'im.lib.utils',
		'main.core.events',
		'im.const',
		'im.event-handler',
		'im.lib.logger',
		'im.lib.timer',
	],
	'skip_core' => true,
];