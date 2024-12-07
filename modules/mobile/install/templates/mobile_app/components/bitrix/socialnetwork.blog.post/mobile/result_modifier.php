<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

use Bitrix\Main\Loader;

$arResult["is_ajax_post"] = (isset($_REQUEST["comment_post_id"]) && (int)$_REQUEST["comment_post_id"] > 0 ? "Y" : "N");
$arResult["Post"]["IS_IMPORTANT"] = false;
if (
	isset($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_IMPRTNT"])
	&& ((int)$arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_IMPRTNT"]["VALUE"] > 0)
)
{
	$arResult["Post"]["IS_IMPORTANT"] = true;
	unset($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_IMPRTNT"]);
}

$arResult['UF_FILE'] = [];
if (
	isset($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"])
	&& (!empty($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["VALUE"]))
)
{
	$arResult['UF_FILE'] = $arResult["POST_PROPERTIES"]["DATA"]['UF_BLOG_POST_FILE'];
	unset($arResult["POST_PROPERTIES"]["DATA"]['UF_BLOG_POST_FILE']);
}

$arResult["POST_PROPERTIES"]["SHOW"] = (!empty($arResult["POST_PROPERTIES"]["DATA"]) ? 'Y' : 'N');

$arResult["Post"]["SPERMX"] = $arResult["Post"]["SPERM"];
if (
	!empty($arResult["Post"])
	&& !empty($arResult["Post"]["SPERMX"])
)
{
	foreach ($arResult["Post"]["SPERMX"] as $groupKey => $group)
	{
		if (empty($group))
		{
			continue;
		}

		foreach ($group as $destKey => $destination)
		{
			if (
				empty($destination)
				|| empty($destination["NAME"])
			)
			{
				continue;
			}

			$arResult["Post"]["SPERMX"][$groupKey][$destKey]["NAME"] = htmlspecialcharsEx($destination["NAME"]);
		}
	}
}

if (is_array($arParams['TOP_RATING_DATA']))
{
	$arResult['TOP_RATING_DATA'] = $arParams['TOP_RATING_DATA'];
}
elseif (!empty($arParams["LOG_ID"]))
{
	$ratingData = \Bitrix\Socialnetwork\ComponentHelper::getLivefeedRatingData(array(
		'topCount' => 10,
		'logId' => array($arParams["LOG_ID"]),
	));

	if (
		!empty($ratingData)
		&& !empty($ratingData[$arParams["LOG_ID"]])
	)
	{
		$arResult['TOP_RATING_DATA'] = $ratingData[$arParams["LOG_ID"]];
	}
}

$arResult['MOBILE_API_VERSION'] = (
	Loader::includeModule('mobileapp')
		? CMobile::getApiVersion()
		: (int)$APPLICATION->getPageProperty('api_version')
);

if (
	isset($arResult['Post']['BACKGROUND_CODE'])
	&& $arResult['Post']['BACKGROUND_CODE'] <> ''
)
{
	$backgroundData = \Bitrix\Mobile\Livefeed\Helper::getBackgroundData();
	if (
		!isset($backgroundData['images'][$arResult['Post']['BACKGROUND_CODE']])
		|| !is_array($backgroundData)
	)
	{
		$arResult['Post']['BACKGROUND_CODE'] = '';
	}
}
else
{
	$arResult['Post']['BACKGROUND_CODE'] = '';
}
