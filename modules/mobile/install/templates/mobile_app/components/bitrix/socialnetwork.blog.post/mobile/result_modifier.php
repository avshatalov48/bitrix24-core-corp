<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Loader;

$arResult["is_ajax_post"] = (intval($_REQUEST["comment_post_id"]) > 0 ? "Y" : "N");
$arResult["Post"]["IS_IMPORTANT"] = false;
if (
	isset($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_IMPRTNT"])
	&& (intval($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_IMPRTNT"]["VALUE"]) > 0)
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
	foreach($arResult["Post"]["SPERMX"] as $groupKey => $group)
	{
		if (!empty($group))
		{
			foreach($group as $destKey => $destination)
			{
				if (
					!empty($destination)
					&& !empty($destination["NAME"])
				)
				{
					$arResult["Post"]["SPERMX"][$groupKey][$destKey]["NAME"] = htmlspecialcharsEx($destination["NAME"]);
				}
			}
		}
	}
}

if (!empty($arParams['TOP_RATING_DATA']))
{
	$arResult['TOP_RATING_DATA'] = $arParams['TOP_RATING_DATA'];
}
elseif (!empty($arParams["LOG_ID"]))
{
	$ratingData = \Bitrix\Socialnetwork\ComponentHelper::getLivefeedRatingData(array(
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
		? \CMobile::getApiVersion()
		: intval($APPLICATION->getPageProperty('api_version'))
);

?>