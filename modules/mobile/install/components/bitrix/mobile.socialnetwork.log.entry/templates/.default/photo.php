<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var array $arEvent */
/** @var string $strOnClick */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

?>
<div id="post_block_check_cont_<?=$arEvent["EVENT"]["ID"]?>" class="post-item-post-<?=($arEvent["EVENT"]["EVENT_ID"] == "photo" ? "block-full" : "img-block")?> post-item-block-inner post-item-contentview" bx-content-view-xml-id="<?=(!empty($arResult["CONTENT_ID"]) ? htmlspecialcharsBx($arResult["CONTENT_ID"]) : "")?>"<?=$strOnClick?>><?

	$arPhotoItems = array();
	$photo_section_id = false;
	if ($arEvent["EVENT"]["EVENT_ID"] == "photo")
	{
		$photo_section_id = $arEvent["EVENT"]["SOURCE_ID"];
		if (strlen($arEvent["EVENT"]["PARAMS"]) > 0)
		{
			$arEventParams = unserialize(htmlspecialcharsback($arEvent["EVENT"]["PARAMS"]));
			if (
				$arEventParams
				&& isset($arEventParams["arItems"])
				&& is_array($arEventParams["arItems"])
			)
				$arPhotoItems = $arEventParams["arItems"];
		}
	}
	elseif ($arEvent["EVENT"]["EVENT_ID"] == "photo_photo")
	{
		if (intval($arEvent["EVENT"]["SOURCE_ID"]) > 0)
			$arPhotoItems = array($arEvent["EVENT"]["SOURCE_ID"]);

		if (strlen($arEvent["EVENT"]["PARAMS"]) > 0)
		{
			$arEventParams = unserialize(htmlspecialcharsback($arEvent["EVENT"]["PARAMS"]));
			if (
				$arEventParams
				&& isset($arEventParams["SECTION_ID"])
				&& intval($arEventParams["SECTION_ID"]) > 0
			)
				$photo_section_id = $arEventParams["SECTION_ID"];
		}
	}

	if (strlen($arEvent["EVENT"]["PARAMS"]) > 0)
	{
		$arEventParams = unserialize(htmlspecialcharsback($arEvent["EVENT"]["PARAMS"]));

		$photo_iblock_type = $arEventParams["IBLOCK_TYPE"];
		$photo_iblock_id = $arEventParams["IBLOCK_ID"];
		$alias = (isset($arEventParams["ALIAS"]) ? $arEventParams["ALIAS"] : false);
		$photo_detail_url = false;

		if ($arEvent["EVENT"]["EVENT_ID"] == "photo")
		{
			$photo_detail_url = $arEventParams["DETAIL_URL"];
			if (
				$photo_detail_url
				&& IsModuleInstalled("extranet")
				&& $arEvent["EVENT"]["ENTITY_TYPE"] == SONET_ENTITY_GROUP
			)
			{
				$photo_detail_url = str_replace("#GROUPS_PATH#", $arResult["WORKGROUPS_PAGE"], $photo_detail_url);
			}
		}
		elseif ($arEvent["EVENT"]["EVENT_ID"] == "photo_photo")
		{
			$photo_detail_url = $arEvent["EVENT"]["URL"];
		}

		if (!$photo_detail_url)
		{
			$photo_detail_url = $arParams["PATH_TO_".($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO_ELEMENT"];
		}

		if (
			strlen($photo_iblock_type) > 0
			&& intval($photo_iblock_id) > 0
			&& intval($photo_section_id) > 0
			&& count($arPhotoItems) > 0
		)
		{
			?><?$APPLICATION->IncludeComponent(
				"bitrix:photogallery.detail.list.ex",
				"mobile",
				Array(
					"IBLOCK_TYPE" => $photo_iblock_type,
					"IBLOCK_ID" => $photo_iblock_id,
					"SHOWN_PHOTOS" => (count($arPhotoItems) > $arParams["PHOTO_COUNT"]
						? array_slice(
							$arPhotoItems,
							-($arParams["PHOTO_COUNT"]),
							$arParams["PHOTO_COUNT"]
						)
						: $arPhotoItems
					),
					"DRAG_SORT" => "N",
					"MORE_PHOTO_NAV" => "N",
					"LIVEFEED_EVENT_ID" => $arEvent["EVENT"]["EVENT_ID"],
					"LIVEFEED_ID" => $arEvent["EVENT"]["ID"],
					"THUMBNAIL_SIZE" => ($arEvent["EVENT"]["EVENT_ID"] == "photo" ? $arParams["PHOTO_THUMBNAIL_SIZE"] : 568),
					"THUMBNAIL_RESIZE_METHOD" => ($arEvent["EVENT"]["EVENT_ID"] == "photo" ? "EXACT" : false),
					"SHOW_CONTROLS" => "N",
					"USE_RATING" => ($arParams["PHOTO_USE_RATING"] == "Y" || $arParams["SHOW_RATING"] == "Y" ? "Y" : "N"),
					"SHOW_RATING" => $arParams["SHOW_RATING"],
					"SHOW_SHOWS" => "N",
					"SHOW_COMMENTS" => "Y",
					"MAX_VOTE" => $arParams["PHOTO_MAX_VOTE"],
					"VOTE_NAMES" => isset($arParams["PHOTO_VOTE_NAMES"])? $arParams["PHOTO_VOTE_NAMES"]: Array(),
					"DISPLAY_AS_RATING" => $arParams["SHOW_RATING"] == "Y"? "rating_main": isset($arParams["PHOTO_DISPLAY_AS_RATING"])? $arParams["PHOTO_DISPLAY_AS_RATING"]: "rating",
					"RATING_MAIN_TYPE" => $arParams["SHOW_RATING"] == "Y"? $arParams["RATING_TYPE"]: "",

					"BEHAVIOUR" => "SIMPLE",
					"SET_TITLE" => "N",
					"CACHE_TYPE" => "A",
					"CACHE_TIME" => $arParams["CACHE_TIME"],
					"CACHE_NOTES" => "",
					"SECTION_ID" => $photo_section_id,
					"ELEMENT_LAST_TYPE"	=> "none",
					"ELEMENT_SORT_FIELD" => "ID",
					"ELEMENT_SORT_ORDER" => "asc",
					"ELEMENT_SORT_FIELD1" => "",
					"ELEMENT_SORT_ORDER1" => "asc",
					"PROPERTY_CODE" => array(),

					"INDEX_URL" => CComponentEngine::MakePathFromTemplate(
						$arParams["PATH_TO_".($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO"],
						array(
							"user_id" => $arEvent["EVENT"]["ENTITY_ID"],
							"group_id" => $arEvent["EVENT"]["ENTITY_ID"]
						)
					),
					"DETAIL_URL" => CComponentEngine::MakePathFromTemplate(
						$photo_detail_url,
						array(
							"user_id" => $arEvent["EVENT"]["ENTITY_ID"],
							"group_id" => $arEvent["EVENT"]["ENTITY_ID"],
						)
					),
					"GALLERY_URL" => "",
					"SECTION_URL" => CComponentEngine::MakePathFromTemplate(
						$arParams["PATH_TO_".($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO_SECTION"],
						array(
							"user_id" => $arEvent["EVENT"]["ENTITY_ID"],
							"group_id" => $arEvent["EVENT"]["ENTITY_ID"],
							"section_id" => ($arEvent["EVENT"]["EVENT_ID"] == "photo_photo" ? $photo_section_id : $arEvent["EVENT"]["SOURCE_ID"])
						)
					),
					"PATH_TO_USER" => "",
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
					"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],

					"USE_PERMISSIONS" => "N",
					"GROUP_PERMISSIONS" => array(),
					"PAGE_ELEMENTS" => $arParams["PHOTO_COUNT"],
					"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT_DETAIL"],
					"SET_STATUS_404" => "N",
					"ADDITIONAL_SIGHTS" => array(),
					"PICTURES_SIGHT" => "real",
					"USE_COMMENTS" => $arParams["PHOTO_USE_COMMENTS"],
					"COMMENTS_TYPE" => ($arParams["PHOTO_COMMENTS_TYPE"] == "blog" ? "blog" : "forum"),
					"FORUM_ID" => $arParams["PHOTO_FORUM_ID"],
					"BLOG_URL" => $arParams["PHOTO_BLOG_URL"],
					"USE_CAPTCHA" => $arParams["PHOTO_USE_CAPTCHA"],
					"SHOW_LINK_TO_FORUM" => "N",
					"IS_SOCNET" => "Y",
					"USER_ALIAS"	=> ($alias ? $alias : ($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "group" : "user"."_".$arEvent["EVENT"]["ENTITY_ID"])),
					//these two params below used to set action url and unique id - for any ajax actions
					"~UNIQUE_COMPONENT_ID" => 'bxfg_ucid_from_req_'.$photo_iblock_id.'_'.($arEvent["EVENT"]["EVENT_ID"] == "photo_photo" ? $photo_section_id : $arEvent["EVENT"]["SOURCE_ID"])."_".$arEvent["EVENT"]["ID"],
					"ACTION_URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_".($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO_SECTION"], array("user_id" => $arEvent["EVENT"]["ENTITY_ID"],"group_id" => $arEvent["EVENT"]["ENTITY_ID"],"section_id" => ($arEvent["EVENT"]["EVENT_ID"] == "photo_photo" ? $photo_section_id : $arEvent["EVENT"]["SOURCE_ID"]))),
				),
				$component,
				array(
					"HIDE_ICONS" => "Y"
				)
			);?><?
		}
	}

?></div>