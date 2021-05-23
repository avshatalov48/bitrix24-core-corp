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
		'salescenter.component.stage-block.send',
		'salescenter.marketplace',
		'salescenter.component.stage-block.tile',
		'salescenter.component.stage-block.hint',
		'salescenter.tile',
		'salescenter.manager',
		'catalog.product-form',
		'currency',
		'ui.dropdown',
		'ui.common',
		'ui.alerts',
		'main.popup',
		'main.core.events',
		'ui.vue.vuex',
		'ui.vue',
		'salescenter.deliveryselector',
		'salescenter.component.stage-block.sms-message',
		'main.core',
		'salescenter.component.stage-block',
		'salescenter.component.stage-block.automation',
		'salescenter.automation-stage',
		'salescenter.component.stage-block.timeline',
		'salescenter.timeline',
		'popup',
		'ui.buttons',
		'ui.buttons.icons',
		'ui.forms',
		'ui.fonts.opensans',
		'ui.pinner',
	],
	'skip_core' => false,
];