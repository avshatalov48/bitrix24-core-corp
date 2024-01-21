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
		'ui.design-tokens',
		'main.loader',
		'salescenter.marketplace',
		'salescenter.component.stage-block.tile',
		'salescenter.component.stage-block.hint',
		'catalog.product-form',
		'main.core.events',
		'ui.vue',
		'salescenter.deliveryselector',
		'ui.fonts.ruble',
		'currency',
		'salescenter.component.stage-block.automation',
		'salescenter.automation-stage',
		'salescenter.component.stage-block.timeline',
		'ui.icons.disk',
		'popup',
		'ui.buttons.icons',
		'ui.forms',
		'ui.fonts.opensans',
		'ui.pinner',
		'salescenter.component.stage-block.sms-message',
		'salescenter.manager',
		'salescenter.timeline',
		'ui.notification',
		'salescenter.tile',
		'ui.entity-selector',
		'salescenter.component.stage-block',
		'currency.currency-core',
		'salescenter.lib',
		'rest.client',
		'ui.vue.vuex',
		'ui.icon-set.actions',
		'main.core',
		'main.popup',
		'ui.buttons',
	],
	'skip_core' => false,
];