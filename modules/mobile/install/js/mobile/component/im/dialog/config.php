<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/mobile/component/im/dialog/dist/dialog.bundle.js',
	],
	'css' =>[
		'/bitrix/js/mobile/component/im/dialog/dist/dialog.bundle.css',
	],
	'rel' => [
		'main.polyfill.core',
		'pull.components.status',
		'main.date',
		'mobile.pull.client',
		'im.model',
		'im.controller',
		'im.provider.pull',
		'im.provider.rest',
		'im.tools.localstorage',
		'im.tools.timer',
		'im.tools.logger',
		'im.const',
		'im.utils',
		'im.component.dialog',
		'im.component.quotepanel',
		'ui.vue.vuex',
		'ui.vue',
	],
	'skip_core' => true,
];