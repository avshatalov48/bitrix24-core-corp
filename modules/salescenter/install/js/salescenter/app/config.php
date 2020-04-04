<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => [
		'/bitrix/js/salescenter/app/dist/app.bundle.css',
		'/bitrix/components/bitrix/ui.sidepanel.wrappermenu/templates/.default/style.css',
		'/bitrix/components/bitrix/ui.button.panel/templates/.default/style.css',
	],
	'js' => '/bitrix/js/salescenter/app/dist/app.bundle.js',
	'rel' => [
		'rest.client',
		'ui.notification',
		'main.loader',
		'main.core',
		'popup',
		'ui.buttons',
		'ui.buttons.icons',
		'ui.forms',
		'ui.fonts.opensans',
		'ui.pinner',
		'ui.vue.vuex',
		'salescenter.manager',
		'currency',
		'ui.vue',
		'ui.dropdown',
		'ui.common',
		'ui.alerts',
	],
	'skip_core' => false,
];