<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 *
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @var array $params
 */
global $APPLICATION, $USER;

use Bitrix\Intranet\Enum\UserRole;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Authentication\ApplicationPasswordTable;
use Bitrix\Security\Mfa\Otp;
use Bitrix\Socialservices\Network;
use Bitrix\SignMobile\Service\Container;
use Bitrix\SignMobile\Service\EventService;
use Bitrix\Voximplant\Security\Helper;
use Bitrix\Call\Settings;
use Bitrix\Intranet\Service\MobileAppSettings;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Intranet\UI\LeftMenu\Preset\Manager;
use Bitrix\Pull\Config;
use Bitrix\Mobile;
use Bitrix\Mobile\Auth;
use Bitrix\Mobile\AvaMenu;
use Bitrix\Mobile\Config\Feature;
use Bitrix\Mobile\Feature\MenuFeature;

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

		if (Loader::includeModule('socialservices'))
		{
			$data['host'] = (new Uri(CSocServBitrix24Net::NETWORK_URL))->getHost();
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
		if (Loader::includeModule('bitrix24') && ($captchaInfo = CBitrix24::getStoredCaptcha()))
		{
			$data["captchaCode"] = $captchaInfo["captchaCode"];
			$data["captchaURL"] = $captchaInfo["captchaURL"];
		}
		elseif ($APPLICATION->NeedCAPTHAForLogin($login))
		{
			$data["captchaCode"] = $APPLICATION->CaptchaGetCode();
		}

		if (Loader::includeModule("security") && Otp::isOtpRequired())
		{
			$data["needOtp"] = true;
		}
	}

	if (Loader::includeModule('socialservices'))
	{
		$lastUserStatus = Network::getLastUserStatus();
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
	$isSignMobileModuleInstalled = Loader::includeModule("signmobile");
	$isSignModuleInstalled = Loader::includeModule("sign");

	if ($isSignMobileModuleInstalled && $isSignModuleInstalled)
	{
		$service = Container::instance()->getEventService();

		if (method_exists(EventService::class, 'sendPriorityDocumentNotificationToSign'))
		{
			$currentUserId = (int)CurrentUser::get()->getId();

			Main\Application::getInstance()->addBackgroundJob(function () use ($service, $currentUserId)
			{
				$service->sendPriorityDocumentNotificationToSign($currentUserId);
			});
		}
		else if(method_exists(EventService::class, 'checkDocumentsSentForSigning'))
		{
			$service->checkDocumentsSentForSigning();
		}
	}

	$event = new Bitrix\Main\Event("mobile", "onRequestSyncMail", [
		'urgent' => false,
	]);
	$event->send();

	$isExtranetModuleInstalled = Loader::includeModule("extranet");
	$extranetSiteId = null;
	$intent = $_REQUEST['intent'] ?? null;

	if ($isExtranetModuleInstalled)
	{
		$extranetSiteId = CExtranet::getExtranetSiteId();
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
			BX_RESIZE_IMAGE_EXACT
		);

		if ($avatar && $avatar["src"] <> '')
		{
			$avatarSource = Uri::urnEncode($avatar["src"]);
		}
	}

	$bExtranetUser = ($isExtranetModuleInstalled && intval($curUser["UF_DEPARTMENT"][0]) <= 0);
	Loader::includeModule("pull");

	$siteId = (
	$bExtranetUser
		? $extranetSiteId
		: SITE_ID
	);

	$siteDir = SITE_DIR;
	if ($bExtranetUser)
	{
		$res = CSite::getById($extranetSiteId);
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

	$context = new Mobile\Context([
		"extranet" => $bExtranetUser,
		"siteId" => $siteId,
		"siteDir" => $siteDir,
		"version" => $moduleVersion,
	]);

	$manager = new Mobile\Tab\Manager($context);

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

		if (preg_match('/^preset_custom_section_id(\d+)$/', $intent, $match))
		{
			$customSectionId = $match[1];

			$manager->setCustomConfig([
				'crm_custom_section-' . $customSectionId => 1,
				'chat' => 2,
				'task' => 3,
				'stream' => 4,
				'menu' => 5,
			]);
		}
		elseif (str_starts_with($intent, 'preset_'))
		{
			$components = explode('_', $intent);
			if (count($components) >= 2)
			{
				$preset = $components[1];
				$manager->setPresetName($preset);
			}
		}
	}
	elseif (Loader::includeModule('intranet') && Loader::includeModule('crm'))
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

	// Activate Marta AI trigger for mobile app (every time)
	if (Loader::includeModule('aiassistant') && ServiceLocator::getInstance()->has(\Bitrix\AiAssistant\Trigger\Service\RunnerService::class))
	{
		$runnerService = ServiceLocator::getInstance()->get(\Bitrix\AiAssistant\Trigger\Service\RunnerService::class);
		$runnerService->registerBackgroundTriggerActivation('/mobile/');
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
	if (Loader::includeModule('voximplant'))
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
			'canPerformCalls' => Helper::canCurrentUserPerformCalls(),
			'lines' => CVoxImplantConfig::GetLines(true, true),
			'defaultLineId' => CVoxImplantUser::getUserOutgoingLine($USER->getId()),
			'callLogService' => Main\Config\Option::get("im", "call_log_service"),
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
	if (Loader::includeModule('call'))
	{
		$callOptions = Settings::getMobileOptions();
	}

	$events = EventManager::getInstance()->findEventHandlers("mobile", "onMobileTabListBuilt");
	if (count($events) > 0)
	{
		$modifiedMenuTabs = ExecuteModuleEventEx($events[0], [$menuTabs]);
		$menuTabs = $modifiedMenuTabs;
	}

	$isImModuleInstalled = Loader::includeModule('im');
	$userName = CUser::FormatName(CSite::GetNameFormat(false), [
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
		 * @var MobileAppSettings $mobileSettings
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

	$userRole = null;
	$isCollabToolEnabled = true;

	if (Loader::includeModule('intranet'))
	{
		$userRole = (new \Bitrix\Intranet\User((int)$USER->GetID()))->getUserRole();
		if ($userRole === UserRole::COLLABER)
		{
			$isCollabToolEnabled = ToolsManager::getInstance()->checkAvailabilityByToolId('collab');
		}
	}

	$data = [
		"status" => "success",
		"id" => $USER->GetID(),
		"login" => $USER->GetLogin(),
		"name" => $userName,
		"sessid_md5" => bitrix_sessid(),
		"cloud" => ModuleManager::isModuleInstalled("bitrix24") && COption::GetOptionString('bitrix24', 'network', 'N') == 'Y',
		"backend_version" => ModuleManager::getVersion('mobile'),
		"target" => md5($USER->GetID() . CMain::GetServerUniqID()),
		"photoUrl" => $avatarSource,
		"newStyleSupported" => true,
		"tabs" => $menuTabs,
		"user" => [
			"type" => $userRole?->value ?? 'employee',
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
				"name" => "JNUIComponent",
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
					"PULL_CONFIG" => Config::get(['JSON' => true]),
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
		"isCollabToolEnabled" => $isCollabToolEnabled,
		'featureFlags' => [
			'disableAvaMenu' => Feature::isEnabled(MenuFeature::class),
		],
	];

	if (Loader::includeModule('bitrix24'))
	{
		$data["restricted"] = \Bitrix\Bitrix24\Limits\User::isUserRestricted($USER->getId());
		$data["blocked"] = \Bitrix\Bitrix24\LicenseScanner\Manager::getInstance()->shouldLockPortal();
	}

	$needAppPass = Context::getCurrent()->getServer()->get("HTTP_BX_APP_PASS");
	$appUUID = Context::getCurrent()->getServer()->get("HTTP_BX_APP_UUID");
	$deviceName = Context::getCurrent()->getServer()->get("HTTP_BX_DEVICE_NAME");
	$userId = $USER->GetID();
	$hitHash = trim($_REQUEST["bx_hit_hash"] ?? '');
	$forceGenerate = Auth::removeOneTimeAuthHash($hitHash);
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

	$isHumanResourcesModuleInstalled = Loader::includeModule('humanresources');
	if ($isHumanResourcesModuleInstalled)
	{
		$isHeadOfDepartment = \Bitrix\HumanResources\Public\Service\Container::getUserDepartmentService()->isHeadOfDepartment($USER->GetID());
		$data["position"] = $isHeadOfDepartment ? 'head' : 'employee';
	}
}

return $data;
