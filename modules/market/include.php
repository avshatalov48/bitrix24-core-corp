<?php

CJSCore::registerExt(
	'market.application',
	[
		'js' => '/bitrix/js/market/application.js',
		'css' => '/bitrix/js/market/css/application.css',
		'lang' => BX_ROOT . '/modules/market/lang/' . LANGUAGE_ID . '/js/application.php',
		'rel' => [
			'ajax',
			'popup',
			'access',
			'sidepanel',
			'ui.notification',
			'ui.info-helper',
		],
	]
);