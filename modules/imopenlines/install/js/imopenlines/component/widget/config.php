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
		'im.component.dialog',
		'im.component.textarea',
		'im.view.quotepanel',
		'imopenlines.component.message',
		'imopenlines.component.form',
		'rest.client',
		'im.provider.rest',
		'main.date',
		'pull.client',
		'ui.vue.components.crm.form',
		'im.controller',
		'im.lib.cookie',
		'im.lib.localstorage',
		'im.lib.uploader',
		'im.lib.utils',
		'im.lib.logger',
		'im.mixin',
		'main.md5',
		'main.core.events',
		'im.const',
		'main.core.minimal',
		'ui.vue.vuex',
		'ui.vue',
	],
	'skip_core' => true,
];