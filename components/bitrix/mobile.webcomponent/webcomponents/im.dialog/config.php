<?
CModule::IncludeModule('mobileapp');

return [
	"rel" => [
		"date",
		"ls",
		"fx",
		"user",
		"mobile_ui",
		"mobile_tools",
		"mobile_uploader",
		"mobile_fastclick",
		"mobile_gesture"
	],
	"deps" => [
		"chat/uploaderconst",
		"chat/tables",
		"chat/restrequest",
		"chat/dialogcache",
		"chat/timer",
		"chat/utils",
		"chat/messengercommon",
		"chat/dataconverter",
		"webcomponent/parameters",
		"webcomponent/urlrewrite",
	],
	"js" => [
		"/bitrix/js/im/common.js",
	],
	"css"=>[
		"/bitrix/js/im/css/common.css",
	],
	"images"=>[
		// mobile
		"/bitrix/js/mobile/images/cross.svg",
		"/bitrix/js/mobile/images/check.svg",
	],
	"langs" => [
		// main
		"/bitrix/modules/main/js_core_uploader.php",
		// im
		"/bitrix/modules/im/js_common.php",
		"/bitrix/modules/im/js_mobile.php",
	],
	"exclude" => []
];