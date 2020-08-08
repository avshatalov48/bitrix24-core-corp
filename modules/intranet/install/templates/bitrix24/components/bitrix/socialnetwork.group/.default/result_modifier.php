<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\Integration;

CSocNetLogComponent::processDateTimeFormatParams($arParams);

if (is_array($arResult["Owner"]))
{
	if (intval($arResult["Owner"]["USER_PERSONAL_PHOTO"]) > 0)
	{
		$arImage = CFile::ResizeImageGet(
			$arResult["Owner"]["USER_PERSONAL_PHOTO"], 
			array("width" => 100, "height" => 100),
			BX_RESIZE_IMAGE_EXACT
		);
	}
	else
	{
		$arImage = array("src" => "");
	}

	$arResult["Owner"]["USER_PERSONAL_PHOTO_FILE"]["SRC"] = $arImage["src"];
}

if (is_array($arResult["Moderators"]["List"]))
{
	foreach($arResult["Moderators"]["List"] as $key => $arModerator)
	{
		if (is_array($arModerator))
		{
			if (intval($arModerator["USER_PERSONAL_PHOTO"]) > 0)
			{
				$arImage = CFile::ResizeImageGet(
					$arModerator["USER_PERSONAL_PHOTO"], 
					array("width" => 100, "height" => 100),
					BX_RESIZE_IMAGE_EXACT
				);
			}
			else
			{
				$arImage = array("src" => "");
			}

			$arResult["Moderators"]["List"][$key]["USER_PERSONAL_PHOTO_FILE"]["SRC"] = $arImage["src"];
		}
	}
}

if (is_array($arResult["Members"]["List"]))
{
	foreach($arResult["Members"]["List"] as $key => $arMember)
	{
		if (is_array($arMember))
		{
			if (intval($arMember["USER_PERSONAL_PHOTO"]) > 0)
			{
				$arImage = CFile::ResizeImageGet(
					$arMember["USER_PERSONAL_PHOTO"], 
					array("width" => 100, "height" => 100),
					BX_RESIZE_IMAGE_EXACT
				);
			}
			else
			{
				$arImage = array("src" => "");
			}
			
			$arResult["Members"]["List"][$key]["USER_PERSONAL_PHOTO_FILE"]["SRC"] = $arImage["src"];
		}
	}
}

$arResult["Urls"]["Delete"] = CComponentEngine::MakePathFromTemplate(
	$arParams["PATH_TO_GROUP_DELETE"],
	array("group_id" => $arResult["Group"]["ID"])
);

$arResult["Urls"]["content_search"] = CComponentEngine::MakePathFromTemplate(
	$arParams["PATH_TO_GROUP_CONTENT_SEARCH"],
	array("group_id" => $arResult["Group"]["ID"])
);

if (!isset($arResult["CanView"]))
{
	$arResult["CanView"] = array();
}

/*
$arResult["CanView"]["content_search"] = (
	is_array($arResult["ActiveFeatures"]) && array_key_exists("search", $arResult["ActiveFeatures"]) &&
	CSocNetFeaturesPerms::CanPerformOperation(
		$GLOBALS["USER"]->GetID(),
		SONET_ENTITY_GROUP,
		$arResult["Group"]["ID"],
		"search",
		"view",
		CSocNetUser::IsCurrentUserModuleAdmin()
	)
);
*/

$arResult["CanView"]["content_search"] = false;

$arResult["FAVORITES"] = false;
if ($USER->IsAuthorized())
{
	$res = \Bitrix\Socialnetwork\WorkgroupFavoritesTable::getList(array(
		'filter' => array(
			'GROUP_ID' => $arResult["Group"]["ID"],
			'USER_ID' => $USER->getId()
		)
	));
	$arResult["FAVORITES"] = ($res->fetch());
}

$arResult["bChatActive"] = (
	ModuleManager::isModuleInstalled('im')
	&& Integration\Im\Chat\Workgroup::getGroupChatAvailable($arResult["Group"]["ID"])
	&& in_array($arResult["CurrentUserPerms"]["UserRole"], UserToGroupTable::getRolesMember())
);

$arResult["Types"] = \Bitrix\Socialnetwork\Item\Workgroup::getTypes(array(
	'currentExtranetSite' => $arResult["bExtranet"],
	'fullMode' => true
));


$arResult["Group"]["IS_EXTRANET_GROUP"] = (
	Loader::includeModule("extranet")
	&& CExtranet::isExtranetSocNetGroup($arResult["Group"]["ID"])
	? "Y"
	: "N"
);

$arResult['Group']['Type'] = \Bitrix\Socialnetwork\Item\Workgroup::getTypeByCode(array(
	'code' => \Bitrix\Socialnetwork\Item\Workgroup::getTypeCodeByParams(array(
		'fields' => array(
			'OPENED' => $arResult['Group']['OPENED'],
			'VISIBLE' => $arResult['Group']['VISIBLE'],
			'PROJECT' => $arResult['Group']['PROJECT'],
			'EXTERNAL' => $arResult["Group"]["IS_EXTRANET_GROUP"]
		),
		'fullMode' => true
	)),
	'fullMode' => true
));

$arResult["Urls"]["GroupsList"] = \Bitrix\Socialnetwork\ComponentHelper::getWorkgroupSEFUrl();

$arParams["SHOW_SEARCH_TAGS_CLOUD"] = (
	!isset($arParams["SHOW_SEARCH_TAGS_CLOUD"])
	|| $arParams["SHOW_SEARCH_TAGS_CLOUD"] != 'Y'
		? 'N'
		: 'Y'
);

$arParams["PATH_TO_GROUPS_LIST"] = \Bitrix\Socialnetwork\ComponentHelper::getWorkgroupSEFUrl();
$arParams["PATH_TO_GROUP_TAG"] = $arParams["PATH_TO_GROUPS_LIST"].(mb_strpos($arParams["PATH_TO_GROUPS_LIST"], '?') !== false ? '&' : '?')."TAG=#tag#&apply_filter=Y";

$arResult["Group"]["KEYWORDS_LIST"] = array();
if (
	isset($arResult["Group"]["KEYWORDS"])
	&& $arResult["Group"]["KEYWORDS"] <> ''
)
{
	$arResult["Group"]["KEYWORDS_LIST"] = explode(',', $arResult["Group"]["KEYWORDS"]);
	foreach($arResult["Group"]["KEYWORDS_LIST"] as $key => $val)
	{
		$val = trim($val);
		if ($val !== '')
		{
			$arResult["Group"]["KEYWORDS_LIST"][$key] = $val;
		}
		else
		{
			unset($arResult["Group"]["KEYWORDS_LIST"][$key]);
		}
	}
}