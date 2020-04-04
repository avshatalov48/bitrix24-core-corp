<?
return [
	"rel" => [
		"main:date",
		"main:ls",
		"main:fx",
		"main:user",
		"mobile:mobile_ui",
		"mobile:mobile_tools",
		"mobile:mobile_uploader",
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