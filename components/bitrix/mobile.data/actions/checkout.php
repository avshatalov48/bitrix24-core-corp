<? if (!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 *
 * @var $APPLICATION CAllMain
 * @var $USER CAllUser
 * @var $params array
 */
global $APPLICATION, $USER;

use Bitrix\Intranet\UI\LeftMenu\Preset\Manager;
use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\Authentication\ApplicationPasswordTable;
use Bitrix\Main\ModuleManager;
use Bitrix\Im;

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
if(array_key_exists("logincheck", $_REQUEST) && $_REQUEST["login"])
{
	$res = CUser::getByLogin($_REQUEST["login"]);
	$data["exists"] = $res->fetch() ? true : false;
	if (!$data["exists"])
	{
		// AD\LDAP
		$ldapComponents = explode("\\", $_REQUEST["login"]);
		if (count($ldapComponents) == 2)
		{
			$res = CUser::getByLogin($ldapComponents[1]);
			$data["exists"] = $res->fetch() ? true : false;
		}
	}

	return Main\Text\Encoding::convertEncoding($data, LANG_CHARSET, 'UTF-8');
}

if(array_key_exists("servercheck", $_REQUEST))
{
	$data["cloud"] = ModuleManager::isModuleInstalled("bitrix24") && COption::GetOptionString('bitrix24', 'network', 'N') == 'Y';
	return Main\Text\Encoding::convertEncoding($data, LANG_CHARSET, 'UTF-8');
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
		"FIELDS" => ["PERSONAL_PHOTO"]
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

	if (intval($curUser["PERSONAL_PHOTO"]) > 0)
	{
		$avatar = CFile::ResizeImageGet(
			$curUser["PERSONAL_PHOTO"],
			["width" => 64, "height" => 64],
			BX_RESIZE_IMAGE_EXACT,
			false
		);

		if ($avatar && $avatar["src"] <> '')
		{
			$avatarSource = $avatar["src"];
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
		if(
			($extranetSiteFields = $res->fetch())
			&& ($extranetSiteFields["ACTIVE"] != "N")
		)
		{
			$siteDir = $extranetSiteFields["DIR"];
		}
	}

	$moduleVersion = (defined("MOBILE_MODULE_VERSION") ? MOBILE_MODULE_VERSION : "default");
	if(array_key_exists("IS_WKWEBVIEW", $_COOKIE) && $_COOKIE["IS_WKWEBVIEW"] == "Y")
	{
		$moduleVersion .= "_wkwebview";
	}

	$context = new \Bitrix\Mobile\Context([
		"extranet"=>$bExtranetUser,
		"siteId"=>$siteId,
		"siteDir"=>$siteDir,
		"version"=>$moduleVersion,
	]);

	$manager = new \Bitrix\Mobile\Tab\Manager($context);

	if ($intent && strpos($intent, 'preset_') === 0)
	{
		$components = explode('_', $intent);
		if (count($components) >= 2)
		{
			$preset = $components[1];
			$manager->setPresetName($preset);
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

//	array_shift($menuTabs);
	$voximplantServer = '';
	$voximplantLogin = '';
	$voximplantLines = [];
	$voximplantDefaultLineId = '';
	if($voximplantInstalled = Main\Loader::includeModule('voximplant'))
	{
		$viUser = new CVoxImplantUser();
		$voximplantAuthorization = $viUser->getAuthorizationInfo($USER->getId());
		if($voximplantAuthorization->isSuccess())
		{
			$voximplantAuthorizationData = $voximplantAuthorization->getData();
			$voximplantServer = $voximplantAuthorizationData['server'];
			$voximplantLogin = $voximplantAuthorizationData['login'];
		}

		$voximplantLines = CVoxImplantConfig::GetLines(true, true);
		$voximplantDefaultLineId = CVoxImplantUser::getUserOutgoingLine($USER->getId());
	}

	$events = \Bitrix\Main\EventManager::getInstance()->findEventHandlers("mobile", "onMobileTabListBuilt");
	if (count($events) > 0)
	{
		$modifiedMenuTabs = ExecuteModuleEventEx($events[0], [$menuTabs]);
		$menuTabs = $modifiedMenuTabs;
	}
	$data = [
		"status" => "success",
		"id" => $USER->GetID(),
		"login" => $USER->GetLogin(),
		"name" => \CUser::FormatName(CSite::GetNameFormat(false), [
			"NAME" => $USER->GetFirstName(),
			"LAST_NAME" => $USER->GetLastName(),
			"SECOND_NAME" => $USER->GetSecondName(),
			"LOGIN" => $USER->GetLogin()
		]),
		"sessid_md5" => bitrix_sessid(),
        "backend_version" => \Bitrix\Main\ModuleManager::getVersion('mobile'),
		"target" => md5($USER->GetID() . CMain::GetServerUniqID()),
		"photoUrl" => $avatarSource,
		"wkWebViewSupported" => true,
		"tabInterfaceSupported" => true,
		"tabs" => $menuTabs,
		"services" => [
			[
				"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("im:calls"),
				"name" => \Bitrix\MobileApp\Mobile::getApiVersion() >= 36 ? "JNUIComponent" : "JSComponent",
				"componentCode" => "calls",
				"params" => [
					"userId" => $USER->getId(),
					"isAdmin" => $USER->isAdmin(),
					"siteDir" => $siteDir,
					"voximplantInstalled" => $voximplantInstalled,
					"voximplantServer" => $voximplantServer,
					"voximplantLogin" => $voximplantLogin,
					"canPerformCalls" => $voximplantInstalled && \Bitrix\Voximplant\Security\Helper::canCurrentUserPerformCalls(),
					"lines" => $voximplantLines,
					"defaultLineId" => $voximplantDefaultLineId,
					"useCustomTurnServer" => Main\Config\Option::get("im", "turn_server_self") === "Y",
					"turnServer" => Main\Config\Option::get("im", "turn_server", ""),
					"turnServerLogin" => Main\Config\Option::get("im", "turn_server_login", ""),
					"turnServerPassword" => Main\Config\Option::get("im", "turn_server_password", ""),
					"callLogService" => Main\Config\Option::get("im", "call_log_service", ""),
					"jitsiServer" => Main\Config\Option::get("im", "jitsi_server", ""),
					"sfuServerEnabled" => Main\Loader::includeModule('im') && Im\Call\Call::isCallServerEnabled(),
				]
			],
			[
				"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("communication"),
				"params" => [
					"USER_ID" => $USER->getId(),
					"SITE_ID" => $siteId,
					"LANGUAGE_ID" => LANGUAGE_ID,
					"PULL_CONFIG" => \Bitrix\Pull\Config::get(['JSON' => true])
				],
				"name" => "JSComponent",
				"componentCode" => "communication"
			],
			[
				"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("background"),
				"params" => [
					"USER_ID" => $USER->getId(),
					"SITE_ID" => $siteId,
					"LANGUAGE_ID" => LANGUAGE_ID,
				],
				"name" => "JSComponent",
				"componentCode" => "background"
			]
		],
		"useModernStyle" => true,
		"appmap" => [
			"main" => ["url" => $siteDir."mobile/index.php?version=".$moduleVersion, "bx24ModernStyle" => true],
			"menu" => ["url" => $siteDir."mobile/left.php?version=".$moduleVersion],
			"notification" => ["url" => $siteDir."mobile/im/notify.php"]
		]
	];


	if(\Bitrix\Main\Loader::includeModule('bitrix24'))
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
		if($forceGenerate) {
			setSessionExpired(false);
		}
		if ($appUUID <> '')
		{
			$result = ApplicationPasswordTable::getList(Array(
				'select' => Array('ID'),
				'filter' => Array(
					'USER_ID' => $USER->GetID(),
					'=CODE' => strtoupper($appUUID),
				)
			));
			if ($row = $result->fetch())
			{
				ApplicationPasswordTable::delete($row['ID']);
			}
		}

		$password = ApplicationPasswordTable::generatePassword();
		$res = ApplicationPasswordTable::add(array(
			'USER_ID' => $USER->GetID(),
			'APPLICATION_ID' => 'mobile',
			'PASSWORD' => $password,
			'CODE' => $appUUID,
			'DATE_CREATE' => new Main\Type\DateTime(),
			'COMMENT' => GetMessage("MD_GENERATE_BY_MOBILE") . ($deviceName <> '' ? " (" . $deviceName . ")" : ""),
			'SYSCOMMENT' => GetMessage("MD_MOBILE_APPLICATION")
		));

		if ($res->isSuccess())
		{
			$data["appPassword"] = $password;
		}

	}
}

return Main\Text\Encoding::convertEncoding($data, LANG_CHARSET, 'UTF-8');
