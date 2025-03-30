<?php
if (!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 *
 * @var $APPLICATION CMain
 * @var $USER CUser
 * @var $params array
 */
global $APPLICATION, $USER;

use Bitrix\Intranet\UI\LeftMenu\Preset\Manager;
use Bitrix\Main;
use Bitrix\Main\Authentication\ApplicationPasswordTable;
use Bitrix\Main\Context;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Web\Uri;
use Bitrix\Mobile\AvaMenu;

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS")
{
	header('Access-Control-Allow-Methods: POST, OPTIONS');
	header('Access-Control-Max-Age: 60');
	header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept');
	die('');
}

if (!IsModuleInstalled('bitrix24'))
{
	header('Access-Control-Allow-Origin: *');
}

$data = [
	"status" => "failed",
	"bitrix_sessid" => bitrix_sessid(),
];

$APPLICATION->RestartBuffer();
if (array_key_exists("logincheck", $_REQUEST) && $_REQUEST["login"])
{
	$res = CUser::getByLogin($_REQUEST["login"]);
	$data["exists"] = (bool)$res->fetch();
	if (!$data["exists"])
	{
		// AD\LDAP
		$ldapComponents = explode("\\", $_REQUEST["login"]);
		if (count($ldapComponents) == 2)
		{
			$res = CUser::getByLogin($ldapComponents[1]);
			$data["exists"] = (bool)$res->fetch();
		}
	}

	return $data;
}

if (array_key_exists("servercheck", $_REQUEST))
{
	$data['cloud'] = false;
	$data['host'] = null;

	if (ModuleManager::isModuleInstalled("bitrix24"))
	{
		$data['cloud'] = COption::GetOptionString('bitrix24', 'network', 'N') == 'Y';

		if (\Bitrix\Main\Loader::includeModule('socialservices'))
		{
			$data['host'] = (new Uri(\CSocServBitrix24Net::NETWORK_URL))->getHost();
		}
	}

	return $data;
}

$userData = CHTTP::ParseAuthRequest();
$login = $userData["basic"]["username"];
$isAlreadyAuthorized = $USER->IsAuthorized();
if (!$isAlreadyAuthorized)
{
	if (IsModuleInstalled('bitrix24'))
	{
		header('Access-Control-Allow-Origin: *');
	}

	if ($login)
	{
		if (\Bitrix\Main\Loader::includeModule('bitrix24') && ($captchaInfo = CBitrix24::getStoredCaptcha()))
		{
			$data["captchaCode"] = $captchaInfo["captchaCode"];
			$data["captchaURL"] = $captchaInfo["captchaURL"];
		}
		elseif ($APPLICATION->NeedCAPTHAForLogin($login))
		{
			$data["captchaCode"] = $APPLICATION->CaptchaGetCode();
		}

		if (\Bitrix\Main\Loader::includeModule("security") && \Bitrix\Security\Mfa\Otp::isOtpRequired())
		{
			$data["needOtp"] = true;
		}
	}

	if (Main\Loader::includeModule('socialservices'))
	{
		$lastUserStatus = \Bitrix\Socialservices\Network::getLastUserStatus();
		if ($lastUserStatus)
		{
			if (is_array($lastUserStatus))
			{
				$data["error"] = $lastUserStatus["error"];
				$data["error_message"] = $lastUserStatus["error_message"];
			}
			else
			{
				$data["error"] = $lastUserStatus;
			}
		}
	}

	CHTTP::SetStatus("401 Unauthorized");
}
else
{
	$isSignMobileModuleInstalled = \Bitrix\Main\Loader::includeModule("signmobile");
	$isSignModuleInstalled = \Bitrix\Main\Loader::includeModule("sign");

	if ($isSignMobileModuleInstalled && $isSignModuleInstalled)
	{
		$service = \Bitrix\SignMobile\Service\Container::instance()->getEventService();
		$service->checkDocumentsSentForSigning();
	}

	$event = new Bitrix\Main\Event("mobile", "onRequestSyncMail", [
		'urgent' => false,
	]);
	$event->send();

	$isExtranetModuleInstalled = \Bitrix\Main\Loader::includeModule("extranet");
	$intent = $_REQUEST['intent'] ?? null;
	if ($isExtranetModuleInstalled)
	{
		$extranetSiteId = \CExtranet::getExtranetSiteId();
		if (!$extranetSiteId)
		{
			$isExtranetModuleInstalled = false;
		}
	}

	$selectFields = [
		"FIELDS" => ["PERSONAL_PHOTO"],
	];

	if ($isExtranetModuleInstalled)
	{
		$selectFields["SELECT"] = ["UF_DEPARTMENT"];
	}

	$dbUser = CUser::GetList(
		["last_name" => "asc", "name" => "asc"],
		'',
		["ID" => $USER->GetID()],
		$selectFields
	);
	$curUser = $dbUser->Fetch();
	$avatarSource = "";

	if ((int)$curUser["PERSONAL_PHOTO"] > 0)
	{
		$avatar = CFile::ResizeImageGet(
			$curUser["PERSONAL_PHOTO"],
			["width" => 64, "height" => 64],
			BX_RESIZE_IMAGE_EXACT,
			false
		);

		if ($avatar && $avatar["src"] <> '')
		{
			$avatarSource = Uri::urnEncode($avatar["src"]);
		}
	}

	$bExtranetUser = ($isExtranetModuleInstalled && intval($curUser["UF_DEPARTMENT"][0]) <= 0);
	\Bitrix\Main\Loader::includeModule("pull");

	$siteId = (
	$bExtranetUser
		? $extranetSiteId
		: SITE_ID
	);

	$siteDir = SITE_DIR;
	if ($bExtranetUser)
	{
		$res = \CSite::getById($extranetSiteId);
		if (
			($extranetSiteFields = $res->fetch())
			&& ($extranetSiteFields["ACTIVE"] != "N")
		)
		{
			$siteDir = $extranetSiteFields["DIR"];
		}
	}

	$moduleVersion = (defined("MOBILE_MODULE_VERSION") ? MOBILE_MODULE_VERSION : "default");
	if (array_key_exists("IS_WKWEBVIEW", $_COOKIE) && $_COOKIE["IS_WKWEBVIEW"] == "Y")
	{
		$moduleVersion .= "_wkwebview";
	}

	$context = new \Bitrix\Mobile\Context([
		"extranet" => $bExtranetUser,
		"siteId" => $siteId,
		"siteDir" => $siteDir,
		"version" => $moduleVersion,
	]);

	$manager = new \Bitrix\Mobile\Tab\Manager($context);

	if ($intent)
	{
		if (isset($_REQUEST["first_open"]) && $_REQUEST["first_open"] === "Y")
		{
			$analyticEvent = new Main\Analytics\AnalyticsEvent('auth_complete', 'intranet', 'activation');
			$request = Context::getCurrent()->getRequest();
			$server = Context::getCurrent()->getServer();
			$host = defined('BX24_HOST_NAME') ? BX24_HOST_NAME : $server->getHttpHost();
			$analyticEvent
				->setSection($intent)
				->setSubSection('qrcode')
				->setType('auth')
				->setP1('platform_mobile')
				->setUserId($USER->getId())
				->send()
			;
		}

		if (str_starts_with($intent, 'preset_'))
		{
			$components = explode('_', $intent);
			if (count($components) >= 2)
			{
				$preset = $components[1];
				$manager->setPresetName($preset);
			}
		}
	}
	elseif (Main\Loader::includeModule('intranet') && Main\Loader::includeModule('crm'))
	{
		$lastInstalledPreset = CUserOptions::GetOption('mobile', 'last_installed_preset_by_left_menu');
		if ($lastInstalledPreset !== 'crm')
		{
			$preset = Manager::getPreset(null, $siteId);
			if ($preset->getCode() === 'crm' && $manager->getPresetName() !== 'crm')
			{
				$manager->setPresetName('crm');
				CUserOptions::SetOption('mobile', 'last_installed_preset_by_left_menu', 'crm');
			}
		}
	}

	$menuTabs = $manager->getActiveTabsData();

	$voximplantOptions = [
		'voximplantInstalled' => false,
		'voximplantServer' => '',
		'voximplantLogin' => '',
		'canPerformCalls' => false,
		'lines' => [],
		'defaultLineId' => '',
		'callLogService' => '',
	];
	if (Main\Loader::includeModule('voximplant'))
	{
		$voximplantServer = '';
		$voximplantLogin = '';
		$viUser = new CVoxImplantUser();
		$voximplantAuthorization = $viUser->getAuthorizationInfo($USER->getId());
		if ($voximplantAuthorization->isSuccess())
		{
			$voximplantAuthorizationData = $voximplantAuthorization->getData();
			$voximplantServer = $voximplantAuthorizationData['server'];
			$voximplantLogin = $voximplantAuthorizationData['login'];
		}

		$voximplantOptions = [
			'voximplantInstalled' => true,
			'voximplantServer' => $voximplantServer,
			'voximplantLogin' => $voximplantLogin,
			'canPerformCalls' => \Bitrix\Voximplant\Security\Helper::canCurrentUserPerformCalls(),
			'lines' => CVoxImplantConfig::GetLines(true, true),
			'defaultLineId' => CVoxImplantUser::getUserOutgoingLine($USER->getId()),
			'callLogService' => Main\Config\Option::get("im", "call_log_service", ""),
		];
	}

	$callOptions = [
		'useCustomTurnServer' => false,
		'turnServer' => '',
		'turnServerLogin' => '',
		'turnServerPassword' => '',
		'jitsiServer' => '',
		'sfuServerEnabled' => false,
		'bitrixCallsEnabled' => false,
		'callBetaIosEnabled' => false,
	];
	if (Main\Loader::includeModule('call'))
	{
		$callOptions = \Bitrix\Call\Settings::getMobileOptions();
	}

	$events = \Bitrix\Main\EventManager::getInstance()->findEventHandlers("mobile", "onMobileTabListBuilt");
	if (count($events) > 0)
	{
		$modifiedMenuTabs = ExecuteModuleEventEx($events[0], [$menuTabs]);
		$menuTabs = $modifiedMenuTabs;
	}

	$isImModuleInstalled = Main\Loader::includeModule('im');
	$userName = \CUser::FormatName(CSite::GetNameFormat(false), [
		"NAME" => $USER->GetFirstName(),
		"LAST_NAME" => $USER->GetLastName(),
		"SECOND_NAME" => $USER->GetSecondName(),
		"LOGIN" => $USER->GetLogin(),
	]);

	$canCopyText = true;
	$canTakeScreenshot = true;
	if (ServiceLocator::getInstance()->has('intranet.option.mobile_app'))
	{
		/**
		 * @var \Bitrix\Intranet\Service\MobileAppSettings $mobileSettings
		 */

		$mobileSettings = ServiceLocator::getInstance()->get('intranet.option.mobile_app');
		if ($mobileSettings->isReady())
		{
			$canCopyText = $mobileSettings->canCopyText();
			$canTakeScreenshot = $mobileSettings->canTakeScreenshot();
		}
	}

	$avaMenuManager = new AvaMenu\Manager($context);
	$profile = new AvaMenu\Profile\Profile();

	$data = [
		"status" => "success",
		"id" => $USER->GetID(),
		"login" => $USER->GetLogin(),
		"name" => $userName,
		"sessid_md5" => bitrix_sessid(),
		"cloud" => \Bitrix\Main\ModuleManager::isModuleInstalled("bitrix24") && COption::GetOptionString('bitrix24', 'network', 'N') == 'Y',
		"backend_version" => \Bitrix\Main\ModuleManager::getVersion('mobile'),
		"target" => md5($USER->GetID() . CMain::GetServerUniqID()),
		"photoUrl" => $avatarSource,
		"newStyleSupported" => true,
		"tabs" => $menuTabs,
		"user" => [
			"type" => $profile->getUserType(),
			"avatar" => $profile->getAvatar(),
		],
		'avamenu' => [
			'userInfo' => $profile->getData(),
			'totalCounter' => $avaMenuManager->getTotalCounter(),
			'items' => $avaMenuManager->getMenuData(),
		],
		"services" => [
			[
				"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("call:calls"),
				"name" => \Bitrix\MobileApp\Mobile::getApiVersion() >= 36 ? "JNUIComponent" : "JSComponent",
				"componentCode" => "calls",
				"params" => array_merge(
					[
						"userId" => $USER->getId(),
						"isAdmin" => $USER->isAdmin(),
						"siteDir" => $siteDir,
					],
					$voximplantOptions,
					$callOptions
				),
			],
			[
				"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("communication"),
				"params" => [
					"USER_ID" => $USER->getId(),
					"SITE_ID" => $siteId,
					"LANGUAGE_ID" => LANGUAGE_ID,
					"PULL_CONFIG" => \Bitrix\Pull\Config::get(['JSON' => true]),
				],
				"name" => "JSComponent",
				"componentCode" => "communication",
			],
			[
				"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("background"),
				"params" => [
					"USER_ID" => $USER->getId(),
					"SITE_ID" => $siteId,
					"LANGUAGE_ID" => LANGUAGE_ID,
				],
				"name" => "JSComponent",
				"componentCode" => "background",
			],
		],
		"canTakeScreenshot" => $canTakeScreenshot,
		"canCopyText" => $canCopyText,
		"appmap" => [
			"main" => ["url" => $siteDir . "mobile/index.php?version=" . $moduleVersion, "bx24ModernStyle" => true],
			"menu" => ["url" => $siteDir . "mobile/left.php?version=" . $moduleVersion],
			"notification" => ["url" => $siteDir . "mobile/im/notify.php"],
		],
	];

	if (\Bitrix\Main\Loader::includeModule('bitrix24'))
	{
		$data["restricted"] = \Bitrix\Bitrix24\Limits\User::isUserRestricted($USER->getId());
		$data["blocked"] = \Bitrix\Bitrix24\LicenseScanner\Manager::getInstance()->shouldLockPortal();
	}

	$needAppPass = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_APP_PASS");
	$appUUID = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_APP_UUID");
	$deviceName = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_DEVICE_NAME");
	$userId = $USER->GetID();
	$hitHash = trim($_REQUEST["bx_hit_hash"] ?? '');
	$forceGenerate = \Bitrix\Mobile\Auth::removeOneTimeAuthHash($hitHash);
	if (($needAppPass == 'mobile' && $USER->GetParam("APPLICATION_ID") === null) || $forceGenerate)
	{
		if ($forceGenerate)
		{
			setSessionExpired(false);
		}
		if ($appUUID <> '')
		{
			$result = ApplicationPasswordTable::getList([
				'select' => ['ID'],
				'filter' => [
					'USER_ID' => $USER->GetID(),
					'=CODE' => strtoupper($appUUID),
				],
			]);
			if ($row = $result->fetch())
			{
				ApplicationPasswordTable::delete($row['ID']);
			}
		}

		$password = ApplicationPasswordTable::generatePassword();
		$res = ApplicationPasswordTable::add([
			'USER_ID' => $USER->GetID(),
			'APPLICATION_ID' => 'mobile',
			'PASSWORD' => $password,
			'CODE' => $appUUID,
			'DATE_CREATE' => new Main\Type\DateTime(),
			'COMMENT' => GetMessage("MD_GENERATE_BY_MOBILE") . ($deviceName <> '' ? " (" . $deviceName . ")" : ""),
			'SYSCOMMENT' => GetMessage("MD_MOBILE_APPLICATION"),
		]);

		if ($res->isSuccess())
		{
			$data["appPassword"] = $password;
		}

	}
}

return $data;
