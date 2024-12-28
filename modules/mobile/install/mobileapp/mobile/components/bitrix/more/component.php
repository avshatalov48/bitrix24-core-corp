<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Bitrix\Socialnetwork\Item\UserWelltory;
use Bitrix\Intranet\Invitation;
use Bitrix\Crm\Terminal\AvailabilityManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $USER, $CACHE_MANAGER;

CModule::IncludeModule("mobile");
CModule::IncludeModule("mobileapp");

Loc::loadMessages(__DIR__ . '/.mobile_menu.php');

function sortMenu($item, $anotherItem)
{
	$itemSort = (array_key_exists("sort", $item) ? $item["sort"] : 100);
	$anotherSort = (array_key_exists("sort", $anotherItem) ? $anotherItem["sort"] : 100);
	if ($itemSort > $anotherSort)
	{
		return 1;
	}

	if ($itemSort == $anotherSort)
	{
		return 0;
	}

	return -1;
}

$isExtranetUser = (\CModule::includeModule("extranet") && !\CExtranet::isIntranetUser());
$isCollaber = (new \Bitrix\Mobile\Context())->isCollaber;
$apiVersion = Bitrix\MobileApp\Mobile::getApiVersion();
$canInviteUsers = (
	Loader::includeModule('intranet')
	&& Invitation::canCurrentUserInvite()
);

$registerUrl = $canInviteUsers ? Invitation::getRegisterUrl() : '';
$registerAdminConfirm = $canInviteUsers ? Invitation::getRegisterAdminConfirm() : false;
$disableRegisterAdminConfirm = !Invitation::canListDelete();
$registerSharingMessage = $canInviteUsers ? Invitation::getRegisterSharingMessage() : '';

$rootStructureSectionId = Invitation::getRootStructureSectionId();
$userId = $USER->getId();
$arResult = [];
$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
$extEnabled = IsModuleInstalled('extranet');
$menuSavedModificationTime = \Bitrix\Main\Config\Option::get("mobile", "jscomponent.menu.date.modified.user_" . $userId, 0);
$menuFile = new \Bitrix\Main\IO\File($this->path . ".mobile_menu.php");
$version = $this->getVersion();
$menuModificationTime = $menuFile->getModificationTime();
$cacheIsActual = ($menuModificationTime == $menuSavedModificationTime);
$clearOptionName = "clear_more_$userId";
$force = \Bitrix\Main\Config\Option::get("mobile", $clearOptionName, false);

if (!$cacheIsActual || $force)
{
	$CACHE_MANAGER->ClearByTag('mobile_custom_menu' . $userId);
	$CACHE_MANAGER->ClearByTag('mobile_custom_menu');
	\Bitrix\Main\Config\Option::set("mobile", "jscomponent.menu.date.modified.user_" . $userId, $menuModificationTime);
	\Bitrix\Main\Config\Option::set("mobile", $clearOptionName, false);
}
$cache_id = 'more_menu_'
	. implode(
		'_',
		[
			$userId,
			$extEnabled,
			LANGUAGE_ID,
			CSite::GetNameFormat(false) . 'ver' . $version,
			$apiVersion,
			/**
			 * Should be removed after the release option release-spring-2023 is set to true!
			 */
			md5(
				serialize([
					'isTerminalAvailable' => (int)(
						Loader::includeModule('crm')
						&& AvailabilityManager::getInstance()->isAvailable()
					),
				])
			),
		]
	);
$cache_dir = '/bx/mobile_component/more/user_' . $userId;
$obCache = new CPHPCache;

if ($obCache->InitCache($ttl, $cache_id, $cache_dir))
{
	$arResult = $obCache->GetVars();
}
else
{
	$CACHE_MANAGER->StartTagCache($cache_dir);
	$arResult = include(".mobile_menu.php");
	$host = Bitrix\Main\Context::getCurrent()->getServer()->getHttpHost();
	$host = preg_replace("/:(80|443)$/", "", $host);
	$arResult["host"] = htmlspecialcharsbx($host);
	$user = $USER->GetByID($userId)->Fetch();
	$arResult["user"]["fullName"] = CUser::FormatName(CSite::GetNameFormat(false), $user);
	$arResult["user"]["avatar"] = "";

	if ($user["PERSONAL_PHOTO"])
	{
		$imageFile = CFile::GetFileArray($user["PERSONAL_PHOTO"]);
		if ($imageFile !== false)
		{
			$avatar = CFile::ResizeImageGet($imageFile, [
				"width" => 150,
				"height" => 150,
			], BX_RESIZE_IMAGE_EXACT, false, false, false, 50);
			$arResult["user"]["avatar"] = $avatar["src"];
		}
	}

	$CACHE_MANAGER->RegisterTag('sonet_group');
	$CACHE_MANAGER->RegisterTag('crm_initiated');
	$CACHE_MANAGER->RegisterTag('USER_CARD_' . intval($userId / TAGGED_user_card_size));
	$CACHE_MANAGER->RegisterTag('sonet_user2group_U' . $userId);
	$CACHE_MANAGER->RegisterTag('mobile_custom_menu' . $userId);
	$CACHE_MANAGER->RegisterTag('mobile_custom_menu');
	$CACHE_MANAGER->RegisterTag('crm_change_role');
	$CACHE_MANAGER->RegisterTag('bitrix24_left_menu');
	$CACHE_MANAGER->EndTagCache();

	if ($obCache->StartDataCache())
	{
		$obCache->EndDataCache($arResult);
	}
}
$events = \Bitrix\Main\EventManager::getInstance()->findEventHandlers("mobile", "onMobileMenuStructureBuilt");
if (count($events) > 0)
{
	$menu = $arResult["menu"];
	foreach ($events as $event)
	{
		$modifiedMenu = ExecuteModuleEventEx($event, [$menu, $this]);
		if ($modifiedMenu != null)
		{
			$menu = $modifiedMenu;
		}

	}

	$arResult["menu"] = $menu;
}

$arResult['spotlights'] = [];

$event = new \Bitrix\Main\Event('mobile', 'onMobileMenuSpotlightBuildList', []);
$event->send();
foreach ($event->getResults() as $eventResult)
{
	/** @var \Bitrix\Main\EventResult $eventResult */
	$spotlight = $eventResult->getParameters();
	if (is_array($spotlight))
	{
		$arResult['spotlights'][] = $spotlight;
	}
}

$isAvaMenuAvailable = \Bitrix\MobileApp\Mobile::getInstance()::getApiVersion() >= 54;
if ($isAvaMenuAvailable)
{
	$arResult["menu"][] = [
		"title" => "",
		"sort" => 0,
		"items" => [],
	];
}
else
{
	$editProfilePath = \Bitrix\MobileApp\Janative\Manager::getComponentPath("user.profile");
	$workPosition = \CUtil::addslashes($arResult["user"]["WORK_POSITION"] ?? '');
	$canEditProfile = $USER->CanDoOperation('edit_own_profile');

	$arResult["menu"][] = [
		"title" => "",
		"sort" => 0,
		"items" => [
			[
				"title" => $arResult["user"]["fullName"],
				"imageUrl" => $arResult["user"]["avatar"],
				"type" => "userinfo",
				"color" => '#404f5d',
				"styles" => [
					"subtitle" => [
						"image" => [
							"useTemplateRender" => true,
						],
						"additionalImage" => [
							"name" => $canEditProfile ? "pencil" : "",
							"useTemplateRender" => true,
						],
					],
					"title" => [
						"font" => [
							"fontStyle" => "medium",
							"size" => 19,
							"color" => "#333333",
						],
					],
				],
				"useLetterImage" => true,
				"subtitle" => $canEditProfile ? GetMessage("MENU_EDIT_PROFILE") : GetMessage("MENU_VIEW_PROFILE"),
				"params" => [
					"url" => SITE_DIR . "mobile/users/?ID=" . $userId,
					"onclick" => <<<JS
					let canEdit = Boolean($canEditProfile);
					let imageUrl =  this.imageUrl? this.imageUrl: "";
					let top = {
								imageUrl: imageUrl,
								value: imageUrl,
								title: this.title,
								subtitle: "$workPosition",
								sectionCode: "top",
								height: 160,
								type:"userpic",
								useLetterImage:true,
								color:"#2e455a"
					};

					PageManager.openComponent("JSStackComponent",
					{
						scriptPath:"$editProfilePath",
						componentCode: "profile.view",
						params: {
							"userId": $userId,
							mode:canEdit?"edit":"view",
							items:[
									top,
									{ type:"loading", sectionCode:"1", title:""}
								],
								sections:[
									{id: "top", backgroundColor:"#f0f0f0"},
									{id: "1", backgroundColor:"#f0f0f0"},
								]
						},
						rootWidget:{
							name:canEdit?"form":"list",
							settings:{
								objectName:"form",
								items:[
									{
										"id":"PERSONAL_PHOTO",
										useLetterImage:true,
										color:"#2e455a",
										imageUrl: this.imageUrl,
										type:"userpic",
										title:this.title,
										sectionCode:"0"},
									{ type:"loading", sectionCode:"1", title:""}
								],
								sections:[
									{id: "0", backgroundColor:"#f0f0f0"},
									{id: "1", backgroundColor:"#f0f0f0"},
								],
								groupStyle: true,
								title: BX.message("PROFILE_INFO")
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

$counterList = [];
$isStressLevelTurnOn = Option::get('intranet', 'stresslevel_available', 'Y') == 'Y';
$showStressItemCondition = (!Loader::includeModule('bitrix24') || \Bitrix\Bitrix24\Release::isAvailable('stresslevel')) && $isStressLevelTurnOn;
$arResult["releaseStressLevel"] = $showStressItemCondition;
if (Loader::includeModule('socialnetwork') && $showStressItemCondition)
{
	$favoriteSection = &$arResult["menu"][0];
	$colors = [
		"green" => "#9DCF00",
		"yellow" => "#F7A700",
		"red" => "#FF5752",
		"unknown" => "#C8CBCE",
	];

	$stressValue = false;
	$stressColor = $colors["unknown"];

	$stressItem = [
		"title" => Loc::getMessage("MB_BP_MAIN_STRESS_LEVEL"),
		"id" => "stress",
		"min_api_version" => 31,
		"imageUrl" => $this->getPath() . "/images/favorite/icon-stress.png?1",
		"color" => "#55D0E0",
		'imageName' => 'stress',
		"hidden" => false,
		"attrs" => [
			"id" => "stress",
			"onclick" => "",
		],

	];

	$data = UserWelltory::getHistoricData([
		'userId' => $USER->getId(),
		'limit' => 1,
	]);

	if (!empty($data))
	{
		$result = $data[0];
		$initStressResult = \Bitrix\MobileApp\Janative\Utils::jsonEncode([
			"value" => $result["value"],
			"type" => $result["type"],
			"comment" => $result["comment"],
			"token" => $result["hash"],
			"date" => $result["date"],
		]);

		$stressItem["styles"] = ["tag" => ["backgroundColor" => $colors[$result["type"]], "cornerRadius" => 15]];
		$stressItem["tag"] = $result["value"] . "%";
		$stressItem["initData"] = $initStressResult;
		$onclick = <<<JS
			if(typeof window.version  === "undefined" || window.version < 1.0)
			{
				reload();
			}
			else
			{
				let initResult = $initStressResult;
				if(initResult["value"])
					{
						initResult["date"] = new Date(initResult["date"]).toLocaleString();
					}
				else
					initResult = null;

				openStressWidget(initResult, false);
			}
JS;

	}
	else
	{
		$stressItem["styles"] = ["tag" => ["backgroundColor" => "#3BC8F5", "cornerRadius" => 5]];
		$stressItem["tag"] = Loc::getMessage("MEASURE_STRESS");
		$onclick = <<<JS
			if(typeof window.version  === "undefined" || window.version < 1.0)
			{
				reload();
			}
			else
			{
				openStressWidget(null, false);
			}
JS;
	}

	$stressItem["attrs"]["onclick"] = $onclick;
	if (!is_array($favoriteSection["items"]))
	{
		$favoriteSection["items"] = [];
	}
	$favoriteSection["items"][] = $stressItem;
}

usort($arResult["menu"], 'sortMenu');

usort($arResult['spotlights'], function ($item1, $item2) {
	$delayCount1 = (int)($item1['delayCount'] ?? 0);
	$delayCount2 = (int)($item2['delayCount'] ?? 0);

	if ($delayCount1 !== $delayCount2)
	{
		return $delayCount1 - $delayCount2;
	}

	$sort1 = (int)($item1['sort'] ?? 100);
	$sort2 = (int)($item2['sort'] ?? 100);

	return $sort1 - $sort2;
});

array_walk($arResult["menu"], function (&$section) use (&$counterList) {
	if (isset($section["items"]) && is_array($section["items"]))
	{
		array_walk($section["items"], function (&$item) use (&$counterList, $section) {
			if (isset($item["hidden"]) && $item["hidden"] == true)
			{
				return;
			}

			$item["sectionCode"] = "section_" . $section["sort"];
			if (!empty($item["attrs"]))
			{
				$item["params"] = $item["attrs"];
				unset($item["attrs"]);
			}
			else
			{
				if (!$item["params"])
				{
					$item["params"] = [];
				}
			}

			unset($item["attrs"]);

			if (!empty($item["params"]["counter"]) && !in_array($item["params"]["counter"], $counterList))
			{
				$counterList[] = $item["params"]["counter"];
			}

			$type = $item["type"] ?? "";
			if ($type != "destruct" && $type != "button")
			{
				if (empty($item["styles"]))
				{
					$item["styles"] = [];
				}

				if (empty($item["styles"]["title"]["font"]))
				{
					$item["styles"]["title"] = [
						"font" => [
							"fontStyle" => "medium",
							"size" => 16,
							"color" => "#333333",
						],
					];
				}

				if ($type != "userinfo")
				{
					$item["height"] = 60;
				}
			}

		});
	}
});

$events = \Bitrix\Mobile\Tourist::getEvents();

$showPresetsCounter = false;
if (!isset($events['visited_tab_presets']))
{
	$user = UserTable::getById($USER->GetID())->fetchObject();
	if ($user && $user->getDateRegister())
	{
		$time = 1733875200;
		if (\CModule::IncludeModule('bitrix24'))
		{
			$zone = \CBitrix24::getPortalZone();
			if ($zone === 'ru')
			{
				$time = 1732579200;
			}
		}

		$showPresetsCounter = $user->getDateRegister()->getTimestamp() > $time;
	}
}

$usersCount = 0;
$isUserFirstTimeInInvitations = !isset($events['visit_invitations']);
$isUserAdmin = ((\CModule::IncludeModule('bitrix24') ? \CBitrix24::isPortalAdmin($USER->GetID()) : $USER->isAdmin()));

if (
	$isUserFirstTimeInInvitations
	&& $isUserAdmin
	&& Loader::includeModule('intranet')
	&& Loader::includeModule('intranetmobile')
)
{
	$usersCount = (new \Bitrix\IntranetMobile\Provider\UserProvider())->getAllUsersCount();
}

$showDiskCounter = !isset($events['visited_disk_tabs']);

$arResult = array_merge($arResult, [
	"counterList" => $counterList,
	"invite" => [
		"canInviteUsers" => $canInviteUsers,
		"registerUrl" => $registerUrl,
		"registerAdminConfirm" => $registerAdminConfirm,
		"disableRegisterAdminConfirm" => $disableRegisterAdminConfirm,
		"registerSharingMessage" => $registerSharingMessage,
		"rootStructureSectionId" => $rootStructureSectionId,
	],
	'customCounters' => [
		'menu_invite' => ($isUserAdmin && $usersCount === 1 && $isUserFirstTimeInInvitations) ? 1 : 0,
		'menu_tab_presets' => $showPresetsCounter ? 1 : 0,
		'menu_disk_tabs' => $showDiskCounter ? 1 : 0,
	],
]);

unset($obCache);

return $arResult;
