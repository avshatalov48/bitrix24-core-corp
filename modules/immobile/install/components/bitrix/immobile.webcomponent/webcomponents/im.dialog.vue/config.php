<?php

return [
	'rel' => [
		'immobile.chat.application.dialog',
		'mobile_ui',
		'mobile_tools',
		'mobile_uploader',
	],
	'deps' => [
		'menu/backdrop',
		'menu/header',

		'webcomponent/parameters',
		'utils/urlrewrite/rules',
		'webcomponent/urlrewrite',
		'webcomponent/storage',

		'im:chat/widgetcache',
		'im:chat/const/background',
		'im:chat/performance',
	],
	'js' => [],
	'css'=>[],
	'images'=>[
		'/bitrix/js/ui/icons/disk/images/'
	],
	'langs' => [],
	'exclude' => []
];
