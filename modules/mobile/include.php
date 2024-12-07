<?php

use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use Bitrix\Main\UI\Copyright;

CModule::AddAutoloadClasses(
	'mobile',
	[
		'CMobileEvent' => 'classes/general/mobile_event.php',
		'CMobileHelper' => 'classes/general/mobile_helper.php',
		'MobileApplication' => 'classes/general/mobile_event.php',
	]
);

CJSCore::RegisterExt('mobile_voximplant', [
	'js' => '/bitrix/js/mobile/mobile_voximplant.js',
]);

CJSCore::RegisterExt('mobile_uploader', [
	'js' => ['/bitrix/js/mobile/uploader.js'],
	'rel' => [
		'ui.progressbarjs',
		'mobile_ui',
	],
]);

CJSCore::RegisterExt('mobile_ui', [
	'js' => '/bitrix/js/mobile/mobile_ui.js',
	'lang' => '/bitrix/modules/mobile/lang/' . LANGUAGE_ID . '/mobile_ui_messages.php',
	'css' => '/bitrix/js/mobile/css/mobile_ui.css',
	'rel' => ['mobile_fastclick', 'mobile_gesture'],
]);
CJSCore::RegisterExt('mobile_crm', [
	'js' => '/bitrix/js/mobile/mobile_crm.js',
	'lang' => '/bitrix/modules/mobile/lang/' . LANGUAGE_ID . '/crm_js_messages.php',
]);
CJSCore::RegisterExt('mobile_tools', [
	'js' => '/bitrix/js/mobile/mobile_tools.js',
	'lang' => '/bitrix/modules/mobile/lang/' . LANGUAGE_ID . '/mobile_tools_messages.php',
	'oninit' => function () {
		return [
			'lang_additional' => [
				'can_perform_calls' => \Bitrix\Main\Loader::includeModule('voximplant') && Bitrix\Voximplant\Security\Helper::canCurrentUserPerformCalls() ? 'Y' : 'N',
			],
		];
	},
]);

EventManager::getInstance()->addEventHandler('main', 'onGetThirdPartySoftware', function () {
	return new EventResult(
		EventResult::SUCCESS,
		[
			(new Copyright('Redux Toolkit'))
				->setCopyright('Copyright (c) 2018 Mark Erikson')
				->setProductUrl('https://redux-toolkit.js.org')
				->setLicence(Copyright::LICENCE_MIT),

			(new Copyright('Redux-State-Sync 3'))
				->setCopyright('Copyright (c) 2018 MU AOHUA')
				->setProductUrl('https://github.com/AOHUA/redux-state-sync')
				->setLicence(Copyright::LICENCE_MIT),

			(new Copyright('Logger for Redux'))
				->setCopyright('Copyright (c) 2016 Eugene Rodionov')
				->setProductUrl('https://github.com/LogRocket/redux-logger')
				->setLicence(Copyright::LICENCE_MIT),

			(new Copyright('redux-batched-subscribe'))
				->setCopyright('Copyright (c) 2016 Terry Appleby')
				->setProductUrl('https://github.com/tappleby/redux-batched-subscribe')
				->setLicence(Copyright::LICENCE_MIT),

			(new Copyright('redux-batched-actions'))
				->setCopyright('Copyright (c) 2016 Tim Shelburne')
				->setProductUrl('https://github.com/tshelburne/redux-batched-actions')
				->setLicence(Copyright::LICENCE_MIT),
		],
	);
});
