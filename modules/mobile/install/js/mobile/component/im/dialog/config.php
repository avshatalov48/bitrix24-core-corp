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
		'main.date',
		'main.md5',

		'rest.client',

		'pull.client',

		'ui.dexie',

		'ui.vue',
		'ui.vue.vuex',

		'im.const',
		'im.controller',
		'im.model',

		'im.provider.pull',

		'im.component.dialog',

		'im.utils',
		'im.tools.localstorage',
		'im.tools.timer',
		'im.tools.logger',
	],
	'skip_core' => true,
	'bundle_js' => 'mobile_im_dialog',
];