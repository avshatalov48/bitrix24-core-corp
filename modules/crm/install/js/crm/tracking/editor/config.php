<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	"js" => [
		"/bitrix/js/crm/tracking/editor/script.js",
	],
	"css" => [
		"/bitrix/js/crm/tracking/editor/style.css",
	],
	"layout" => [
		"/bitrix/js/crm/tracking/editor/layout.html",
	],
	"rel" => [
		'ui.webpacker',
		'crm.tracking.connector',
	],
	"lang" => "/bitrix/modules/crm/install/js/tracking.editor.php",
	"options" => [
		"webpacker" => [
			"useLangCamelCase" => true,
			"deleteLangPrefixes" => ["CRM_TRACKING_EDITOR_"],
		]
	]
];