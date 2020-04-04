<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	"js" => [
		"/bitrix/js/crm/tracking/guest/script.js",
	],
	"rel" => [
		'ui.webpacker'
	],
	"options" => [
		"webpacker" => [
			"properties" => [
				"lifespan" => \Bitrix\Main\Loader::includeModule('crm')
					? \Bitrix\Crm\Tracking\Settings::getAttrWindow()
					:
					null,
				"canRegisterOrder" => \Bitrix\Main\Loader::includeModule('crm')
					&& \Bitrix\Crm\Tracking\Channel\Order::isConfigured()
			]
		]
	]
];