<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/imopenlines/component/message/dist/message.bundle.js',
	],
	'css' => [
		'/bitrix/js/imopenlines/component/message/dist/message.bundle.css',
	],
	'rel' => [
		'main.polyfill.core',
		'ui.vue',
		'ui.vue.vuex',
		'im.view.message',
		'ui.fonts.opensans',
		'main.core.events',
	],
	'skip_core' => true,
];