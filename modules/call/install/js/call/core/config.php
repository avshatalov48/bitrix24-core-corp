<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Call\Settings;
use Bitrix\Call\Integration\AI\CallAISettings;

if (!Loader::includeModule('im') || !Loader::includeModule('call'))
{
	return [];
}


return [
	'js' => [
		'./dist/call.bundle.js',
	],
	'css' => [
		'./dist/call.bundle.css',
	],
	'rel' => [
		'im.lib.utils',
		'call.core',
		'im.v2.lib.promo',
		'ui.dialogs.messagebox',
		'ui.buttons',
		'im.v2.lib.desktop-api',
		'im.v2.const',
		'im.v2.lib.desktop',
		'intranet.desktop-download',
		'im.v2.lib.utils',
		'call.lib.call-token-manager',
		'call.lib.analytics',
		'main.core',
		'main.popup',
		'main.core.events',
		'ui.switcher',
		'call.lib.settings-manager',
		'call.component.user-list-popup',
		'call.component.user-list',
		'loader',
		'resize_observer',
		'webrtc_adapter',
		'im.lib.localstorage',
		'ui.hint',
		'voximplant',
	],
	'oninit' => function ()
	{
		$features = [];
		$limits = \Bitrix\Im\Limit::getTypesForJs();
		foreach ($limits as $limit)
		{
			$features[$limit['id']] = [
				'enable' => !$limit['active'],
				'articleCode' => $limit['articleCode'],
			];
		}

		return [
			'lang_additional' => [
				'turn_server' => \Bitrix\Im\Call\Call::getTurnServer(),
				'turn_server_firefox' => \Bitrix\Im\Call\Call::getTurnServer(),
				'turn_server_login' => Option::get('call', 'turn_server_login'),
				'turn_server_password' => Option::get('call', 'turn_server_password'),
				'turn_server_max_users' => Option::get('call', 'turn_server_max_users'),
				'call_server_enabled' => \Bitrix\Im\Call\Call::isCallServerEnabled() ? 'Y' : 'N',
				'call_beta_ios_enabled' => \Bitrix\Im\Call\Call::isIosBetaEnabled() ? 'Y' : 'N',
				'call_server_max_users' => \Bitrix\Im\Call\Call::getMaxCallServerParticipants(),
				'call_log_service' => \Bitrix\Im\Call\Call::getLogService(),
				'call_client_selftest_url' => \Bitrix\Call\Library::getClientSelfTestUrl(),
				'call_collect_stats' => Option::get('im', 'collect_call_stats', 'N'),
				'call_docs_status' => \Bitrix\Im\Integration\Disk\Documents::getDocumentsInCallStatus(),
				'call_resumes_status' => \Bitrix\Im\Integration\Disk\Documents::getResumesOfCallStatus(),
				'call_features' => $features,
				'conference_chat_enabled' => Settings::isConferenceChatEnabled(),
				'call_use_tcp_sdp' => Settings::useTcpSdp(),
				'user_jwt' => \Bitrix\Call\JwtCall::getUserJwt((int)CurrentUser::get()->getId()),
			],
			'settings' => [
				'ai' => [
					'serviceEnabled' => Settings::isAIServiceEnabled(),
					'settingsEnabled' => CallAISettings::isEnableBySettings(),
					'recordingMinUsers' => CallAISettings::getRecordMinUsers(),
					'agreementAccepted' => CallAISettings::isAgreementAccepted(),
					'tariffAvailable' => CallAISettings::isTariffAvailable(),
					'baasAvailable' => CallAISettings::isBaasServiceHasPackage(),
					'feedBackLink' => CallAISettings::getFeedBackLink(),
					'baasPromoSlider' => CallAISettings::getBaasSliderCode(),
					'helpSlider' => CallAISettings::getHelpSliderCode(),
					'disclaimerArticleCode' => CallAISettings::getDisclaimerArticleCode(),
				],
				'call' => [
					'jwtCallsEnabled' => Settings::isNewCallsEnabled(),
					'plainCallsUseJwt' => Settings::isPlainCallsUseNewScheme(),
					'callBalancerUrl' => Settings::getBalancerUrl(),
				],
				'isUserControlFeatureEnabled' => Settings::isNewCallsEnabled(),
				'isPictureInPictureFeatureEnabled' => Settings::isNewCallsEnabled(),
				'isDisableCameraNewJoinedUsersFeatureEnabled' => Settings::isDisableCameraNewJoinedUsersFeatureEnabled(),
				'isKibanaLogsEnabled' => Settings::isKibanaLogsEnabled(),
				'countDisableCameraNewJoinedUsersFeature' => Settings::countDisableCameraNewJoinedUsersFeature(),
				'isNewQOSEnabled' => Settings::isNewQOSEnabled(),
				'isAirDesignEnabled' => \Bitrix\Im\V2\Service\Locator::getMessenger()->getApplication()->isAirDesignEnabled(),
				'shouldHideQuickAccess' => \Bitrix\Im\V2\Service\Locator::getMessenger()->getApplication()->shouldHideQuickAccess(),
				'callInstalled' => \Bitrix\Main\ModuleManager::isModuleInstalled('call'),
			],
		];
	},
	'skip_core' => false,
];