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
use Bitrix\Mobile\Livefeed\Helper;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/mobile.socialnetwork.log.entry/include.php");

$arEvent = $arResult['Event'];

if (
	!empty($arEvent['EVENT_FORMATTED'])
	&& !empty($arEvent['EVENT_FORMATTED']['UF'])
	&& is_array($arEvent['EVENT_FORMATTED']['UF'])
	&& isset($arEvent['EVENT_FORMATTED']['UF']['UF_SONET_LOG_DOC'])
	&& (!empty($arEvent['EVENT_FORMATTED']['UF']['UF_SONET_LOG_DOC']['VALUE']))

)
{
	$arResult['UF_FILE'] = $arEvent['EVENT_FORMATTED']['UF']['UF_SONET_LOG_DOC'];
	unset($arResult['Event']['EVENT_FORMATTED']['UF']['UF_SONET_LOG_DOC']);
}


	$arResult["RECORDS"] = array();
	$arResult["LAST_COMMENT_TS"] = 0;

	if (is_array($arEvent["COMMENTS"]))
	{
		$commentData = [];
		$commentInlineDiskData = [];
		$inlineDiskObjectIdList = [];
		$inlineDiskAttachedObjectIdList = [];

		foreach ($arEvent["COMMENTS"] as $comment)
		{
			$commentId = (
				isset($comment["EVENT"]["SOURCE_ID"])
				&& (int)$comment["EVENT"]["SOURCE_ID"] > 0
					? (int)$comment["EVENT"]["SOURCE_ID"]
					: $comment["EVENT"]["ID"]
			);

			if ($ufData = Helper::getDiskDataByCommentText($comment['EVENT']['MESSAGE']))
			{
				$commentInlineDiskData[$commentId] = $ufData;
				$inlineDiskObjectIdList = array_merge($inlineDiskObjectIdList, $ufData['OBJECT_ID']);
				$inlineDiskAttachedObjectIdList = array_merge($inlineDiskAttachedObjectIdList, $ufData['ATTACHED_OBJECT_ID']);
			}

			$commentData[$comment['EVENT']['ID']] = $comment;
		}

		$inlineDiskAttachedObjectIdImageList = [];
		$entityAttachedObjectIdList = [];
		if ($ufData = Helper::getDiskUFDataForComments($inlineDiskObjectIdList, $inlineDiskAttachedObjectIdList))
		{
			$inlineDiskAttachedObjectIdImageList = $ufData['ATTACHED_OBJECT_DATA'];
			$entityAttachedObjectIdList = $ufData['ENTITIES_DATA'];
		}

		foreach ($commentData as $comment)
		{
			$commentId = (
				isset($comment["EVENT"]["SOURCE_ID"])
				&& (int)$comment["EVENT"]["SOURCE_ID"] > 0
					? (int)$comment["EVENT"]["SOURCE_ID"]
					: $comment["EVENT"]["ID"]
			);
			$arResult["LAST_COMMENT_TS"] = ($arResult["LAST_COMMENT_TS"] ?: $comment["LOG_DATE_TS"]);

			$textFormatted = ($comment['EVENT_FORMATTED']['MESSAGE'] ?? $comment['EVENT']['MESSAGE']);
			$textFormatted = CSocNetTextParser::closetags(htmlspecialcharsback($textFormatted));

			$arResult["RECORDS"][$commentId] = array(
				"ID" => $commentId,
				"NEW" => (
				($arResult["COUNTER_TYPE"] === "**")
				&& (int)$comment["EVENT"]["USER_ID"] !== (int)$USER->GetID()
				&& (int)$arResult["LAST_LOG_TS"] > 0
				&& (MakeTimeStamp($comment["EVENT"]["LOG_DATE"]) - (int)$arResult["TZ_OFFSET"]) > $arResult["LAST_LOG_TS"]
						? "Y"
						: "N"
				),
				"APPROVED" => "Y",
				"POST_TIMESTAMP" => $comment["LOG_DATE_TS"],
				"AUTHOR" => array(
					"ID" => $comment["EVENT"]["USER_ID"],
					"NAME" => $comment["EVENT"]["~CREATED_BY_NAME"],
					"LAST_NAME" => $comment["EVENT"]["~CREATED_BY_LAST_NAME"],
					"SECOND_NAME" => $comment["EVENT"]["~CREATED_BY_SECOND_NAME"],
					"PERSONAL_GENDER" => $comment["EVENT"]["~CREATED_BY_PERSONAL_GENDER"],
					"AVATAR" => $comment["AVATAR_SRC"]
				),
				"FILES" => $comment["EVENT_FORMATTED"]["FILES"],
				"UF" => $comment["UF"],
				"~POST_MESSAGE_TEXT" => $comment["EVENT"]["MESSAGE"],
				"POST_MESSAGE_TEXT" => $textFormatted,
				"RATING_VOTE_ID" => false,
				"AUX" => ($comment["AUX"] ?? ''),
				"ORIGINAL_COMMENT_ID" => $comment["EVENT"]["ID"],
				"CAN_DELETE" => ($comment["CAN_DELETE"] ?? true)
			);
		}

		if ($arParams["IS_LIST"])
		{
			$arResult["RECORDS"] = array_filter($arResult["RECORDS"], static function ($value) { return (
				isset($value['NEW'])
				&& $value['NEW'] === 'Y'
			); });

			if (!empty($arResult["RECORDS"]))
			{
				$arResult["RECORDS"] = array_slice($arResult["RECORDS"], 0, 3, true);
			}
		}

		foreach($arResult["RECORDS"] as $commentId => $record)
		{
			$originalComment = $commentData[$record['ORIGINAL_COMMENT_ID']];

			if (
				$originalComment["EVENT"]["RATING_TYPE_ID"] <> ''
				&& $originalComment["EVENT"]["RATING_ENTITY_ID"] > 0
				&& $arParams["SHOW_RATING"] === "Y"
			)
			{
				$voteId = $originalComment["EVENT"]["RATING_TYPE_ID"] . '_' . $originalComment["EVENT"]["RATING_ENTITY_ID"] . '-' . (time() + random_int(0, 1000));

				$arResult["RECORDS"][$commentId]["RATING_VOTE_ID"] = $voteId;
				$arResult["RECORDS"][$commentId]["RATING_USER_HAS_VOTED"] = $arResult["RATING_COMMENTS"][$originalComment["EVENT"]["RATING_ENTITY_ID"]]["USER_HAS_VOTED"];
				$arResult["RECORDS"][$commentId]["RATING_USER_REACTION"] = $arResult["RATING_COMMENTS"][$originalComment["EVENT"]["RATING_ENTITY_ID"]]["USER_REACTION"];
			}

			if (
				!empty($inlineDiskAttachedObjectIdImageList)
				&& isset($commentInlineDiskData[$commentId])
			)
			{
				$inlineAttachedImagesId = Helper::getCommentInlineAttachedImagesId([
					'commentId' => $commentId,
					'inlineDiskAttachedObjectIdImageList' => $inlineDiskAttachedObjectIdImageList,
					'commentInlineDiskData' => $commentInlineDiskData[$commentId],
					'entityAttachedObjectIdList' => $entityAttachedObjectIdList[$commentId],
				]);

				if (
					!empty($arResult["RECORDS"][$commentId]["UF"])
					&& !empty($arResult["RECORDS"][$commentId]["UF"]["UF_SONET_COM_DOC"])
					&& !empty($arResult["RECORDS"][$commentId]["UF"]["UF_SONET_COM_DOC"]['VALUE'])
				)
				{
					$arResult["RECORDS"][$commentId]["UF"]["UF_SONET_COM_DOC"]['VALUE_INLINE'] = $inlineAttachedImagesId;
				}
			}
		}
	}


if (is_array($arParams['TOP_RATING_DATA']))
{
	$arResult['TOP_RATING_DATA'] = $arParams['TOP_RATING_DATA'];
}
elseif (!empty($arResult["Event"]["EVENT"]["ID"]))
{
	$ratingData = \Bitrix\Socialnetwork\ComponentHelper::getLivefeedRatingData(array(
		'topCount' => 10,
		'logId' => array($arResult["Event"]["EVENT"]["ID"]),
	));

	if (
		!empty($ratingData)
		&& !empty($ratingData[$arResult["Event"]["EVENT"]["ID"]])
	)
	{
		$arResult['TOP_RATING_DATA'] = $ratingData[$arResult["Event"]["EVENT"]["ID"]];
	}
}

$arResult['MOBILE_API_VERSION'] = (
	Loader::includeModule('mobileapp')
	? CMobile::getApiVersion()
	: (int)$APPLICATION->getPageProperty('api_version')
);
