<?
return [
	"js" => [
		// core
		"core/core.js" => "/bitrix/js/main/core/core.js",
		"core/core_ls.js" => "/bitrix/js/main/core/core_ls.js",
		"core/core_fx.js" => "/bitrix/js/main/core/core_fx.js",
		"core/main.date.js" => "/bitrix/js/main/date/main.date.js",
		"core/core_date.js" => "/bitrix/js/main/core/core_date.js",
		"core/core_user.js" => "/bitrix/js/main/core/core_user.js",
		// mobile
		"module/js/mobile/mobile_tools.js" => "/bitrix/js/mobile/mobile_tools.js",
		"module/js/mobileapp/mobile_ui.js" => "/bitrix/js/mobile/mobile_ui.js",
		"module/js/mobileapp/fastclick.js" => "/bitrix/js/mobileapp/fastclick.js",
		"module/js/mobileapp/gesture.js" => "/bitrix/js/mobileapp/gesture.js",
		// uploader
		"module/js/uploader/progressbar.js" => "/bitrix/js/mobile/external/progressbar.js",
		"module/js/uploader/uploader.js" => "/bitrix/js/mobile/uploader.js",
		// im
		"module/js/im/common.js" => "/bitrix/js/im/common.js",
		// chat common api
		"jscomponent/db.js" => "/bitrix/components/bitrix/mobile.jscomponent/jsextensions/db/extension.js",
		"jscomponent/restrequest.js" => "/bitrix/components/bitrix/mobile.jscomponent/jsextensions/chat/restrequest/extension.js",
		"jscomponent/dialogcache.js" => "/bitrix/components/bitrix/mobile.jscomponent/jsextensions/chat/dialogcache/extension.js",
		"jscomponent/timer.js" => "/bitrix/components/bitrix/mobile.jscomponent/jsextensions/chat/timer/extension.js",
		"jscomponent/utils.js" => "/bitrix/components/bitrix/mobile.jscomponent/jsextensions/chat/utils/extension.js",
		"jscomponent/messengercommon.js" => "/bitrix/components/bitrix/mobile.jscomponent/jsextensions/chat/messengercommon/extension.js",
		"jscomponent/tables.js" => "/bitrix/components/bitrix/mobile.jscomponent/jsextensions/chat/tables/extension.js",
		"jscomponent/dataconverter.js" => "/bitrix/components/bitrix/mobile.jscomponent/jsextensions/chat/dataconverter/extension.js",
		"jscomponent/uploaderconst.js" => "/bitrix/components/bitrix/mobile.jscomponent/jsextensions/chat/uploaderconst/extension.js",
		"jscomponent/webcomponentparameters.js" => "/bitrix/components/bitrix/mobile.jscomponent/jsextensions/webcomponent/parameters/extension.js",
		"jscomponent/webcomponent_urlrewrite.js" => "/bitrix/components/bitrix/mobile.jscomponent/jsextensions/webcomponent/urlrewrite/extension.js",
	],
	"css"=>[
		// core
		"core/css/core_date.css" => "/bitrix/js/main/core/css/core_date.css",
		// mobile
		"module/mobile/css/mobile_ui.css" => "/bitrix/js/mobile/css/mobile_ui.css",
		// im
		"module/im/css/common.css" => "/bitrix/js/im/css/common.css",
	],
	"images"=>[
		// mobile
		"module/mobile/images/cross.svg" => "/bitrix/js/mobile/images/cross.svg",
		"module/mobile/images/check.svg" => "/bitrix/js/mobile/images/check.svg",
	],
	"langs" => [
		// chat common api
		"/bitrix/components/bitrix/mobile.jscomponent/jsextensions/chat/messengercommon/lang/#LANG_ID#/extension.php",
		"/bitrix/components/bitrix/mobile.jscomponent/jsextensions/chat/dataconverter/lang/#LANG_ID#/extension.php",
		// core
		"/bitrix/modules/main/lang/#LANG_ID#/date_format.php",
		"/bitrix/modules/main/lang/#LANG_ID#/js_core_user.php",
		"/bitrix/modules/main/lang/#LANG_ID#/js_core_uploader.php",
		// mobile
		"/bitrix/modules/mobile/lang/#LANG_ID#/mobile_tools_messages.php",
		"/bitrix/modules/mobile/lang/#LANG_ID#/mobile_ui_messages.php",
		// im
		"/bitrix/modules/im/lang/#LANG_ID#/js_common.php",
		"/bitrix/modules/im/lang/#LANG_ID#/js_mobile.php",
	],
];