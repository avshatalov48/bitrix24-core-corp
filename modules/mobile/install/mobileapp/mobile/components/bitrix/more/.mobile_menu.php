<?php

use Bitrix\Intranet\AI;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Main\ModuleManager;
use Bitrix\Mobile\Tab\Manager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $USER;

/**
 * @var  $USER CAllUser
 * @var  $this \Bitrix\MobileApp\Janative\Entity\Component
 * @var  $isExtranetUser bool
 */

$allowedFeatures = [];

$hereDocGetMessage = function ($code) {
	return Loc::getMessage($code);
};

if (CModule::IncludeModule("socialnetwork"))
{
	$socNetFeatures = new \Bitrix\Mobile\Component\SocNetFeatures($USER->getId());
	$allowedFeatures = [];
	foreach (["tasks", "files", "calendar"] as $feature)
	{
		$allowedFeatures[$feature] = $feature == "calendar"
			? $socNetFeatures->isEnabledForGroup($feature)
			: $socNetFeatures->isEnabledForUser($feature);
	}
}

$diskEnabled = Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk');
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

$diskComponentVersion = \Bitrix\MobileApp\Janative\Manager::getComponentVersion("user.disk");
$calendarComponentPath = \Bitrix\MobileApp\Janative\Manager::getComponentPath("calendar:calendar.events");
$calendarComponentVersion = \Bitrix\MobileApp\Janative\Manager::getComponentVersion("calendar:calendar.events");
$workgroupsComponentVersion = \Bitrix\MobileApp\Janative\Manager::getComponentVersion("workgroups");
$catalogStoreDocumentListComponentVersion = \Bitrix\MobileApp\Janative\Manager::getComponentVersion("catalog.store.document.list");

$taskParams = json_encode([
	"COMPONENT_CODE" => "tasks.list",
	"USER_ID" => $USER->GetId(),
	"SITE_ID" => SITE_ID,
	"LANGUAGE_ID" => LANGUAGE_ID,
	"SITE_DIR" => SITE_DIR,
	"PATH_TO_TASK_ADD" => "/mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#",
	"MESSAGES" => [],
]);

$calendarMobileJSComponentsIsUsed = false;

if (Loader::includeModule('calendarmobile'))
{
	$calendarMobileJSComponentsIsUsed = \Bitrix\CalendarMobile\JSComponent::isUsed();
}

$menuStructure = [];
$favoriteItems = [];

if (\Bitrix\MobileApp\Mobile::getApiVersion() < 41)
{
	$favoriteItems[] = [
		"hidden" => ($isExtranetUser || !ModuleManager::isModuleInstalled("bizproc")),
		"title" => Loc::getMessage("MB_BP_MAIN_MENU_ITEM"),
		"imageUrl" => $imageDir . "favorite/icon-bp.png",
		"color" => "#33c3bd",
		"attrs" => [
			"url" => $siteDir . "mobile/bp/?USER_STATUS=0",
			"id" => "bp_list",
			"counter" => "bp_tasks",
		],
	];
}

if ($calendarMobileJSComponentsIsUsed)
{
	$favoriteItems[] = [
		"title" => Loc::getMessage("MB_CALENDAR_LIST"),
		"imageUrl" => $imageDir . "favorite/icon-calendar.png",
		"color" => "#F5A200",
		"hidden" => $isExtranetUser && !$allowedFeatures["calendar"],
		"actions" => [
			[
				"title" => Loc::getMessage("MORE_ADD"),
				"identifier" => "add",
				"color" => "#7CB316",
			],
		],
		"attrs" => [
			"actionOnclick" => <<<JS
					PageManager.openPage({url:"/mobile/calendar/edit_event.php", modal:true, data:{ modal:"Y"}});
JS
			,
			"onclick" => <<<JS
			PageManager.openComponent("JSStackComponent",
						{
							scriptPath:"{$calendarComponentPath}",
							componentCode: "calendar",							
							rootWidget:{
								name:"list",
								settings:{
									title: this.title,
									objectName: "list",
								}
							}
						});
JS
			,
		],
	];
}
else
{
	$favoriteItems[] = [
		"title" => Loc::getMessage("MB_CALENDAR_LIST"),
		"imageUrl" => $imageDir . "favorite/icon-calendar.png",
		"color" => "#F5A200",
		"actions" => [
			[
				"title" => Loc::getMessage("MORE_ADD"),
				"identifier" => "add",
				"color" => "#7CB316",
			],
		],
		"attrs" => [
			"actionOnclick" => <<<JS
					PageManager.openPage({url:"/mobile/calendar/edit_event.php", modal:true, data:{ modal:"Y"}});
JS
			,
			"onclick" => <<<JS

			PageManager.openList(
			{
				url:"/mobile/?mobile_action=calendar&user_id="+$userId,
				table_id:"calendar_list",
				table_settings:
				{
					showTitle:"YES",
					name:"{$hereDocGetMessage("MB_CALENDAR_LIST")}",
					useTagsInSearch:"NO",
					button:{
						type: 'plus',
						eventName:"onCalendarEventAddButtonPushed"
					}
				}
			});

			if(typeof calendarEventAttached == "undefined")
			{
				calendarEventAttached = true;
				BX.addCustomEvent("onCalendarEventAddButtonPushed", ()=>{
					PageManager.openPage({url:"/mobile/calendar/edit_event.php", modal:true, data:{ modal:"Y"}});
				});
			}
JS
			,
		],

		"hidden" => !(ModuleManager::isModuleInstalled('calendar') && !$isExtranetUser && $allowedFeatures["calendar"]),
	];
}

if (\Bitrix\MobileApp\Mobile::getApiVersion() < 41)
{
	$favoriteItems[] = [
		"title" => Loc::getMessage("MB_CURRENT_USER_FILES_MAIN_MENU_ITEM_NEW"),
		"imageUrl" => $imageDir . "favorite/icon-mydisk.png",
		"color" => "#20A1E7",
		"attrs" => [
			"onclick" => <<<JS

				if(Application.getApiVersion() >= 28)
				{
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
				}
				else
				{
					PageManager.openList(
					{
						url:"/mobile/?mobile_action=disk_folder_list&type=user&path=/&entityId="+$userId,
						table_settings:
						{
							showTitle:"YES",
							name: "{$hereDocGetMessage("MB_CURRENT_USER_FILES_MAIN_MENU_ITEM_NEW")}",
							useTagsInSearch:"NO",
							type:"files",
						}
					});
				}
JS
			,
			"id" => "doc_user",
		],
		"hidden" => !$diskEnabled || !$allowedFeatures["files"],
		"id" => "doc_user",
	];
}

$favoriteItems[] = [
	"title" => Loc::getMessage("MB_CURRENT_USER_FILES_MAIN_MENU_ITEM_NEW"),
	"imageUrl" => $imageDir . "favorite/icon-mydisk.png",
	"color" => "#20A1E7",
	"attrs" => [
		"url" => '/mobile/?mobile_action=disk_folder_list&type=user&path=/&entityId=' . $USER->GetID(),
		"table_settings" => [
			"useTagsInSearch" => "NO",
			"type" => "files",
		],
		"_type" => "list",
		"id" => "doc_user",
	],
	"hidden" => $diskEnabled || !$allowedFeatures["files"],
];

$favoriteItems[] = [
	"imageUrl" => $imageDir . "favorite/icon-users.png",
	"color" => "#AF9245",
	"title" => Loc::getMessage($isExtranetUser ? "MB_CONTACTS" : "MB_COMPANY"),
	"attrs" => [
		"onclick" =>
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
				title:"{$hereDocGetMessage($isExtranetUser ? "MB_CONTACTS" : "MB_COMPANY")}",
				settings: {useSearch: true},
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
JS
		,
	],
	"id" => "users",
];

$favoriteItems[] = [
	"imageUrl" => $imageDir . "favorite/icon-disk.png",
	"color" => "#3CD162",
	"title" => Loc::getMessage("MB_SHARED_FILES_MAIN_MENU_ITEM_NEW"),
	"attrs" => [
		"onclick" => <<<JS

			if(Application.getApiVersion() >= 28)
				{
					ComponentHelper.openList({
							name:"user.disk",
							object:"list",
							version:"{$diskComponentVersion}",
							componentParams:{userId: env.userId, ownerId: "shared_files_"+env.siteId, entityType:"common"},
							widgetParams:{title:"{$hereDocGetMessage("MB_SHARED_FILES_MAIN_MENU_ITEM_NEW")}", useSearch: true}
					});
				}
				else
				{
					PageManager.openList(
					{
						url:"/mobile/?mobile_action=disk_folder_list&type=common&path=/&entityId=shared_files_"+env.siteId,
						table_settings:
						{
							name:"{$hereDocGetMessage("MB_SHARED_FILES_MAIN_MENU_ITEM_NEW")}",
							showTitle:"YES",
							useTagsInSearch:"NO",
							type:"files",
						}
					});
				}

JS
		,
		"id" => "doc_shared",
	],
	"hidden" => !$diskEnabled || $isExtranetUser || !$allowedFeatures["files"],
];

$favoriteItems[] = [
	"title" => Loc::getMessage("MB_SHARED_FILES_MAIN_MENU_ITEM_NEW"),
	"imageUrl" => $imageDir . "favorite/icon-disk.png",
	"color" => "#b9bdc3",
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
	"hidden" => $diskEnabled || $isExtranetUser || !$allowedFeatures["files"],
];

$favorite = [
	"title" => Loc::getMessage("MB_SEC_FAVORITE"),
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

	if (count($arMenuApps) > 0)
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
}

/**
 * Catalog menu
 */

if (
	!$isExtranetUser
	&& IsModuleInstalled('catalog')
	&& CModule::IncludeModule('catalog')
)
{
	$catalogMenuItems = [];

	if (AccessController::getCurrent()->check(\Bitrix\Catalog\Access\ActionDictionary::ACTION_CATALOG_READ))
	{
		$storeItemTitle = Loc::getMessage("MENU_CATALOG_STORE");
		if (Option::get('mobile', 'catalog_store_test', 'N') === 'Y')
		{
			$catalogMenuItems[] = [
				"title" => "Entity Selector Test",
				"imageUrl" => $imageDir . "catalog/icon-catalog-store.png",
				"color" => '#8590a2',
				"hidden" => false,
				"attrs" => [
					"id" => "selector.test",
					"onclick" => <<<JS
						ComponentHelper.openLayout({
							name: 'selector.test',
							object: 'layout',
							widgetParams: {
								title: 'Entity Selector Test'
							}
						});
JS,
				],
			];

			$catalogMenuItems[] = [
				"title" => "Stage slider",
				"imageUrl" => $imageDir . "catalog/icon-catalog-store.png",
				"color" => '#8590a2',
				"hidden" => false,
				"attrs" => [
					"id" => "crm:crm.category.view.test",
					"onclick" => <<<JS
						ComponentHelper.openLayout({
							name: 'crm:crm.category.view.test',
							object: 'layout',
							widgetParams: {
								title: 'Stage slider'
							},
							componentParams: {
										entityTypeId: 2,
										categoryId: 1,
										readOnly: true
							}
						});
JS,
				],
			];

			$catalogMenuItems[] = [
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

			$catalogMenuItems[] = [
				"title" => "crm:crm.category.list",
				"imageUrl" => $imageDir . "catalog/icon-catalog-store.png",
				"color" => '#8590a2',
				"hidden" => false,
				"attrs" => [
					"id" => "crm:crm.category.list",
					"onclick" => <<<JS
				ComponentHelper.openLayout({
					name: 'crm:crm.category.list',
					componentParams: {
						entityTypeId: 2,
						currentCategoryId: 0,
						readOnly: false,
					},
					widgetParams: {
						modal: true,
						backdrop: {
							showOnTop: true,
							swipeContentAllowed: false,
						}
					}
				});
JS,
				],
			];

			$catalogMenuItems[] = [
				"title" => "crm:crm.test",
				"imageUrl" => $imageDir . "catalog/icon-catalog-store.png",
				"color" => '#8590a2',
				"hidden" => false,
				"attrs" => [
					"id" => "crm:crm.test",
					"onclick" => <<<JS
						ComponentHelper.openLayout({
								name: "crm:crm.test",
								version: '1',
								object: "layout",
								componentParams: {},
								widgetParams: {
									title: "crm:crm.test"
								}
						});
JS,
				],
			];
		}
	}

	$menuStructure[] = [
		"title" => Loc::getMessage("MENU_CATALOG"),
		"sort" => 125,
		"code" => "catalog",
		"hidden" => false,
		"items" => $catalogMenuItems,
	];
}

if (!$isExtranetUser)
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
	"title" => Loc::getMessage('MB_SEC_GROUPS'),
	"sort" => 130,
	"hidden" => false,
	"items" => [],
];

if (!$isExtranetUser)
{
	$menuName = Loc::getMessage('MENU_INTRANET');
	$groupSection["items"][] = [
		"title" => $menuName,
		"attrs" => [
			"onclick" => <<<JS
				if (Application.getApiVersion() >= 28)
				{
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
							title: "{$menuName}",
							useSearch: false,
							doNotHideSearchResult: true
						}
					});	
				}
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
						title: "{$menuName}",
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

$timemanEnabledForUser = false;
if (Loader::includeModule('timeman'))
{
	$timemanEnabledForUser = CTimeMan::CanUse();
}
$menuStructure[] = [
	"title" => GetMessage("MENU_WORK_DAY"),
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

$settingsComponentPath = \Bitrix\MobileApp\Janative\Manager::getComponentPath("settings");
$qrComponentPath = \Bitrix\MobileApp\Janative\Manager::getComponentPath("qrcodeauth");
$settingsUserId = $USER->GetID();
$settingsSiteId = SITE_ID;
$isUserAdmin = ((\CModule::IncludeModule('bitrix24') ? \CBitrix24::isPortalAdmin($settingsUserId) : $USER->isAdmin()))
	? "true" : "false";

$settingsLanguageId = LANGUAGE_ID;

$menuStructure[] = [
	'title' => Loc::getMessage('MB_SEC_B24'),
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
							rootWidget:{
								name:"settings",
								settings:{
									objectName: "settings",
									title: this.title
								}
							}
						});
JS
				,
			],
		]
	],
];

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
		"imageUrl" => $imageDir . "catalog/icon-catalog-store.png",
		"color" => '#8590a2',
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
		"title" => "telegram connector",
		"imageUrl" => $imageDir . "favorite/stream.png",
		"color" => '#8590a2',
		"hidden" => false,
		"attrs" => [
			"id" => "imconnector:imconnector.telegram",
			"onclick" => <<<JS
				ComponentHelper.openLayout({
					name: 'imconnector:imconnector.telegram',
					object: 'layout',
					widgetParams: {
						title: 'Telegram',
						modal: true,
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

return [
	"menu" => $menuStructure,
	"popupMenuItems" => [
		[
			"title" => Loc::getMessage("MENU_CHANGE_ACCOUNT"),
			"sectionCode" => "menu",
			"id" => "switch_account",
			"iconUrl" => $imageDir . "settings/change_account_popup.png?5",
			"onclick" => <<<JS
				Application.exit();
JS
			,

		],
		[
			"title" => Loc::getMessage("MENU_PRESET_TAB"),
			"sectionCode" => "menu",
			"id" => "tab.settings",
			"iconUrl" => $imageDir . "settings/tab_settings.png?9",
			"onclick" => <<<JS
				PageManager.openComponent("JSStackComponent",{
						scriptPath: availableComponents["tab.presets"].publicUrl,
						rootWidget:{
							name: "layout",
							settings:{
									// backdrop:{},
									objectName: "layout",
									titleParams: { text: this.title, useLargeTitleMode: true}
								}
						}
					});
JS
			,

		],
	],
];
