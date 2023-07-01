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
		'main.polyfill.customevent',
		'pull.component.status',
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
		'im.lib.utils',
		'main.md5',
		'im.lib.uploader',
		'main.core',
		'im.lib.logger',
		'im.event-handler',
		'im.const',
		'main.core.minimal',
		'ui.vue.vuex',
		'ui.vue',
		'main.core.events',
		'ui.vue.components.smiles',
	],
	'skip_core' => false,
];