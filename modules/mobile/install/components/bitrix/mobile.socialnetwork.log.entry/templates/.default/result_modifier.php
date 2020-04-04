<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/mobile.socialnetwork.log.entry/include.php");

if (!$arParams["IS_LIST"])
{
	$arResult["RECORDS"] = array();
	$arResult["LAST_COMMENT_TS"] = 0;

	$arEvent = $arResult["Event"];

	if (is_array($arEvent["COMMENTS"]))
	{
		$commentData = $commentInlineDiskData = $inlineDiskObjectIdList = $inlineDiskAttachedObjectIdList = array();

		foreach($arEvent["COMMENTS"] as $comment)
		{
			$commentId = (
				isset($comment["EVENT"]["SOURCE_ID"])
				&& intval($comment["EVENT"]["SOURCE_ID"]) > 0
					? intval($comment["EVENT"]["SOURCE_ID"])
					: $comment["EVENT"]["ID"]
			);

			if ($ufData = MSLEUFProcessor::getDataByText($comment['EVENT']['MESSAGE']))
			{
				$commentInlineDiskData[$commentId] = $ufData;
				$inlineDiskObjectIdList = array_merge($inlineDiskObjectIdList, $ufData['OBJECT_ID']);
				$inlineDiskAttachedObjectIdList = array_merge($inlineDiskAttachedObjectIdList, $ufData['ATTACHED_OBJECT_ID']);
			}

			$commentData[] = $comment;
		}

		$inlineDiskAttachedObjectIdImageList = $entityAttachedObjectIdList = array();
		if ($ufData = \MSLEUFProcessor::getUFData($inlineDiskObjectIdList, $inlineDiskAttachedObjectIdList))
		{
			$inlineDiskAttachedObjectIdImageList = $ufData['ATTACHED_OBJECT_DATA'];
			$entityAttachedObjectIdList = $ufData['ENTITIES_DATA'];
		}

		foreach($commentData as $comment)
		{
			$commentId = (
				isset($comment["EVENT"]["SOURCE_ID"])
				&& intval($comment["EVENT"]["SOURCE_ID"]) > 0
					? intval($comment["EVENT"]["SOURCE_ID"])
					: $comment["EVENT"]["ID"]
			);
			$arResult["LAST_COMMENT_TS"] = ($arResult["LAST_COMMENT_TS"] ?: $comment["LOG_DATE_TS"]);

			$arResult["RECORDS"][$commentId] = array(
				"ID" => $commentId,
				"NEW" => (
				($arResult["COUNTER_TYPE"] == "**")
				&& $comment["EVENT"]["USER_ID"] != $USER->GetID()
				&& intval($arResult["LAST_LOG_TS"]) > 0
				&& (MakeTimeStamp($comment["EVENT"]["LOG_DATE"]) - intval($arResult["TZ_OFFSET"])) > $arResult["LAST_LOG_TS"]
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
				"POST_MESSAGE_TEXT" => CSocNetTextParser::closetags(htmlspecialcharsback((array_key_exists("EVENT_FORMATTED", $comment) && array_key_exists("MESSAGE", $comment["EVENT_FORMATTED"]) ? $comment["EVENT_FORMATTED"]["MESSAGE"] : $comment["EVENT"]["MESSAGE"]))),
				"RATING_VOTE_ID" => false
			);

			if (
				strlen($comment["EVENT"]["RATING_TYPE_ID"]) > 0
				&& $comment["EVENT"]["RATING_ENTITY_ID"] > 0
				&& $arParams["SHOW_RATING"] == "Y"
			)
			{
				$voteId = $comment["EVENT"]["RATING_TYPE_ID"].'_'.$comment["EVENT"]["RATING_ENTITY_ID"].'-'.(time()+rand(0, 1000));

				$arResult["RECORDS"][$commentId]["RATING_VOTE_ID"] = $voteId;
				$arResult["RECORDS"][$commentId]["RATING_USER_HAS_VOTED"] = $arResult["RATING_COMMENTS"][$comment["EVENT"]["RATING_ENTITY_ID"]]["USER_HAS_VOTED"];
				$arResult["RECORDS"][$commentId]["RATING_USER_REACTION"] = $arResult["RATING_COMMENTS"][$comment["EVENT"]["RATING_ENTITY_ID"]]["USER_REACTION"];
			}

			// find all inline images and remove them from UF
			if (
				!empty($inlineDiskAttachedObjectIdImageList)
				&& isset($commentInlineDiskData[$commentId])
			)
			{
				$inlineAttachedImagesId = array();
				if (!empty($commentInlineDiskData[$commentId]['OBJECT_ID']))
				{
					foreach($commentInlineDiskData[$commentId]['OBJECT_ID'] as $val)
					{
						$inlineAttachedImagesId = array_merge($inlineAttachedImagesId, array_keys($inlineDiskAttachedObjectIdImageList, $val));
					}
				}
				if (!empty($commentInlineDiskData[$commentId]['ATTACHED_OBJECT_ID']))
				{
					$inlineAttachedImagesId = array_merge($inlineAttachedImagesId, array_intersect($commentInlineDiskData[$commentId]['ATTACHED_OBJECT_ID'], array_keys($inlineDiskAttachedObjectIdImageList)));
				}

				if (is_array($entityAttachedObjectIdList[$commentId]))
				{
					$inlineAttachedImagesId = array_intersect($inlineAttachedImagesId, $entityAttachedObjectIdList[$commentId]);
				}

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
}

if (!empty($arParams['TOP_RATING_DATA']))
{
	$arResult['TOP_RATING_DATA'] = $arParams['TOP_RATING_DATA'];
}
elseif (!empty($arResult["Event"]["EVENT"]["ID"]))
{
	$ratingData = \Bitrix\Socialnetwork\ComponentHelper::getLivefeedRatingData(array(
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