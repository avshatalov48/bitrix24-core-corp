<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var array $arEvent */
/** @global CDatabase $DB */
/** @global CMain $APPLICATION */
/** @var boolean $isUnread */
/** @var string $ind */

$component = $this->getComponent();

if ((int)$arEvent["SOURCE_ID"] > 0)
{
	$arComponentParams = [
		"PATH_TO_BLOG" => $arParams["PATH_TO_USER_BLOG"] ?? null,
		"PATH_TO_POST" => $arParams["PATH_TO_USER_MICROBLOG_POST"] ?? null,
		"PATH_TO_BLOG_CATEGORY" => $arParams["PATH_TO_USER_BLOG_CATEGORY"] ?? null,
		"PATH_TO_CRMCONTACT" => (!empty($arParams["PATH_TO_CRMCONTACT"]) ? $arParams["PATH_TO_CRMCONTACT"] : '') ,
		"PATH_TO_POST_EDIT" => $arParams["PATH_TO_USER_BLOG_POST_EDIT"] ?? null,
		"PATH_TO_USER" => $arParams["PATH_TO_USER"],
		"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
		"PATH_TO_SMILE" => $arParams["PATH_TO_BLOG_SMILE"] ?? null,
		"PATH_TO_MESSAGES_CHAT" => $arResult["PATH_TO_MESSAGES_CHAT"] ?? null,
		"PATH_TO_LOG_ENTRY" => $arParams["PATH_TO_LOG_ENTRY"],
		"PATH_TO_LOG_ENTRY_EMPTY" => $arParams["PATH_TO_LOG_ENTRY_EMPTY"],
		"SET_NAV_CHAIN" => "N",
		"SET_TITLE" => "N",
		"POST_PROPERTY" => $arParams["POST_PROPERTY"] ?? null,
		"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
		"DATE_TIME_FORMAT_FROM_LOG" => $arParams["DATE_TIME_FORMAT"],
		"LOG_ID" => $arEvent["ID"],
		"USER_ID" => $arEvent["USER_ID"],
		"ENTITY_TYPE" => $arEvent["ENTITY_TYPE"],
		"ENTITY_ID" => $arEvent["ENTITY_ID"],
		"EVENT_ID" => $arEvent["EVENT_ID"],
		"EVENT_ID_FULLSET" => $arEvent["EVENT_ID_FULLSET"],
		"IND" => $ind,
		"SONET_GROUP_ID" => $arParams["GROUP_ID"],
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
		"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
		"SHOW_YEAR" => $arParams["SHOW_YEAR"] ?? null,
		"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"] ?? null,
		"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"] ?? null,
		"USE_SHARE" => $arParams["USE_SHARE"] ?? null,
		"SHARE_HIDE" => $arParams["SHARE_HIDE"] ?? null,
		"SHARE_TEMPLATE" => $arParams["SHARE_TEMPLATE"] ?? null,
		"SHARE_HANDLERS" => $arParams["SHARE_HANDLERS"] ?? null,
		"SHARE_SHORTEN_URL_LOGIN" => $arParams["SHARE_SHORTEN_URL_LOGIN"] ?? null,
		"SHARE_SHORTEN_URL_KEY" => $arParams["SHARE_SHORTEN_URL_KEY"] ?? null,
		"SHOW_RATING" => $arParams["SHOW_RATING"],
		"RATING_TYPE" => $arParams["RATING_TYPE"],
		"IMAGE_MAX_WIDTH" => $arParams["IMAGE_MAX_WIDTH"],
		"IMAGE_MAX_HEIGHT" => $arParams["IMAGE_MAX_HEIGHT"] ?? null,
		"ALLOW_POST_CODE" => $arParams["ALLOW_POST_CODE"] ?? null,
		"ID" => $arEvent["SOURCE_ID"],
		"FROM_LOG" => ($arParams['LOG_ID'] <= 0 ? "Y" : "N"),
		"IS_LIST" => (
			(int)($arParams["LOG_ID"]) <= 0
			|| $arParams["IS_LIST"] === 'Y'
		),
		"IS_UNREAD" => $isUnread,
		"IS_HIDDEN" => false,
		"LAST_LOG_TS" => (isset($arResult["LAST_LOG_TS"]) && $arResult["LAST_LOG_TS"] > 0 ? $arResult["LAST_LOG_TS"] + $arResult["TZ_OFFSET"] : 0),
		"CACHE_TIME" => $arParams["CACHE_TIME"] ?? null,
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"ALLOW_VIDEO"  => $arParams["BLOG_COMMENT_ALLOW_VIDEO"] ?? null,
		"ALLOW_IMAGE_UPLOAD" => $arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"] ?? null,
		"USE_CUT" => $arParams["BLOG_USE_CUT"] ?? null,
		"MOBILE" => "Y",
		"ATTACHED_IMAGE_MAX_WIDTH_FULL" => 640,
		"ATTACHED_IMAGE_MAX_HEIGHT_FULL" => 832,
		"RETURN_DATA" => ($arParams["LOG_ID"] > 0 ? "Y" : "N"),
		"AVATAR_SIZE_COMMENT" => $arParams["AVATAR_SIZE_COMMENT"],
		"AVATAR_SIZE" => $arParams["AVATAR_SIZE"],
		"CHECK_PERMISSIONS_DEST" => $arParams["CHECK_PERMISSIONS_DEST"],
		"COMMENTS_COUNT" => $arEvent["COMMENTS_COUNT"],
		"USE_FOLLOW" => $arParams["USE_FOLLOW"],
		"USE_FAVORITES" => (isset($arResult["GROUP_READ_ONLY"]) && $arResult["GROUP_READ_ONLY"] === "Y" ? "N" : "Y"),
		"GROUP_READ_ONLY" => (isset($arResult["GROUP_READ_ONLY"]) && $arResult["GROUP_READ_ONLY"] === "Y" ? "Y" : "N"),
		"TOP_RATING_DATA" => ($arResult['TOP_RATING_DATA'][$arEvent["ID"]] ?? false),
		"TARGET" => (isset($arParams["TARGET"]) && $arParams["TARGET"] <> '' ? $arParams["TARGET"] : false),
		"SITE_TEMPLATE_ID" => (isset($arParams["SITE_TEMPLATE_ID"]) && $arParams["SITE_TEMPLATE_ID"] <> '' ? $arParams["SITE_TEMPLATE_ID"] : ""),
		'UNREAD_BLOG_COMMENT_ID' => ($arResult['unreadBlogCommentId'][(int)$arEvent['SOURCE_ID']] ?? []),
	];

	if ($arParams["USE_FOLLOW"] === "Y")
	{
		$arComponentParams["FOLLOW"] = $arEvent["FOLLOW"];
		$arComponentParams["FOLLOW_DEFAULT"] = $arResult["FOLLOW_DEFAULT"];
	}

	if (
		$arEvent["RATING_TYPE_ID"] <> ''
		&& $arEvent["RATING_ENTITY_ID"] > 0
		&& $arParams["SHOW_RATING"] === "Y"
	)
	{
		$arComponentParams["RATING_ENTITY_ID"] = $arEvent["RATING_ENTITY_ID"];
	}

	if (!empty($arEvent['CONTENT_ID']))
	{
		$arComponentParams['CONTENT_ID'] = $arEvent['CONTENT_ID'];
	}

	if ($arResult['currentUserId'] > 0)
	{
		$arComponentParams["FAVORITES"] = (
			array_key_exists("FAVORITES_USER_ID", $arEvent)
			&& (int)$arEvent["FAVORITES_USER_ID"] > 0
				? "Y"
				: "N"
		);
	}

	$arComponentParams['PINNED_PANEL_DATA'] = [];
	if (array_key_exists('PINNED_PANEL_DATA', $arEvent))
	{
		$arComponentParams['PINNED_PANEL_DATA'] = $arEvent['PINNED_PANEL_DATA'];
	}

	if (isset($arEvent['PINNED_USER_ID']))
	{
		$arComponentParams['PINNED'] = ((int)$arEvent['PINNED_USER_ID'] > 0 ? 'Y' : 'N');
	}

	$arComponentParams["ATTRIBUTES"] = $arParams["ATTRIBUTES"];


	$APPLICATION->IncludeComponent(
		"bitrix:socialnetwork.blog.post",
		"mobile",
		$arComponentParams,
		$component,
		[ 'HIDE_ICONS' => 'Y' ]
	);
}
