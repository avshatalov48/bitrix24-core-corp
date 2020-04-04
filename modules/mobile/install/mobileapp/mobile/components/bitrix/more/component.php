<?

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Controller\User\StressLevel;
use Bitrix\Socialnetwork\Item\UserWelltory;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $USER, $CACHE_MANAGER;

CModule::IncludeModule("mobile");
CModule::IncludeModule("mobileapp");

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

$userId = $USER->getId();
$arResult = [];
$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
$extEnabled = IsModuleInstalled('extranet');
$menuSavedModificationTime = \Bitrix\Main\Config\Option::get("mobile", "jscomponent.menu.date.modified.user_" . $userId, 0);
$menuFile = new \Bitrix\Main\IO\File($this->path . ".mobile_menu.php");
$version = \Bitrix\MobileApp\Janative\Manager::getComponentVersion("more");
$menuModificationTime = $menuFile->getModificationTime();
$cacheIsActual = ($menuModificationTime == $menuSavedModificationTime);
if (!$cacheIsActual)
{
	$CACHE_MANAGER->ClearByTag('mobile_custom_menu' . $userId);
	$CACHE_MANAGER->ClearByTag('mobile_custom_menu');
	\Bitrix\Main\Config\Option::set("mobile", "jscomponent.menu.date.modified.user_" . $userId, $menuModificationTime);
}

$cache_id = 'user_mobile_menu_' . $userId . '_' . $extEnabled . '_' . LANGUAGE_ID . '_' . CSite::GetNameFormat(false) . "ver" . $version . "_" . $apiVersion;
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
	$arResult["user"] = $user;
	$arResult["user"]["fullName"] = CUser::FormatName(CSite::GetNameFormat(false), $user);
	$arResult["user"]["avatar"] = "";

	if ($user["PERSONAL_PHOTO"])
	{
		$imageFile = CFile::GetFileArray($user["PERSONAL_PHOTO"]);
		if ($imageFile !== false)
		{
			$avatar = CFile::ResizeImageGet($imageFile, ["width" => 150, "height" => 150], BX_RESIZE_IMAGE_EXACT, false, false, false, 50);
			$arResult["user"]["avatar"] = $avatar["src"];
		}
	}

	$CACHE_MANAGER->RegisterTag('sonet_group');
	$CACHE_MANAGER->RegisterTag('USER_CARD_' . intval($userId / TAGGED_user_card_size));
	$CACHE_MANAGER->RegisterTag('sonet_user2group_U' . $userId);
	$CACHE_MANAGER->RegisterTag('mobile_custom_menu' . $userId);
	$CACHE_MANAGER->RegisterTag('crm_change_role');
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
		$menu = ExecuteModuleEventEx($event, [$menu, $this]);
	}

	$arResult["menu"] = $menu;
}

$editProfilePath = \Bitrix\MobileApp\Janative\Manager::getComponentPath("user.profile");
$workPosition = $arResult["user"]["WORK_POSITION"];
$canEditProfile = $USER->CanDoOperation('edit_own_profile');
$apiVersion = Bitrix\MobileApp\Mobile::getApiVersion();
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
						"useTemplateRender" => true
					],
					"additionalImage" => [
						"name" => $canEditProfile ? "pencil" : "",
						"useTemplateRender" => true
					]
				],
				"title" => [
					"font" => [
						"fontStyle" => "medium",
						"size" => 19,
						"color" => "#333333"
					]
				]
			],
			"useLetterImage" => true,
			"subtitle" => $apiVersion < 27 || !$canEditProfile ? GetMessage("MENU_VIEW_PROFILE") : GetMessage("MENU_EDIT_PROFILE"),
			"params" => [
				"url" => SITE_DIR . "mobile/users/?ID=" . $userId,
				"onclick" => <<<JS
						if(Application.getApiVersion() < 27)
						{
							PageManager.openPage({url:this.params.url});
						}
						else
						{
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
						}
						
JS

			]
		]
	]
];

$counterList = [];
$isStressLevelTurnOn = Option::get('intranet', 'stresslevel_available', 'Y') == 'Y';
$showStressItemCondition =(!Loader::includeModule('bitrix24') || \Bitrix\Bitrix24\Release::isAvailable('stresslevel')) && $isStressLevelTurnOn ;
$arResult["releaseStressLevel"] = $showStressItemCondition;
if(Loader::includeModule('socialnetwork') && $showStressItemCondition)
{
	$favoriteSection = &$arResult["menu"][0];
	$colors = [
		"green" => "#9DCF00",
		"yellow" => "#F7A700",
		"red" => "#FF5752",
		"unknown" => "#C8CBCE"
	];

	$stressValue = false;
	$stressColor = $colors["unknown"];

	$stressItem = [
		"title" => Loc::getMessage("MB_BP_MAIN_STRESS_LEVEL"),
		"id" => "stress",
		"min_api_version" => 31,
		"imageUrl" => $this->getPath() . "/images/favorite/icon-stress.png?1",
		"color" => "#55D0E0",
		"hidden"=>\Bitrix\MobileApp\Mobile::$apiVersion < 31,
		"attrs" => [
			"id" => "stress",
			"onclick"=>""
		]

	];


	$data = UserWelltory::getHistoricData([
		'userId' => $USER->getId(),
		'limit' => 1
	]);

	if (!empty($data))
	{
		$result = $data[0];
		$initStressResult = \Bitrix\MobileApp\Janative\Utils::jsonEncode([
			"value"=>$result["value"],
			"type"=>$result["type"],
			"comment"=>$result["comment"],
			"token"=>$result["hash"],
			"date"=>$result["date"]
		]);

		$stressItem["styles"] = ["tag"=>["backgroundColor"=>$colors[$result["type"]] , "cornerRadius"=>15]];
		$stressItem["tag"] = $result["value"]."%";
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
		$stressItem["styles"] = ["tag"=>["backgroundColor"=>"#3BC8F5" , "cornerRadius"=>5]];
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

	array_unshift($favoriteSection["items"], $stressItem);
}

usort($arResult["menu"], 'sortMenu');

array_walk($arResult["menu"], function (&$section) use (&$counterList) {
	if (isset($section["items"]) && is_array($section["items"]))
	{
		array_walk($section["items"], function (&$item) use (&$counterList, $section) {
			if (isset($item["hidden"]) && $item["hidden"] == true)
			{
				return;
			}

			$item["sectionCode"] = "section_" . $section["sort"];
			if ($item["attrs"])
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

			if ($item["params"]["counter"] && !in_array($item["params"]["counter"], $counterList))
			{
				$counterList[] = $item["params"]["counter"];
			}

			if ($item["type"] != "destruct" && $item["type"] != "button")
			{
				if (!$item["styles"])
				{
					$item["styles"] = [];
				}

				if (!$item["styles"]["title"]["font"])
				{
					$item["styles"]["title"] = ["font" => [
						"fontStyle" => "medium",
						"size" => 16,
						"color" => "#333333"
					]];
				}

				if ($item["type"] != "userinfo")
				{
					$item["height"] = 60;
				}
			}

		});
	}
});


$arResult["counterList"] = $counterList;

unset($obCache);

return $arResult;