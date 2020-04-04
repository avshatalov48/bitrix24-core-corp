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

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Authentication\ApplicationPasswordTable;
use Bitrix\Mobile\WebComponentManager;
use Bitrix\Main\ModuleManager;

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
	$isExtranetModuleInstalled = \Bitrix\Main\Loader::includeModule("extranet");
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
		($by = ["last_name" => "asc", "name" => "asc"]),
		($order = false),
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

		if ($avatar && strlen($avatar["src"]) > 0)
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

	$isOpenlinesOperator = (
		!$bExtranetUser
		&& \Bitrix\Main\Loader::includeModule('im')
		&& \Bitrix\Im\Integration\Imopenlines\User::isOperator()
	);

	$menuTabs = [];

	if (\Bitrix\Main\Loader::includeModule('im'))
	{
		$menuTabs[] = [
			"sort" => count($menuTabs)+1,
			"imageName" => "chat",
			"badgeCode" => "messages",
			"component" => [
				"name" => "JSComponentChatRecent",
				"title" => GetMessage("MD_COMPONENT_IM_RECENT"),
				"componentCode" => "im.recent",
				"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("im.recent"),
				"params" => [
					"COMPONENT_CODE" => "im.recent",
					"USER_ID" => $USER->GetId(),
					"OPENLINES_USER_IS_OPERATOR" => $isOpenlinesOperator,
					"SITE_ID" => $siteId,
					"LANGUAGE_ID" => LANGUAGE_ID,
					"SITE_DIR" => $siteDir,
					"LIMIT_ONLINE" => CUser::GetSecondsForLimitOnline(),
					"IM_GENERAL_CHAT_ID" => CIMChat::GetGeneralChatId(),
					"SEARCH_MIN_SIZE" => CSQLWhere::GetMinTokenSize(),

					"WIDGET_CHAT_CREATE_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im.chat.create'),
					"WIDGET_CHAT_USERS_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im.chat.user.list'),
					"WIDGET_CHAT_RECIPIENTS_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im.chat.user.selector'),
					"WIDGET_CHAT_TRANSFER_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im.chat.transfer.selector'),
					"COMPONENT_CHAT_DIALOG_VERSION" => WebComponentManager::getWebComponentVersion('im.dialog'),

					"MESSAGES" => [
						"COMPONENT_TITLE" => GetMessage("MD_COMPONENT_IM_RECENT"),
						"IMOL_CHAT_ANSWER_M" => \Bitrix\Im\Integration\Imopenlines\Localize::get(\Bitrix\Im\Integration\Imopenlines\Localize::FILE_LIB_CHAT, "IMOL_CHAT_ANSWER_M"),
						"IMOL_CHAT_ANSWER_F" => \Bitrix\Im\Integration\Imopenlines\Localize::get(\Bitrix\Im\Integration\Imopenlines\Localize::FILE_LIB_CHAT, "IMOL_CHAT_ANSWER_F")
					]
				],
				"settings" => ["useSearch" => true, "preload" => true],
			],
		];
	}

	if ($isOpenlinesOperator)
	{
		$menuTabs[] = [
			"sort" => count($menuTabs)+1,
			"imageName" => "openlines",
			"badgeCode" => "openlines",
			"component" => [
				"name" => "JSComponentChatRecent",
				"title" => GetMessage("MD_COMPONENT_IM_OPENLINES"),
				"componentCode" => "im.openlines.recent",
				"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("im.recent"), // TODO change
				"params" => [
					"COMPONENT_CODE" => "im.openlines.recent",
					"USER_ID" => $USER->GetId(),
					"OPENLINES_USER_IS_OPERATOR" => $isOpenlinesOperator,
					"SITE_ID" => $siteId,
					"SITE_DIR" => $siteDir,
					"LANGUAGE_ID" => LANGUAGE_ID,
					"LIMIT_ONLINE" => CUser::GetSecondsForLimitOnline(),
					"IM_GENERAL_CHAT_ID" => CIMChat::GetGeneralChatId(),
					"SEARCH_MIN_SIZE" => CSQLWhere::GetMinTokenSize(),

					"WIDGET_CHAT_USERS_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im.chat.user.list'),
					"WIDGET_CHAT_RECIPIENTS_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im.chat.user.selector'),
					"WIDGET_CHAT_TRANSFER_VERSION" => \Bitrix\MobileApp\Janative\Manager::getComponentVersion('im.chat.transfer.selector'),
					"COMPONENT_CHAT_DIALOG_VERSION" => WebComponentManager::getWebComponentVersion('im.dialog'),

					"MESSAGES" => [
						"COMPONENT_TITLE" => GetMessage("MD_COMPONENT_IM_OPENLINES"),
						"IMOL_CHAT_ANSWER_M" => \Bitrix\Im\Integration\Imopenlines\Localize::get(\Bitrix\Im\Integration\Imopenlines\Localize::FILE_LIB_CHAT, "IMOL_CHAT_ANSWER_M"),
						"IMOL_CHAT_ANSWER_F" => \Bitrix\Im\Integration\Imopenlines\Localize::get(\Bitrix\Im\Integration\Imopenlines\Localize::FILE_LIB_CHAT, "IMOL_CHAT_ANSWER_F")
					]
				],
				"settings" => ["useSearch" => false, "preload" => true],
			]
		];
	}

	$menuTabs[] = [
		"sort" => count($menuTabs)+1,
		"imageName" => "stream",
		"badgeCode" => "stream",
		"page" => ["useSlidingNavBar" => false, "url" => $siteDir."mobile/index.php?version=".$moduleVersion],
	];

	$menuTabs[] = [
		"sort" => count($menuTabs)+1,
		"imageName" => "bell",
		"badgeCode" => "notifications",
		"page" => ["page_id" => "notifications", "url" => $siteDir."mobile/im/notify.php"]
	];

	if (!$isOpenlinesOperator)
	{
		if((\Bitrix\MobileApp\Mobile::getPlatform() == "ios" && \Bitrix\MobileApp\Mobile::getSystemVersion() < 11) || \Bitrix\MobileApp\Mobile::getApiVersion() < 28 )
		{
			$menuTabs[] = [
				"sort" => count($menuTabs)+1,
				"imageName" => "task",
				"badgeCode" => "tasks",
				"page" => ["url" => $siteDir."mobile/tasks/snmrouter/"],
			];
		}
		else
		{
			$defaultViewType = Bitrix\Main\Config\Option::get('tasks', 'view_type', 'view_all');

			$menuTabs[] = [
				"sort" => count($menuTabs)+1,
				"imageName" => "task",
				"badgeCode" => "tasks",
				"component" => [
					"name" => "JSStackComponent",
					"title" => GetMessage("MD_COMPONENT_TASKS_LIST"),
					"componentCode" => "tasks.list",
					"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("tasks.list"),
					"rootWidget" => [
						'name'=>'tasks.list',
						'settings'=>[
							'useSearch'=>true,
							'objectName'=>'list',
							'menuSections'=> [
								['id'=> "presets"],
								['id'=> "counters", 'itemTextColor'=> "#f00"]
							],
							'menuItems'=>[
								['id'=>"view_all", 'title'=> Loc::getMessage('TASKS_ROLE_VIEW_ALL'), 'sectionCode'=>'presets', 'showAsTitle'=>true, 'badgeCount'=> 0],
								['id'=>"view_role_responsible", 'title'=> Loc::getMessage('TASKS_ROLE_RESPONSIBLE'), 'sectionCode'=>'presets', 'showAsTitle'=>true, 'badgeCount'=> 0],
								['id'=>"view_role_accomplice", 'title'=> Loc::getMessage('TASKS_ROLE_ACCOMPLICE'), 'sectionCode'=>'presets', 'showAsTitle'=>true, 'badgeCount'=> 0],
								['id'=>"view_role_auditor", 'title'=> Loc::getMessage('TASKS_ROLE_AUDITOR'), 'sectionCode'=>'presets', 'showAsTitle'=>true, 'badgeCount'=> 0],
								['id'=>"view_role_originator", 'title'=> Loc::getMessage('TASKS_ROLE_ORIGINATOR'), 'sectionCode'=>'presets', 'showAsTitle'=>true, 'badgeCount'=> 0]
							],
							'filter'=>$defaultViewType
						]
					],


					"params" => [
						"COMPONENT_CODE" => "tasks.list",
						"USER_ID" => $USER->GetId(),
						"SITE_ID" => $siteId,
						"LANGUAGE_ID" => LANGUAGE_ID,
						"SITE_DIR" => $siteDir,

						"PATH_TO_TASK_ADD"=> $siteDir."mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#",

						"MESSAGES" => [

						]
					]
				]
			];
		}
	}

	$menuTabs[] = [
		"sort" => count($menuTabs)+1,
		"imageName" => "menu_2",
		"badgeCode" => "more",
		"component" => [
			"settings" => ["useSearch" => true],
			"name" => "JSMenuComponent",
			"title" => GetMessage("MD_COMPONENT_MORE"),
			"componentCode" => "settings",
			"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("more"),
			"params" => [
				"userId" => $USER->getId(),
				"SITE_ID" => $siteId,
			]
		]
	];

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
		"name" => \CUser::FormatName(CSite::GetNameFormat(false), [
			"NAME" => $USER->GetFirstName(),
			"LAST_NAME" => $USER->GetLastName(),
			"SECOND_NAME" => $USER->GetSecondName(),
			"LOGIN" => $USER->GetLogin()
		]),
		"sessid_md5" => bitrix_sessid(),
		"target" => md5($USER->GetID() . CMain::GetServerUniqID()),
		"photoUrl" => $avatarSource,
		"wkWebViewSupported" => true,
		"tabInterfaceSupported" => true,
		"tabs" => $menuTabs,
		"services" => [
			[
				"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("calls"),
				"name" => "JSComponent",
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
					"defaultLineId" => $voximplantDefaultLineId
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
			"right" => ["url" => $siteDir."mobile/im/right.php?version=".$moduleVersion],
			"notification" => ["url" => $siteDir."mobile/im/notify.php"]
		]
	];

	$needAppPass = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_APP_PASS");
	$appUUID = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_APP_UUID");
	$deviceName = \Bitrix\Main\Context::getCurrent()->getServer()->get("HTTP_BX_DEVICE_NAME");

	if ($needAppPass == 'mobile' && $USER->GetParam("APPLICATION_ID") === null)
	{
		if (strlen($appUUID) > 0)
		{
			$result = ApplicationPasswordTable::getList(Array(
				'select' => Array('ID'),
				'filter' => Array(
					'USER_ID' => $USER->GetID(),
					'CODE' => $appUUID
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
			'COMMENT' => GetMessage("MD_GENERATE_BY_MOBILE") . (strlen($deviceName) > 0 ? " (" . $deviceName . ")" : ""),
			'SYSCOMMENT' => GetMessage("MD_MOBILE_APPLICATION")
		));

		if ($res->isSuccess())
		{
			$data["appPassword"] = $password;
		}
	}
}

return Main\Text\Encoding::convertEncoding($data, LANG_CHARSET, 'UTF-8');
