<?php

use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Service\Container;
use Bitrix\DiskMobile\AirDiskFeature;
use Bitrix\Intranet\AI;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Mobile\Config\Feature;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\Main\EventManager;
use Bitrix\MobileApp\Mobile;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $USER;

/**
 * @var  $USER CUser
 * @var  $this \Bitrix\MobileApp\Janative\Entity\Component
 * @var  $isExtranetUser bool
 * @var  $isCollaber bool
 */

$allowedFeatures = [];

$hereDocGetMessage = function ($code) {
	return Loc::getMessage($code);
};

if (CModule::IncludeModule("socialnetwork"))
{
	$socNetFeatures = new \Bitrix\Mobile\Component\SocNetFeatures($USER->getId());
	$allowedFeatures = [];
	foreach (['tasks', 'files'] as $feature)
	{
		$allowedFeatures[$feature] = $socNetFeatures->isEnabledForUser($feature);
	}
}

$diskEnabled = Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk');
$airDiskEnabled = Feature::isEnabled(AirDiskFeature::class);
$userId = $USER->getId();
$siteDir = SITE_DIR;
$siteId = SITE_ID;
$extranetSiteId = '';

if (ModuleManager::isModuleInstalled('extranet'))
{
	$extranetSiteId = Option::get('extranet', 'extranet_site');
}

if ($isExtranetUser && $extranetSiteId)
{
	$res = \CSite::getById($extranetSiteId);
	if (
		($extranetSiteFields = $res->fetch())
		&& ($extranetSiteFields["ACTIVE"] !== "N")
	)
	{
		$siteId = $extranetSiteId;
		$siteDir = $extranetSiteFields["DIR"];
	}
}

$imageDir = $this->getPath() . "/images/";

$diskComponentVersion = Manager::getComponentVersion("user.disk");
$workgroupsComponentVersion = Manager::getComponentVersion("workgroups");

$menuStructure = [];
$favoriteItems = [];

if (Mobile::getApiVersion() < 41)
{
	$favoriteItems[] = [
		"hidden" => ($isExtranetUser || !ModuleManager::isModuleInstalled("bizproc")),
		"title" => Loc::getMessage("MB_BP_MAIN_MENU_ITEM"),
		"imageUrl" => $imageDir . "favorite/icon-bp.png",
		"color" => "#33c3bd",
		'imageName' => 'business_process',
		"attrs" => [
			"url" => $siteDir . "mobile/bp/?USER_STATUS=0",
			"id" => "bp_list",
			"counter" => "bp_tasks",
		],
	];
}

if (Loader::includeModule('signmobile')
	&& class_exists(\Bitrix\SignMobile\Config\Feature::class)
	&& method_exists(\Bitrix\SignMobile\Config\Feature::class, 'isMyDocumentsGridAvailable')
	&& \Bitrix\SignMobile\Config\Feature::instance()->isMyDocumentsGridAvailable()
)
{
	$counterId = 'sign_b2e_current';
	if (enum_exists(\Bitrix\Sign\Type\CounterType::class))
	{
		$counterId = \Bitrix\Sign\Type\CounterType::SIGN_B2E_MY_DOCUMENTS->value;
	}

	$favoriteItems[] = [
		"title" => Loc::getMessage("MENU_MY_DOCUMENTS"),
		"imageUrl" => $imageDir . "sign/my-documents.png",
		'imageName' => 'sign',
		"color" => '#1F86FF',
		"hidden" => false,
		"attrs" => [
			"id" => "signmobile",
			"counter" => $counterId,
			"onclick" => <<<JS
				ComponentHelper.openLayout({
					name: 'sign:sign.b2e.grid',
					object: 'layout',
					widgetParams: {
						titleParams: {text: "{$hereDocGetMessage("MENU_MY_DOCUMENTS")}", type: "section"},
					}
				});
			JS,
		],
	];
}

if (Mobile::getApiVersion() < 41)
{
	$favoriteItems[] = [
		"title" => Loc::getMessage("MB_CURRENT_USER_FILES_MAIN_MENU_ITEM_NEW"),
		"imageUrl" => $imageDir . "favorite/icon-mydisk.png",
		"color" => "#20A1E7",
		"attrs" => [
			"onclick" => <<<JS

				ComponentHelper.openList({
					name:"user.disk",
					object:"list",
					version:"{$diskComponentVersion}",
					componentParams:{userId: env.userId},
					widgetParams:{
						title:"{$hereDocGetMessage("MB_CURRENT_USER_FILES_MAIN_MENU_ITEM_NEW")}",
						useSearch: true,
						doNotHideSearchResult: true
					}
				});

JS
			,
			"id" => "doc_user",
		],
		"hidden" => !$diskEnabled || !$allowedFeatures["files"],
		"id" => "doc_user",
	];
}


$favoriteItems[] = [
	"imageUrl" => $imageDir . "favorite/icon-disk.png",
	"color" => "#3CD162",
	"title" => Loc::getMessage("MB_SHARED_FILES_MAIN_MENU_ITEM_NEW"),
	'imageName' => 'folder_24',
	"attrs" => [
		"onclick" => <<<JS

		ComponentHelper.openList({
			name:"user.disk",
			object:"list",
			version:"{$diskComponentVersion}",
			componentParams:{userId: env.userId, ownerId: "shared_files_"+env.siteId, entityType:"common"},
			widgetParams:{titleParams: { text:"{$hereDocGetMessage("MB_SHARED_FILES_MAIN_MENU_ITEM_NEW")}", type: "section"}, useSearch: true}
		});

JS
		,
		"id" => "doc_shared",
	],
	"hidden" => $airDiskEnabled || !$diskEnabled || $isExtranetUser || !$allowedFeatures["files"],
];

$favoriteItems[] = [
	"title" => Loc::getMessage("MB_SHARED_FILES_MAIN_MENU_ITEM_NEW"),
	"imageUrl" => $imageDir . "favorite/icon-disk.png",
	"color" => "#b9bdc3",
	'imageName' => 'folder_24',
	"attrs" => [
		"onclick" => <<<JS

		PageManager.openList(
		{
			url:"/mobile/?mobile_action=disk_folder_list&type=common&path=/&entityId=shared_files_"+env.siteId,
			table_settings:
			{
				useTagsInSearch:"NO",
				type:"files"
			}
		});
JS
		,
		"id" => "doc_shared",
	],
	"hidden" => $airDiskEnabled || $diskEnabled || $isExtranetUser || !$allowedFeatures["files"],
];


$favorite = [
	"title" => Loc::getMessage("MB_SEC_FAVORITE_MSGVER_2"),
	"hidden" => false,
	"_code" => "favorite",
	"sort" => 100,
	"items" => $favoriteItems,
];

$menuStructure[] = $favorite;

/**
 * Marketplace apps
 */

if (CModule::IncludeModule("rest"))
{
	$arMenuApps = [];
	$arUserGroupCode = $USER->GetAccessCodes();
	$numLocalApps = 0;

	$dbApps = \Bitrix\Rest\AppTable::getList([
		'order' => ["ID" => "ASC"],
		'filter' => [
			"=ACTIVE" => \Bitrix\Rest\AppTable::ACTIVE,
			"=MOBILE" => \Bitrix\Rest\AppTable::ACTIVE,
		],
		'select' => [
			'ID',
			'STATUS',
			'ACCESS',
			'MENU_NAME' => 'LANG.MENU_NAME',
			'MENU_NAME_DEFAULT' => 'LANG_DEFAULT.MENU_NAME',
			'MENU_NAME_LICENSE' => 'LANG_LICENSE.MENU_NAME',
		],
	]);

	while ($apps = $dbApps->fetch())
	{
		if ($apps["STATUS"] == \Bitrix\Rest\AppTable::STATUS_LOCAL)
		{
			$numLocalApps++;
		}

		$lang = in_array(LANGUAGE_ID, ["ru", "en", "de"]) ? LANGUAGE_ID : LangSubst(LANGUAGE_ID);
		if ($apps["MENU_NAME"] <> '' || $apps['MENU_NAME_DEFAULT'] <> '' || $apps['MENU_NAME_LICENSE'] <> '')
		{
			$appRightAvailable = false;
			if (\CRestUtil::isAdmin())
			{
				$appRightAvailable = true;
			}
			elseif (!empty($apps["ACCESS"]))
			{
				$rights = explode(",", $apps["ACCESS"]);
				foreach ($rights as $rightID)
				{
					if (in_array($rightID, $arUserGroupCode))
					{
						$appRightAvailable = true;
						break;
					}
				}
			}
			else
			{
				$appRightAvailable = true;
			}

			if ($appRightAvailable)
			{
				$appName = $apps["MENU_NAME"];

				if ($appName == '')
				{
					$appName = $apps['MENU_NAME_DEFAULT'];
				}
				if ($appName == '')
				{
					$appName = $apps['MENU_NAME_LICENSE'];
				}

				$arMenuApps[] = [
					"title" => $appName,
					"attrs" => [
						"cache" => false,
						"id" => $apps["ID"],
						"url" => $siteDir . "mobile/marketplace/?id=" . $apps["ID"],
					],
				];
			}
		}
	}

	if (!empty($arMenuApps))
	{
		$menuStructure[] = [
			"title" => Loc::getMessage("MB_MARKETPLACE_GROUP_TITLE_2"),
			"sort" => 110,
			"hidden" => CMobile::getInstance()->getApiVersion() <= 15,
			"items" => $arMenuApps,
		];
	}
}

/**
 * CRM menu
 */
if (
	!$isExtranetUser
	&& IsModuleInstalled('crm')
	&& CModule::IncludeModule('crm')
	&& CCrmPerms::IsAccessEnabled()
)
{
	$crmIsInitialized = \Bitrix\Crm\Settings\Crm::wasInitiated();

	$crmMenuItems = [
		'title' => 'CRM',
		'sort' => 120,
		'hidden' => !$crmIsInitialized,
		'items' => [
			[
				'title' => Loc::getMessage('MB_CRM_ACTIVITY'),
				'imageUrl' => $imageDir . 'crm/icon-crm-mydeals.png',
				'imageName' => 'my_deals',
				'color' => '#8590a2',
				'hidden' => false,
				'attrs' => [
					'url' => '/mobile/crm/activity/list.php',
					'id' => 'crm_activity_list',
				],
			],
		],
	];

	$menuStructure[] = $crmMenuItems;

	$customSectionsMenuItems = [
		'title' => Loc::getMessage('MB_CRM_DYNAMIC_CUSTOM_SECTION'),
		'sort' => 123,
		'hidden' => false,
		'items' => [],
	];

	if (
		IntranetManager::isCustomSectionsAvailable()
		&& \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager()->checkExternalDynamicAvailability()
	)
	{
		$customSections = IntranetManager::getCustomSections();
		foreach ($customSections as $customSection)
		{
			$pages = $customSection->getPages();
			if (empty($pages))
			{
				continue;
			}

			$hasPermissions = false;
			foreach ($pages as $page)
			{
				$entityTypeId = IntranetManager::getEntityTypeIdByPageSettings($page->getSettings());
				if (Container::getInstance()->getUserPermissions()->canReadType($entityTypeId))
				{
					$hasPermissions = true;
					break;
				}
			}

			if (!$hasPermissions)
			{
				continue;
			}

			$customSectionId = $customSection->getId();
			$customSectionTitle = $customSection->getTitle();
			$customSectionComponentText = CUtil::JSescape($customSectionTitle);

			$customSectionsMenuItems['items'][] = [
				'title' => $customSectionTitle,
				'imageUrl' => $imageDir . 'crm/icon-crm-dynamic.png',
				'color' => '#3BA7EF',
				'imageName' => 'activity',
				'hidden' => false,
				'attrs' => [
					'id' => 'dynamic_custom_section_' . $customSectionId,
					'onclick' => <<<JS
						ComponentHelper.openLayout({
							widgetParams: {
								titleParams: {
									text: '$customSectionComponentText',
								},
							},
							name: 'crm:crm.tabs',
							canOpenInDefault: true,
							componentParams: {
								customSectionId: $customSectionId,
							},
						});
JS,
				],
			];
		}
	}

	if (!empty($customSectionsMenuItems['items']))
	{
		$menuStructure[] = $customSectionsMenuItems;
	}
}

/**
 * Catalog menu
 */
if (
	!$isExtranetUser
	&& IsModuleInstalled('catalog')
	&& CModule::IncludeModule('catalog')
	&& \Bitrix\Catalog\Restriction\ToolAvailabilityManager::getInstance()->checkInventoryManagementAvailability()
	&& !\Bitrix\Catalog\Store\EnableWizard\Manager::isOnecMode()
)
{
	$menuStructure[] = [
		"title" => Loc::getMessage("MENU_CATALOG"),
		"sort" => 125,
		"code" => "catalog_store",
		"hidden" => false,
		"items" => [],
	];
}

if (
	!$isExtranetUser
	&& Bitrix\Main\Loader::includeModule('crm')
	&& Container::getInstance()->getIntranetToolsManager()->checkTerminalAvailability()
)
{
	$menuStructure[] = [
		"title" => Loc::getMessage("MENU_CRM_TERMINAL_V2"),
		"sort" => 127,
		"code" => "terminal",
		"hidden" => false,
		"items" => [],
	];
}

/**
 * Groups
 */

$workgroupUrlTemplate = \Bitrix\Mobile\Project\Helper::getProjectNewsPathTemplate([
	'siteDir' => $siteDir,
]);
$workgroupCalendarWebPathTemplate = \Bitrix\Mobile\Project\Helper::getProjectCalendarWebPathTemplate([
	'siteDir' => $siteDir,
	'siteId' => $siteId,
]);

$features = implode(',', \Bitrix\Mobile\Project\Helper::getMobileFeatures());
$mandatoryFeatures = implode(',', \Bitrix\Mobile\Project\Helper::getMobileMandatoryFeatures());

$groupSection = [
	"title" => Loc::getMessage('MB_SEC_GROUPS_MSGVER_1'),
	"sort" => 130,
	"hidden" => false,
	"items" => [],
];

$projectsEnabled = true;
if (Loader::includeModule('intranet'))
{
	$projectsEnabled = ToolsManager::getInstance()->checkAvailabilityByToolId('tasks');
}

if (!$isExtranetUser && $projectsEnabled)
{
	$menuName = Loc::getMessage('MENU_INTRANET');
	$groupSection["items"][] = [
		"title" => $menuName,
		'imageName' => 'intranet',
		'imageUrl' => $imageDir . 'favorite/intranet.png',
		'color' => '#0075FF',
		"attrs" => [
			"onclick" => <<<JS
				ComponentHelper.openList({
					name: 'workgroups',
					object: 'list',
					version: "{$workgroupsComponentVersion}",
					componentParams: {
						siteId: "{$siteId}",
						siteDir: "{$siteDir}",
						pathTemplate: "{$workgroupUrlTemplate}",
						calendarWebPathTemplate: "{$workgroupCalendarWebPathTemplate}",
						features: "{$features}",
						mandatoryFeatures: "{$mandatoryFeatures}",
						currentUserId: "{$userId}"
					},
					widgetParams: {
						titleParams: {text: "{$menuName}", type: "section"},
						useSearch: false,
						doNotHideSearchResult: true
					}
				});
JS
			,
		],
	];
}

if (
	$isExtranetUser
	|| $extranetSiteId
)
{
	$menuName = Loc::getMessage('MENU_EXTRANET');
	$groupSection["items"][] = [
		"title" => $menuName,
		'imageName' => 'globe_extranet',
		'color' => '#FAA72C',
		'imageUrl' => $imageDir . 'favorite/extranet.png',
		"attrs" => [
			"onclick" => <<<JS
				ComponentHelper.openList({
					name: 'workgroups',
					object: 'list',
					version: "{$workgroupsComponentVersion}",
					componentParams: {
						siteId: "{$extranetSiteId}",
						siteDir: "{$siteDir}",
						pathTemplate: "{$workgroupUrlTemplate}",
						calendarWebPathTemplate: "{$workgroupCalendarWebPathTemplate}",
						features: "{$features}",
						mandatoryFeatures: "{$mandatoryFeatures}",
						currentUserId: "{$userId}"
					},
					widgetParams: {
						titleParams: {text: "{$menuName}", type: "section"},
						useSearch: false,
						doNotHideSearchResult: true
					}
				});
JS
			,
			"id" => "workgroups_extranet",
		],
	];
}

$menuStructure[] = $groupSection;

$isAvaMenuAvailable = Mobile::getInstance()::getApiVersion() >= 54;
if (!$isAvaMenuAvailable)
{
	$timemanEnabledForUser = false;
	if (Loader::includeModule('timeman'))
	{
		$timemanEnabledForUser = CTimeMan::CanUse();
	}
	$menuStructure[] = [
		"title" => GetMessage("MENU_WORK_DAY_MSGVER_1"),
		"sort" => 2,
		"hidden" => ($isExtranetUser || !IsModuleInstalled("timeman") || !$timemanEnabledForUser),
		"items" => [
			[
				"title" => Loc::getMessage("MENU_WORK_DAY_MANAGE"),
				"imageUrl" => $imageDir . "favorite/icon-timeman.png",
				"color" => "#2FC6F6",
				"type" => "info",
				"params" => [
					"url" => $siteDir . "mobile/timeman/",
					"backdrop" => ["onlyMediumPosition" => false, "mediumPositionPercent" => 80],
				],
			],
		],
	];
}

if (
	!$isExtranetUser && !$isCollaber
)
{
	$myBitrix24Items = [];

	if (
		(
			\CModule::IncludeModule('bitrix24')
			|| $USER->isAdmin()
		)
		&& Loader::includeModule('intranet')
		&& Loader::includeModule('intranetmobile')
	)
	{
		$myBitrix24Items[] = [
			'id' => 'invite',
			'title' => Loc::getMessage('MENU_BITRIX24_INVITE'),
			'imageName' => 'add_person',
			'color' => '#FB6DBA',
			'imageUrl' => $imageDir . 'favorite/add_person.png',
			'attrs' => [
				'showHighlighted' => true,
				'highlightWithCounter' => true,
				'counter' => 'menu_invite',
				'onclick' => <<<JS
						requireLazy('intranet:invite-opener-new')
							.then(({ openIntranetInviteWidget }) => {
								if (openIntranetInviteWidget)
								{
									openIntranetInviteWidget({});
								}
							});
				JS,
			],
		];
	}

	if (
		IsModuleInstalled('intranetmobile')
		&& Bitrix\Main\Loader::includeModule('intranetmobile')
		&& Mobile::getInstance()::getApiVersion() >= 54
	)
	{
		$componentPath = Manager::getComponentPath('intranet:user.list');

		$canUseTelephony = (
			Bitrix\Main\Loader::includeModule('voximplant')
			&& \Bitrix\Voximplant\Security\Helper::canCurrentUserPerformCalls()
		) ? "Y" : "N";

		$myBitrix24Items[] = [
			'id' => 'users',
			'title' => Loc::getMessage($isExtranetUser ? 'MB_CONTACTS' : 'MB_COMPANY'),
			'imageUrl' => $imageDir . 'favorite/icon-users.png',
			'color' => '#AF22AF',
			'imageName' => 'three_persons',
			'attrs' => [
				"counter" => "total_invitation",
				'onclick' =>
					<<<JS
			let inviteParams = {};
			try
			{
				inviteParams = JSON.parse(Application.sharedStorage('menuComponentSettings').get("invite"));
			}
			catch (e)
			{
				//do nothing
			}
			PageManager.openComponent('JSStackComponent', {
				scriptPath: "{$componentPath}",
				componentCode: "intranet.user.list",
				params: {
					canInvite: (inviteParams.canInviteUsers ? inviteParams.canInviteUsers : false),
					canUseTelephony: "{$canUseTelephony}",
				},
				rootWidget: {
					name: 'layout',
					componentCode: 'users',
					settings: {
						objectName: "layout",
						titleParams: {text: "{$hereDocGetMessage($isExtranetUser ? "MB_CONTACTS" : "MB_COMPANY")}", type: "section"},
					},
				},
			});
JS,
			],
		];
	}
	else
	{
		$myBitrix24Items[] = [
			'id' => 'users',
			'title' => Loc::getMessage($isExtranetUser ? 'MB_CONTACTS' : 'MB_COMPANY'),
			'imageName' => 'three_persons',
			'imageUrl' => $imageDir . 'favorite/icon-users.png',
			'color' => '#AF9245',
			'attrs' => [
				'onclick' =>
					<<<JS
			var inviteParams = {};
			try
			{
				inviteParams = JSON.parse(Application.sharedStorage('menuComponentSettings').get("invite"));
			}
			catch (e)
			{
				//do nothing
			}

			PageManager.openComponent(
			"JSComponentList",
			{
				settings: {useSearch: true, titleParams:{ text: "{$hereDocGetMessage($isExtranetUser ? "MB_CONTACTS" : "MB_COMPANY")}", type: "section" }},
				componentCode: "users",
				scriptPath: availableComponents["users"]["publicUrl"],
				params:{
					COMPONENT_CODE: "users",
					canInvite: (inviteParams.canInviteUsers ? inviteParams.canInviteUsers : false),
					rootStructureSectionId: (inviteParams.rootStructureSectionId ? inviteParams.rootStructureSectionId : 1),
					registerUrl: (inviteParams.registerUrl ? inviteParams.registerUrl : ''),
					registerAdminConfirm: (inviteParams.registerAdminConfirm ? inviteParams.registerAdminConfirm : false),
					disableRegisterAdminConfirm: (inviteParams.disableRegisterAdminConfirm ? inviteParams.disableRegisterAdminConfirm : false),
					sharingMessage: (inviteParams.registerSharingMessage ? inviteParams.registerSharingMessage : ''),
					userId: {$userId}
				}
			})
JS,
			],
		];
	}

	$tabPresetsTitle = Loc::getMessage('MENU_BITRIX24_MENU_BOTTOM');
	$myBitrix24Items[] = [
		'id' => 'tab_presets',
		'title' => $tabPresetsTitle,
		'imageName' => 'bottom_menu',
		'color' => '#1E8EC2',
		'imageUrl' => $imageDir . 'favorite/bottom_menu.png',
		'attrs' => [
			'showHighlighted' => true,
			'highlightWithCounter' => true,
			'counter' => 'menu_tab_presets',
			'onclick' => <<<JS
				PageManager.openComponent('JSStackComponent',{
					scriptPath: availableComponents['tab.presets'].publicUrl,
					rootWidget:{
					name: 'layout',
					settings:{
						objectName: 'layout',
						titleParams: { text: "{$tabPresetsTitle}", useLargeTitleMode: true}
					}
				}
			});
			JS,
		],
	];

	$menuStructure[] = [
		'title' => Loc::getMessage('MB_SEC_B24_MSGVER_1'),
		'sort' => 1,
		'hidden' => false,
		'items' => $myBitrix24Items,
	];
}

$voximplantInstalled = false;
if ($voximplantInstalled = Loader::includeModule('voximplant'))
{
	$menuStructure[] = [
		"title" => GetMessage("MENU_TELEPHONY"),
		"min_api_version" => 22,
		"hidden" => !\Bitrix\Voximplant\Security\Helper::canCurrentUserPerformCalls(),
		"sort" => 3,
		"items" => [
			[
				'imageName' => 'phone_up',
				"title" => Loc::getMessage("MENU_TELEPHONY_CALL"),
				"color" => "#9ACB00",
				"unselectable" => true,
				"imageUrl" => $imageDir . "telephony/icon-call.png",
				"params" => [
					"onclick" => <<<JS
						BX.postComponentEvent("onNumpadRequestShow");
JS
					,

				],
			],
		],
	];
}

if (!$isAvaMenuAvailable)
{
	$settingsComponentPath = Manager::getComponentPath("settings");
	$qrComponentPath = Manager::getComponentPath("qrcodeauth");
	$settingsUserId = $USER->GetID();
	$settingsSiteId = SITE_ID;
	$isUserAdmin = ((\CModule::IncludeModule('bitrix24') ? \CBitrix24::isPortalAdmin($settingsUserId) : $USER->isAdmin()))
		? "true" : "false";

	$settingsLanguageId = LANGUAGE_ID;

	$menuStructure[] = [
		'title' => Loc::getMessage('MB_SEC_B24_MSGVER_1'),
		'min_api_version' => 25,
		'sort' => 1,
		"items" => [
			[
				"title" => Loc::getMessage("TO_LOGIN_ON_DESKTOP_MSGVER_1"),
				"useLetterImage" => true,
				"color" => "#4BA3FB",
				'type' => 'info',
				"imageUrl" => $imageDir . "settings/desktop_login.png",
				'attrs' => [
					"onclick" => <<<JS
				qrauth.open({
					title: this.title,
					showHint: false
				})
JS
					,
				],
			],
			[
				"title" => Loc::getMessage("MENU_SETTINGS"),
				"useLetterImage" => false,
				"color" => "#666475",
				"imageUrl" => $imageDir . "settings/settings.png",
				"attrs" => [
					"onclick" => <<<JS
						PageManager.openComponent("JSStackComponent",
						{
							scriptPath:"$settingsComponentPath",
							componentCode: "settings.config",
							params: {
								"USER_ID": $settingsUserId,
								"SITE_ID": "$settingsSiteId",
								"LANGUAGE_ID": "$settingsLanguageId",
								"IS_ADMIN": $isUserAdmin
							},
							rootWidget: {
								name: "settings",
								settings: {
									objectName: "settings",
									title: this.title,
								}
							}
						});
JS
					,
				],
			],
		],
	];

}

if (Loader::includeModule("intranet") && !$isExtranetUser)
{
	$assistantApp = AI\Center::getAssistantApp();
	$assistantAppId = is_array($assistantApp) && $assistantApp["ACTIVE"] === "Y" ? intval($assistantApp["ID"]) : 0;
	$assistants = AI\Center::getAssistants();

	if ($assistantAppId > 0 && count($assistants) > 0)
	{
		$items = [];
		foreach ($assistants as $assistant)
		{
			$hidden = isset($assistant['data']['featureEnabled']) && $assistant['data']['featureEnabled'] === false;
			$items[] = [
				"title" => $assistant["name"],
				"hidden" => $hidden,
				"attrs" => [
					"url" =>
						"/mobile/marketplace/?id=$assistantAppId&" .
						"lazyload=Y&mobileMode=Y&assistantId={$assistant["id"]}",
					"cache" => false,
				],
			];
		}

		$menuStructure[] = [
			"title" => Loc::getMessage("MENU_AI"),
			"sort" => 110,
			"hidden" => false,
			"items" => $items,
		];
	}
}

if (Option::get('mobile', 'developers_menu_section', 'N') === 'Y')
{
	$developerMenuItems = [];
	$isEnableStoryBook = false;
	foreach (EventManager::getInstance()->findEventHandlers("mobileapp", "onJNComponentWorkspaceGet", ['mobile']) as $event)
	{
		if ($event['TO_METHOD'] === 'getJNDevWorkspace')
		{
			$isEnableStoryBook = true;
		}
	}

	if ($isEnableStoryBook)
	{
		$developerMenuItems[] = [
			"title" => "StoryBook",
			"imageUrl" => $imageDir . "favorite/storybook.png",
			"hidden" => false,
			"attrs" => [
				"id" => "StoryBook",
				"onclick" => <<<JS
				ComponentHelper.openLayout({
					name: 'dev:storybook',
					object: 'layout',
					widgetParams: {
						title: 'StoryBook'
					}
				});
JS,
			],
		];
	}

	$developerMenuItems[] = [
		"title" => "Frontend Unit Tests",
		"imageUrl" => $imageDir . "catalog/icon-catalog-store.png",
		"color" => '#8590a2',
		"hidden" => false,
		"attrs" => [
			"id" => "unit.tests",
			"onclick" => <<<JS
				ComponentHelper.openLayout({
					name: 'unit.tests',
					object: 'layout',
					widgetParams: {
						title: 'Frontend Unit Tests'
					}
				});
JS,
		],
	];

	$developerMenuItems[] = [
		"title" => "Developer playground",
		"color" => '#8590a2',
		"imageUrl" => $imageDir . "catalog/icon-catalog-store.png",
		"hidden" => false,
		"attrs" => [
			"id" => "playground",
			"onclick" => <<<JS
				ComponentHelper.openLayout({
					name: 'playground',
					object: 'layout',
					widgetParams: {
						title: 'Developer playground'
					}
				});
JS,
		],
	];

	$developerMenuItems[] = [
		"title" => "Fields Test",
		"imageUrl" => $imageDir . "catalog/icon-catalog-store.png",
		"color" => '#8590a2',
		"hidden" => false,
		"attrs" => [
			"id" => "fields.component",
			"onclick" => <<<JS
				ComponentHelper.openLayout({
						name: "fields.test",
						version: '1',
						object: "layout",
						componentParams: {},
						widgetParams: {
							title: "Fields Test"
						}
				});
JS,
		],
	];

	$developerMenuItems[] = [
		"title" => "ListView benchmark",
		"imageUrl" => $imageDir . "catalog/icon-catalog-store.png",
		"color" => '#8590a2',
		"hidden" => false,
		"attrs" => [
			"id" => "listview.benchmark",
			"onclick" => <<<JS
				ComponentHelper.openLayout({
					name: 'dev:list-view-benchmark',
					object: 'layout',
					widgetParams: {
						title: 'ListView benchmark'
					}
				});
			JS,
		],
	];

	$developerMenuItems[] = [
		"title" => "Rich-text editor sandbox",
		"imageUrl" => $imageDir . "catalog/icon-catalog-store.png",
		"color" => '#8590a2',
		"hidden" => false,
		"attrs" => [
			"id" => "text-editor-demo",
			"onclick" => <<<JS
				ComponentHelper.openLayout({
					name: 'dev:text-editor-sandbox',
					object: 'layout',
					widgetParams: {
						title: 'Text editor sandbox'
					}
				});
			JS,
		],
	];

	$developerMenuItems[] = [
		"title" => "Formatter sandbox",
		"imageUrl" => $imageDir . "catalog/icon-catalog-store.png",
		"color" => '#8590a2',
		"hidden" => false,
		"attrs" => [
			"id" => "formatter-sandbox",
			"onclick" => <<<JS
				ComponentHelper.openLayout({
					name: 'dev:formatter-sandbox',
					object: 'layout',
					widgetParams: {
						title: 'Formatter sandbox',
					}
				});
			JS,
		],
	];

	if (!empty($developerMenuItems))
	{
		$menuStructure[] = [
			"title" => "Development",
			"sort" => 150,
			"hidden" => false,
			"items" => $developerMenuItems,
		];
	}
}

$useAssets = Mobile::getApiVersion() >= 54;
$popupMenuItems = [
	[
		"title" => Loc::getMessage("MENU_CHANGE_ACCOUNT"),
		"sectionCode" => "menu",
		"id" => "switch_account",
		"iconName" => "log_out",
		"iconUrl" => !$useAssets ? $imageDir . "settings/change_account_popup.png?5" : null,
		"onclick" => <<<JS
				Application.exit();
JS
		,
	],
];

return [
	"menu" => $menuStructure,
	"popupMenuItems" => $popupMenuItems,
];
