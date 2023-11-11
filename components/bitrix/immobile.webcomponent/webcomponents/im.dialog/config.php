<?php

CModule::IncludeModule('mobileapp');

return [
	'rel' => [
		'main.md5',
		'date',
		'ls',
		'fx',
		'user',
		'mobile_ui',
		'mobile_tools',
		'mobile_uploader',
		'mobile_fastclick',
		'mobile_gesture',
		'im.v2.lib.parser'
	],
	'deps' => [
		'im:chat/uploaderconst',
		'im:chat/tables',
		'im:chat/restrequest',
		'im:chat/dialogcache',
		'im:chat/timer',
		'im:chat/utils',
		'im:chat/messengercommon',
		'im:chat/dataconverter',
		'webcomponent/parameters',
		'webcomponent/urlrewrite',
	],
	'js' => [
		'/bitrix/js/im/common.js',
	],
	'css'=>[
		'/bitrix/js/im/css/common.css',
		'/bitrix/js/im/css/dark_im.css',
	],
	'images'=>[
		// mobile
		'/bitrix/js/mobile/images/cross.svg',
		'/bitrix/js/mobile/images/check.svg',
	],
	'langs' => [
		// main
		'/bitrix/modules/main/js_core_uploader.php',
		// im
		'/bitrix/modules/im/js_common.php',
		'/bitrix/modules/im/js_mobile.php',
	],
	'exclude' => []
];
