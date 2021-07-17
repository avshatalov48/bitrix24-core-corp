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
		'main.popup',
		'ui.notification',
		'main.loader',
		'catalog.product-form',
		'main.core.events',
		'popup',
		'ui.buttons',
		'ui.buttons.icons',
		'ui.forms',
		'ui.fonts.opensans',
		'ui.pinner',
		'salescenter.marketplace',
		'salescenter.component.stage-block.tile',
		'salescenter.component.stage-block.hint',
		'ui.vue.vuex',
		'ui.vue',
		'salescenter.deliveryselector',
		'ui.fonts.ruble',
		'currency',
		'salescenter.component.stage-block.sms-message',
		'salescenter.manager',
		'salescenter.component.stage-block.automation',
		'salescenter.automation-stage',
		'salescenter.component.stage-block.timeline',
		'salescenter.timeline',
		'salescenter.component.stage-block',
		'salescenter.tile',
		'main.core',
	],
	'skip_core' => false,
];