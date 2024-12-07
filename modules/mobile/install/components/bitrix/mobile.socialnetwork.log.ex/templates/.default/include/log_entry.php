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
/** @var boolean $isUnread */
/** @var array $arEvent */
/** @var string $ind */

$component = $this->getComponent();

$arComponentParams = array_merge($arParams, [
	"LOG_ID" => $arEvent["ID"],
	"IS_LIST" => (
		(int)$arParams["LOG_ID"] <= 0
		|| $arParams["IS_LIST"] === 'Y'
	),
	"LAST_LOG_TS" => $arResult["LAST_LOG_TS"] ?? null,
	"COUNTER_TYPE" => $arResult["COUNTER_TYPE"],
	"AJAX_CALL" => $arResult["AJAX_CALL"],
	"PATH_TO_LOG_ENTRY_EMPTY" => $arParams["PATH_TO_LOG_ENTRY_EMPTY"],
	"bReload" => $arResult["bReload"] ?? null,
	"IND" => $ind,
	"EVENT" => [
		"IS_UNREAD" => $isUnread,
		"LOG_DATE" => $arEvent["LOG_DATE"],
		"COMMENTS_COUNT" => $arEvent["COMMENTS_COUNT"],
	],
	"TOP_RATING_DATA" => ($arResult['TOP_RATING_DATA'][$arEvent["ID"]] ?? false),
	"TARGET" => (isset($arParams["TARGET"]) && $arParams["TARGET"] <> '' ? $arParams["TARGET"] : false),
	'UNREAD_LOG_COMMENT_ID' => ($arResult['unreadLogCommentId'][(int)$arEvent['ID']] ?? []),
]);

if ($arResult['currentUserId'] > 0)
{
	if ($arParams["USE_FOLLOW"] === "Y")
	{
		$arComponentParams["EVENT"]["FOLLOW"] = $arEvent["FOLLOW"];
		$arComponentParams["EVENT"]["DATE_FOLLOW"] = $arEvent["DATE_FOLLOW"];
		$arComponentParams["FOLLOW_DEFAULT"] = $arResult["FOLLOW_DEFAULT"];
	}

	$arComponentParams["EVENT"]["FAVORITES"] = (
		array_key_exists("FAVORITES_USER_ID", $arEvent)
		&& (int)$arEvent["FAVORITES_USER_ID"] > 0
			? "Y"
			: "N"
	);
}

if (
	$arEvent["RATING_TYPE_ID"] <> ''
	&& $arEvent["RATING_ENTITY_ID"] > 0
	&& $arParams["SHOW_RATING"] === "Y"
)
{
	$arComponentParams["RATING_TYPE"] = $arParams["RATING_TYPE"];
	$arComponentParams["EVENT"]["RATING_TYPE_ID"] = $arEvent["RATING_TYPE_ID"];
	$arComponentParams["EVENT"]["RATING_ENTITY_ID"] = $arEvent["RATING_ENTITY_ID"];
}

if (!empty($arEvent['CONTENT_ID']))
{
	$arComponentParams['CONTENT_ID'] = $arEvent['CONTENT_ID'];
}

if (isset($arResult["CRM_ACTIVITY2TASK"]))
{
	$arComponentParams["CRM_ACTIVITY2TASK"] = $arResult["CRM_ACTIVITY2TASK"];
}

$arComponentParams['PINNED_PANEL_DATA'] = (
	array_key_exists('PINNED_PANEL_DATA', $arEvent)
		? $arEvent['PINNED_PANEL_DATA']
		: []
);

$arComponentParams['EVENT']['PINNED'] = (
	array_key_exists('PINNED_USER_ID', $arEvent)
	&& (int)$arEvent['PINNED_USER_ID'] > 0
		? 'Y'
		: 'N'
);


$APPLICATION->IncludeComponent(
	"bitrix:mobile.socialnetwork.log.entry",
	"",
	$arComponentParams,
	$component,
	[ 'HIDE_ICONS' => 'Y' ]
);
