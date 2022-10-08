<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => [
		'/bitrix/js/imopenlines/component/form/dist/form.bundle.js',
	],
	'css' => [
		'/bitrix/js/imopenlines/component/form/dist/form.bundle.css',
	],
	'rel' => [
		'main.polyfill.core',
		'ui.vue',
		'ui.vue.vuex',
		'ui.fonts.opensans',
	],
	'skip_core' => true,
];
