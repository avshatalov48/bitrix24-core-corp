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
		'popup',
		'ui.buttons',
		'ui.buttons.icons',
		'ui.forms',
		'ui.fonts.opensans',
		'ui.pinner',
		'currency',
		'ui.dropdown',
		'ui.common',
		'ui.alerts',
		'main.core.events',
		'marketplace',
		'applayout',
		'main.popup',
		'salescenter.manager',
		'ui.vue.vuex',
		'main.core',
		'salescenter.deliveryselector',
		'ui.vue',
	],
	'skip_core' => false,
];