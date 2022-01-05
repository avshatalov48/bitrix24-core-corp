<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Integration;
use Bitrix\Socialnetwork\ComponentHelper;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

CSocNetLogComponent::processDateTimeFormatParams($arParams);

$pageTitle = Loc::getMessage('SONET_C6_CARD_TITLE');
if ($arResult['isScrumProject'])
{
	$pageTitle = Loc::getMessage('SONET_C6_CARD_TITLE_SCRUM');
}
elseif ($arResult['Group']['PROJECT'] === 'Y')
{
	$pageTitle = Loc::getMessage('SONET_C6_CARD_TITLE_PROJECT');
}
$APPLICATION->SetTitle($pageTitle);

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
	$arResult["Owner"]["NAME_FORMATTED"] = CUser::FormatName(
		$arParams["NAME_TEMPLATE"],
		array(
			"NAME" => htmlspecialcharsBack($arResult["Owner"]["USER_NAME"]),
			"LAST_NAME" => htmlspecialcharsBack($arResult["Owner"]["USER_LAST_NAME"]),
			"SECOND_NAME" => htmlspecialcharsBack($arResult["Owner"]["USER_SECOND_NAME"]),
			"LOGIN" => htmlspecialcharsBack($arResult["Owner"]["USER_LOGIN"])
		),
		true
	);
}

if (is_array($arResult["Moderators"]["List"]))
{
	foreach($arResult["Moderators"]["List"] as $key => $moderator)
	{
		if (is_array($moderator))
		{
			if (intval($moderator["USER_PERSONAL_PHOTO"]) > 0)
			{
				$arImage = CFile::ResizeImageGet(
					$moderator["USER_PERSONAL_PHOTO"],
					array("width" => 100, "height" => 100),
					BX_RESIZE_IMAGE_EXACT
				);
			}
			else
			{
				$arImage = array("src" => "");
			}

			$arResult["Moderators"]["List"][$key]["USER_PERSONAL_PHOTO_FILE"]["SRC"] = $arImage["src"];
			$arResult["Moderators"]["List"][$key]["NAME_FORMATTED"] = CUser::FormatName(
				$arParams["NAME_TEMPLATE"],
				array(
					"NAME" => htmlspecialcharsBack($moderator["USER_NAME"]),
					"LAST_NAME" => htmlspecialcharsBack($moderator["USER_LAST_NAME"]),
					"SECOND_NAME" => htmlspecialcharsBack($moderator["USER_SECOND_NAME"]),
					"LOGIN" => htmlspecialcharsBack($moderator["USER_LOGIN"])
				),
				true
			);
		}
	}
}

if (is_array($arResult["Members"]["List"]))
{
	foreach($arResult["Members"]["List"] as $key => $member)
	{
		if (is_array($member))
		{
			if (intval($member["USER_PERSONAL_PHOTO"]) > 0)
			{
				$arImage = CFile::ResizeImageGet(
					$member["USER_PERSONAL_PHOTO"],
					array("width" => 100, "height" => 100),
					BX_RESIZE_IMAGE_EXACT
				);
			}
			else
			{
				$arImage = array("src" => "");
			}
			
			$arResult["Members"]["List"][$key]["USER_PERSONAL_PHOTO_FILE"]["SRC"] = $arImage["src"];
			$arResult["Members"]["List"][$key]["NAME_FORMATTED"] = CUser::FormatName(
				$arParams["NAME_TEMPLATE"],
				array(
					"NAME" => htmlspecialcharsBack($member["USER_NAME"]),
					"LAST_NAME" => htmlspecialcharsBack($member["USER_LAST_NAME"]),
					"SECOND_NAME" => htmlspecialcharsBack($member["USER_SECOND_NAME"]),
					"LOGIN" => htmlspecialcharsBack($member["USER_LOGIN"])
				),
				true
			);

		}
	}
}

$arResult["Urls"]["Delete"] = CComponentEngine::MakePathFromTemplate(
	$arParams["PATH_TO_GROUP_DELETE"],
	array("group_id" => $arResult["Group"]["ID"])
);

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

$arResult["Types"] = \Bitrix\Socialnetwork\Helper\Workgroup::getTypes([
	'currentExtranetSite' => $arResult["bExtranet"],
]);

$arResult["groupTypeCode"] = \Bitrix\Socialnetwork\Helper\Workgroup::getTypeCodeByParams(array(
	'fields' => array(
		'VISIBLE' => (isset($arResult["Group"]['VISIBLE']) && $arResult["Group"]['VISIBLE'] === 'Y' ? 'Y' : 'N'),
		'OPENED' => (isset($arResult["Group"]['OPENED']) && $arResult["Group"]['OPENED'] === 'Y' ? 'Y' : 'N'),
		'PROJECT' => (isset($arResult["Group"]['PROJECT']) && $arResult["Group"]['PROJECT'] === 'Y' ? 'Y' : 'N'),
		'EXTERNAL' => (isset($arResult["Group"]["IS_EXTRANET_GROUP"]) && $arResult["Group"]["IS_EXTRANET_GROUP"] === 'Y' ? 'Y' : 'N'),
	)
));

$arResult["Group"]["IS_EXTRANET_GROUP"] = (
	Loader::includeModule("extranet")
	&& CExtranet::isExtranetSocNetGroup($arResult["Group"]["ID"])
		? "Y"
		: "N"
);

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

$arParams["PATH_TO_GROUPS_LIST"] = ComponentHelper::getWorkgroupSEFUrl();
$arParams["PATH_TO_GROUP_TAG"] = $arParams["PATH_TO_GROUPS_LIST"].(mb_strpos($arParams["PATH_TO_GROUPS_LIST"], '?') !== false ? '&' : '?')."TAG=#tag#&apply_filter=Y";

if (empty($arResult["Urls"]["GroupsList"]))
{
	$arResult["Urls"]["GroupsList"] = CComponentEngine::MakePathFromTemplate(
		$arParams["PATH_TO_GROUPS_LIST"],
		array("user_id" => $USER->getId())
	);
}

$arParams['USER_LIMIT'] = 17;

if (
	!empty($arResult['Group'])
	&& !empty($arResult['Group']['DESCRIPTION'])
)
{
	$arResult['Group']['DESCRIPTION'] = str_replace("\n", "<br />", $arResult['Group']['DESCRIPTION']);
}