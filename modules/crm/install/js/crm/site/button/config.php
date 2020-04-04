<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	"js" => [
		"/bitrix/js/crm/site/button/script.js",
	],
	"css" => [
		"/bitrix/components/bitrix/crm.button.button/templates/.default/style.css",
		"/bitrix/components/bitrix/crm.button.webform/templates/.default/style.css"
	],
	"layout" => [
		"/bitrix/components/bitrix/crm.button.button/templates/.default/layout.html",
	],
	"rel" => ['ui.webpacker', 'crm.tracking.guest'],
	"options" => [
		"webpacker" => [
			"callMethod" => "window.BX.SiteButton.init",
		]
	]
];