<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('im'))
{
	return [];
}

use Bitrix\Main\Config\Option;
use Bitrix\Call\Settings;
use Bitrix\Call\Integration\AI\CallAISettings;

return [
	'js' => [
		'./dist/call.bundle.js',
	],
	'css' => [
		'./dist/call.bundle.css',
	],
	'rel' => [
		'im.lib.utils',
		'im.v2.lib.promo',
		'ui.switcher',
		'ui.dialogs.messagebox',
		'ui.buttons',
		'im.v2.lib.desktop-api',
		'im.v2.const',
		'intranet.desktop-download',
		'main.core.events',
		'im.v2.lib.utils',
		'main.core',
		'main.popup',
		'call.lib.analytics',
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
				'turn_server' => Option::get('im', 'turn_server'),
				'turn_server_firefox' => Option::get('im', 'turn_server_firefox'),
				'turn_server_login' => Option::get('im', 'turn_server_login'),
				'turn_server_password' => Option::get('im', 'turn_server_password'),
				'turn_server_max_users' => Option::get('im', 'turn_server_max_users'),
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
				],
				'isUserControlFeatureEnabled' => Settings::isUserControlFeatureEnabled(),
				'isPictureInPictureFeatureEnabled' => Settings::isPictureInPictureFeatureEnabled(),
				'isNewQOSEnabled' => Settings::isNewQOSEnabled(),
			],
		];
	},
	'skip_core' => false,
];
