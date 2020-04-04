<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	"js" => [
		"/bitrix/js/crm/tracking/tracker/script.js",
	],
	"rel" => [
		'ui.webpacker', 'crm.tracking.guest'
	],
	"options" => [
		"webpacker" => [
			"callMethod" => "window.b24Tracker.Manager.Instance.load",
		]
	]
];