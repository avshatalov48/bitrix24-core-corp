<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/imopenlines/component/widget/dist/widget.bundle.js',
	],
	'css' =>[
		'/bitrix/js/imopenlines/component/widget/dist/widget.bundle.css',
	],
	'rel' => [
		'main.polyfill.core',
		'main.polyfill.customevent',
		'pull.component.status',
		'ui.vue.components.smiles',
		'im.view.dialog',
		'im.view.textarea',
		'im.view.quotepanel',
		'imopenlines.component.message',
		'imopenlines.component.form',
		'rest.client',
		'im.provider.rest',
		'main.date',
		'pull.client',
		'im.controller',
		'im.lib.cookie',
		'im.lib.localstorage',
		'im.lib.logger',
		'main.md5',
		'im.const',
		'ui.icons',
		'ui.forms',
		'im.lib.utils',
		'ui.vue',
		'ui.vue.vuex',
	],
	'skip_core' => true,
];