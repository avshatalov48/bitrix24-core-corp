<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

__IncludeLang(dirname(__FILE__)."/lang/".LANGUAGE_ID."/include.php");

$GLOBALS["CurUserCanAddComments"] = array();

if (!function_exists('__SLMGetVisible'))
{
	function __SLMGetVisible($arFields, $arCurrentUserSubscribe, $arMyEntities = array())
	{
		$bHasLogEventCreatedBy = CSocNetLogTools::HasLogEventCreatedBy($arFields["EVENT_ID"]);

		if (array_key_exists($arFields["ENTITY_TYPE"]."_".$arFields["ENTITY_ID"]."_".$arFields["EVENT_ID"]."_N_N", $arCurrentUserSubscribe["VISIBLE"]))
			$strVisible = $arCurrentUserSubscribe["VISIBLE"][$arFields["ENTITY_TYPE"]."_".$arFields["ENTITY_ID"]."_".$arFields["EVENT_ID"]."_N_N"];
		elseif ($bHasLogEventCreatedBy && array_key_exists("U_".$arFields["USER_ID"]."_".$arFields["EVENT_ID"]."_N_Y", $arCurrentUserSubscribe["VISIBLE"]))
			$strVisible = $arCurrentUserSubscribe["VISIBLE"]["U_".$arFields["USER_ID"]."_".$arFields["EVENT_ID"]."_N_Y"];
		elseif (array_key_exists($arFields["ENTITY_TYPE"]."_".$arFields["ENTITY_ID"]."_all_N_N", $arCurrentUserSubscribe["VISIBLE"]))
			$strVisible = $arCurrentUserSubscribe["VISIBLE"][$arFields["ENTITY_TYPE"]."_".$arFields["ENTITY_ID"]."_all_N_N"];
		elseif ($bHasLogEventCreatedBy && array_key_exists("U_".$arFields["USER_ID"]."_all_N_Y", $arCurrentUserSubscribe["VISIBLE"]))
			$strVisible = $arCurrentUserSubscribe["VISIBLE"]["U_".$arFields["USER_ID"]."_all_N_Y"];
		elseif
		(
			array_key_exists($arFields["ENTITY_TYPE"], $arMyEntities)
			&& in_array($arFields["ENTITY_ID"], $arMyEntities[$arFields["ENTITY_TYPE"]])
			&& array_key_exists($arFields["ENTITY_TYPE"]."_0_".$arFields["EVENT_ID"]."_Y_N", $arCurrentUserSubscribe["VISIBLE"])
		)
			$strVisible = $arCurrentUserSubscribe["VISIBLE"][$arFields["ENTITY_TYPE"]."_0_".$arFields["EVENT_ID"]."_Y_N"];
		elseif
		(
			array_key_exists($arFields["ENTITY_TYPE"], $arMyEntities)
			&& in_array($arFields["ENTITY_ID"], $arMyEntities[$arFields["ENTITY_TYPE"]])
			&& array_key_exists($arFields["ENTITY_TYPE"]."_0_all_Y_N", $arCurrentUserSubscribe["VISIBLE"])
		)
			$strVisible = $arCurrentUserSubscribe["VISIBLE"][$arFields["ENTITY_TYPE"]."_0_all_Y_N"];
		elseif (array_key_exists($arFields["ENTITY_TYPE"]."_0_".$arFields["EVENT_ID"]."_N_N", $arCurrentUserSubscribe["VISIBLE"]))
			$strVisible = $arCurrentUserSubscribe["VISIBLE"][$arFields["ENTITY_TYPE"]."_0_".$arFields["EVENT_ID"]."_N_N"];
		elseif (array_key_exists($arFields["ENTITY_TYPE"]."_0_all_N_N", $arCurrentUserSubscribe["VISIBLE"]))
			$strVisible = $arCurrentUserSubscribe["VISIBLE"][$arFields["ENTITY_TYPE"]."_0_all_N_N"];
		else
			$strVisible = "Y";

		return $strVisible;
	}
}

if (!function_exists('__SLMGetLogRecord'))
{
	function __SLMGetLogRecord($arEvents, $arParams, $arCurrentUserSubscribe, $arMyEntities, &$arTmpEventsNew)
	{
		static $arSiteWorkgroupsPage;

		$arParams["MOBILE"] = "Y";
		$arParams["NEW_TEMPLATE"] = "Y";

		if ($arTmpEvents == false)
			$arTmpEvents = array();

		$arEventTmp = CSocNetLogTools::FindLogEventByID($arEvents["EVENT_ID"]);
		if (
			$arEventTmp
			&& array_key_exists("CLASS_FORMAT", $arEventTmp)
			&& array_key_exists("METHOD_FORMAT", $arEventTmp)
		)
			$arEvents["FIELDS_FORMATTED"] = call_user_func(array($arEventTmp["CLASS_FORMAT"], $arEventTmp["METHOD_FORMAT"]), $arEvents, $arParams);

		$path2Entity = ($arEvents["ENTITY_TYPE"] == SONET_ENTITY_USER ? CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arEvents["ENTITY_ID"])) : "");

		$timestamp = MakeTimeStamp(
			is_array($arEvents["FIELDS_FORMATTED"])
			&& array_key_exists("EVENT_FORMATTED", $arEvents["FIELDS_FORMATTED"])
			&& is_array($arEvents["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
			&& array_key_exists("LOG_DATE_FORMAT", $arEvents["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
				? $arEvents["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["LOG_DATE_FORMAT"]
				: (
					array_key_exists("LOG_DATE_FORMAT", $arEvents)
					? $arEvents["LOG_DATE_FORMAT"]
					: $arEvents["LOG_DATE"]
				)
		);

		$timeFormated = FormatDate(GetMessage("SONET_SLM_FORMAT_TIME"), $timestamp);

		if (strlen($arParams["DATE_TIME_FORMAT"]) <= 0)
			$dateTimeFormated = __SMLFormatDate($timestamp);
		else
			$dateTimeFormated = FormatDate(
				($arParams["DATE_TIME_FORMAT"] == "FULL" ? $GLOBALS["DB"]->DateFormatToPHP(str_replace(":SS", "", FORMAT_DATETIME)) : $arParams["DATE_TIME_FORMAT"]),
				$timestamp
			);

		if (strcasecmp(LANGUAGE_ID, 'EN') !== 0 && strcasecmp(LANGUAGE_ID, 'DE') !== 0)
		{
			$dateTimeFormated = ToLower($dateTimeFormated);
			$dateFormated = ToLower($dateFormated);
		}
		// strip current year
		if (!empty($arParams['DATE_TIME_FORMAT']) && ($arParams['DATE_TIME_FORMAT'] == 'j F Y G:i' || $arParams['DATE_TIME_FORMAT'] == 'j F Y g:i a'))
		{
			$dateTimeFormated = ltrim($dateTimeFormated, '0');
			$curYear = date('Y');
			$dateTimeFormated = str_replace(array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), '', $dateTimeFormated);
		}

		$arTmpUser = array(
			"NAME" => $arEvents["~USER_NAME"],
			"LAST_NAME" => $arEvents["~USER_LAST_NAME"],
			"SECOND_NAME" => $arEvents["~USER_SECOND_NAME"],
			"LOGIN" => $arEvents["~USER_LOGIN"]
		);

		$arEvents["FIELDS_FORMATTED"]["LOG_TIME_FORMAT"] = $timeFormated;
		$arEvents["FIELDS_FORMATTED"]["LOG_UPDATE_TS"] = MakeTimeStamp($arEvents["LOG_UPDATE"]);

		$arEvents["FIELDS_FORMATTED"]["LOG_DATE_TS"] = MakeTimeStamp($arEvents["LOG_DATE"]);
		$arEvents["FIELDS_FORMATTED"]["LOG_DATE_DAY"] = ConvertTimeStamp(MakeTimeStamp($arEvents["LOG_DATE"]), "SHORT");
		$arEvents["FIELDS_FORMATTED"]["LOG_UPDATE_DAY"] = ConvertTimeStamp(MakeTimeStamp($arEvents["LOG_UPDATE"]), "SHORT");
		$arEvents["FIELDS_FORMATTED"]["COMMENTS_COUNT"] = $arEvents["COMMENTS_COUNT"];
		$arEvents["FIELDS_FORMATTED"]["TMP_ID"] = $arEvents["TMP_ID"];

		if (strlen($arParams["DATE_TIME_FORMAT"]) <= 0) // list
		{
			if (
				array_key_exists("EVENT_FORMATTED", $arEvents["FIELDS_FORMATTED"])
				&& is_array($arEvents["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
				&& array_key_exists("LOG_DATE_FORMAT", $arEvents["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
			)
			{
				$bToday = (ConvertTimeStamp(MakeTimeStamp($arEvents["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["LOG_DATE_FORMAT"]), "SHORT") == ConvertTimeStamp());
				if (
					$bToday
					|| (intval((time() - $timestamp) / 60 / 60) < 24) // last 24h
				)
					$arEvents["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = $timeFormated;
				else
					$arEvents["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = $dateTimeFormated;
			}
			else
			{
				$bToday = ($arEvents["FIELDS_FORMATTED"]["LOG_DATE_DAY"] == ConvertTimeStamp());
				if (
					$bToday
					|| (intval((time() - $timestamp) / 60 / 60) < 24) // last 24h
				)
					$arEvents["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = $timeFormated;
				else
					$arEvents["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = $dateTimeFormated;
			}
		}
		else // detail
		{
			$arFormat = Array(
				"tommorow" => "tommorow, ".GetMessage("SONET_SLM_FORMAT_TIME"),
				"today" => "today, ".GetMessage("SONET_SLM_FORMAT_TIME"),
				"yesterday" => "yesterday, ".GetMessage("SONET_SLM_FORMAT_TIME"),
				"" => (date("Y", $timestamp) == date("Y") ? GetMessage("SONET_SLM_FORMAT_DATE") : GetMessage("SONET_SLM_FORMAT_DATE_YEAR"))
			);
			$arEvents["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = FormatDate($arFormat, $timestamp);
		}

		if (is_array($arCurrentUserSubscribe))
			$arEvents["FIELDS_FORMATTED"]["VISIBLE"] = __SLMGetVisible($arEvents, $arCurrentUserSubscribe, $arMyEntities);

		$arCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($arEvents["EVENT_ID"]);
		if (
			!array_key_exists("HAS_COMMENTS", $arEvents["FIELDS_FORMATTED"])
			|| $arEvents["FIELDS_FORMATTED"]["HAS_COMMENTS"] != "N"
		)
			$arEvents["FIELDS_FORMATTED"]["HAS_COMMENTS"] = (
				$arCommentEvent
				&& (
					$arCommentEvent["EVENT_ID"] == "blog_comment_micro"
					|| !array_key_exists("ENABLE_COMMENTS", $arEvents)
					|| $arEvents["ENABLE_COMMENTS"] != "N"
				)
					? "Y"
					: "N"
			);

		if (intval($arParams["LOG_ID"]) > 0)
		{
			if (
				array_key_exists("OPERATION_ADD", $arCommentEvent) 
				&& $arCommentEvent["OPERATION_ADD"] == "log_rights"
			)
				$arEvents["FIELDS_FORMATTED"]["CAN_ADD_COMMENTS"] = CSocNetLogRights::CheckForUser($arEvents["ID"], $GLOBALS["USER"]->GetID());
			else
			{
				$array_key = $arEvents["ENTITY_TYPE"]."_".$arEvents["ENTITY_ID"]."_".$arEvents["EVENT_ID"];
				if (array_key_exists($array_key, $GLOBALS["CurUserCanAddComments"]))
					$arEvents["FIELDS_FORMATTED"]["CAN_ADD_COMMENTS"] = ($GLOBALS["CurUserCanAddComments"][$array_key] == "Y" && $arEvents["FIELDS_FORMATTED"]["HAS_COMMENTS"] == "Y" ? "Y" : "N");
				else
				{
					$feature = CSocNetLogTools::FindFeatureByEventID($arEvents["EVENT_ID"]);
					if (
						$feature 
						&& $arCommentEvent 
						&& array_key_exists("OPERATION_ADD", $arCommentEvent) 
						&& strlen($arCommentEvent["OPERATION_ADD"]) > 0
					)
						$GLOBALS["CurUserCanAddComments"][$array_key] = (CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]->GetID(), $arEvents["ENTITY_TYPE"], $arEvents["ENTITY_ID"], ($feature == "microblog" ? "blog" : $feature), $arCommentEvent["OPERATION_ADD"]) ? "Y" : "N");
					else
						$GLOBALS["CurUserCanAddComments"][$array_key] = "Y";

					$arEvents["FIELDS_FORMATTED"]["CAN_ADD_COMMENTS"] = (
						$GLOBALS["CurUserCanAddComments"][$array_key] == "Y" 
						&& $arEvents["FIELDS_FORMATTED"]["HAS_COMMENTS"] == "Y" 
							? "Y" 
							: "N"
					);
				}
			}
		}

		if (array_key_exists("FAVORITES_USER_ID", $arEvents) && intval($arEvents["FAVORITES_USER_ID"]) > 0)
			$arEvents["FIELDS_FORMATTED"]["FAVORITES"] = "Y";
		else
			$arEvents["FIELDS_FORMATTED"]["FAVORITES"] = "N";

		$arTmpEventsNew[] = $arEvents["FIELDS_FORMATTED"];
	}
}

if (!function_exists('__SLMGetLogCommentRecord'))
{
	function __SLMGetLogCommentRecord($arComments, $arParams, $arCurrentUserSubscribe, $arMyEntities, &$arTmpComments)
	{
		$arParams["MOBILE"] = "Y";
		$arParams["NEW_TEMPLATE"] = "Y";

		$dateFormated = FormatDate(
			$GLOBALS['DB']->DateFormatToPHP(FORMAT_DATE),
			MakeTimeStamp(array_key_exists("LOG_DATE_FORMAT", $arComments) ? $arComments["LOG_DATE_FORMAT"] : $arComments["LOG_DATE"])
		);
		$timestamp = MakeTimeStamp(array_key_exists("LOG_DATE_FORMAT", $arComments) ? $arComments["LOG_DATE_FORMAT"] : $arComments["LOG_DATE"]);
		$timeFormated = FormatDate(GetMessage("SONET_SLM_FORMAT_TIME"), $timestamp);
/*
		if (strlen($arParams["DATE_TIME_FORMAT"]) <= 0)
			$dateTimeFormated = __SMLFormatDate($timestamp);
		else
*/
		$dateTimeFormated = FormatDate(
			($arParams["DATE_TIME_FORMAT"] == "FULL" ? $GLOBALS["DB"]->DateFormatToPHP(str_replace(":SS", "", FORMAT_DATETIME)) : $arParams["DATE_TIME_FORMAT"]),
			$timestamp
		);

		if (strcasecmp(LANGUAGE_ID, 'EN') !== 0 && strcasecmp(LANGUAGE_ID, 'DE') !== 0)
		{
			$dateFormated = ToLower($dateFormated);
			$dateTimeFormated = ToLower($dateTimeFormated);
		}
		// strip current year
		if (!empty($arParams['DATE_TIME_FORMAT']) && ($arParams['DATE_TIME_FORMAT'] == 'j F Y G:i' || $arParams['DATE_TIME_FORMAT'] == 'j F Y g:i a'))
		{
			$dateTimeFormated = ltrim($dateTimeFormated, '0');
			$curYear = date('Y');
			$dateTimeFormated = str_replace(array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), '', $dateTimeFormated);
		}

		$title = "";

		if ($arComments["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
			$path2Entity = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arComments["ENTITY_ID"]));
		else
			$path2Entity = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arComments["ENTITY_ID"]));

		if (intval($arComments["USER_ID"]) > 0)
		{
			$suffix = (is_array($GLOBALS["arExtranetUserID"]) && in_array($arComments["USER_ID"], $GLOBALS["arExtranetUserID"]) ? GetMessage("SONET_LOG_EXTRANET_SUFFIX") : "");

			$arTmpUser = array(
				"NAME" => $arComments["~CREATED_BY_NAME"],
				"LAST_NAME" => $arComments["~CREATED_BY_LAST_NAME"],
				"SECOND_NAME" => $arComments["~CREATED_BY_SECOND_NAME"],
				"LOGIN" => $arComments["~CREATED_BY_LOGIN"]
			);
			$bUseLogin = $arParams["SHOW_LOGIN"] != "N" ? true : false;
			$arCreatedBy = array(
				"FORMATTED" => CUser::FormatName($arParams["NAME_TEMPLATE"], $arTmpUser, $bUseLogin).$suffix,
				"URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arComments["USER_ID"], "id" => $arComments["USER_ID"]))
			);
		}
		else
			$arCreatedBy = array("FORMATTED" => GetMessage("SONET_C73_CREATED_BY_ANONYMOUS"));

		$arTmpUser = array(
			"NAME" => $arComments["~USER_NAME"],
			"LAST_NAME" => $arComments["~USER_LAST_NAME"],
			"SECOND_NAME" => $arComments["~USER_SECOND_NAME"],
			"LOGIN" => $arComments["~USER_LOGIN"]
		);

		$arParamsTmp = $arParams;
		$arParamsTmp["AVATAR_SIZE"] = $arParams["AVATAR_SIZE_COMMENT"];

		if (
			is_array($arComments)
		)
		{
			unset($arComments["~MESSAGE"]);
			unset($arComments["TEXT_MESSAGE"]);
			unset($arComments["~TEXT_MESSAGE"]);
		}

		$arTmpCommentEvent = array(
			"EVENT"	=> $arComments,
			"LOG_DATE" => $arComments["LOG_DATE"],
			"LOG_DATE_TS" => MakeTimeStamp($arComments["LOG_DATE"]),
			"LOG_DATE_DAY"	=> ConvertTimeStamp(MakeTimeStamp($arComments["LOG_DATE"]), "SHORT"),
			"LOG_TIME_FORMAT" => $timeFormated,
			"TITLE_TEMPLATE" => $title,
			"TITLE" => $title,
			"TITLE_FORMAT" => $title, // need to use url here
			"ENTITY_NAME" => (($arComments["ENTITY_TYPE"] == SONET_ENTITY_GROUP) ? $arComments["GROUP_NAME"] : CUser::FormatName($arParams['NAME_TEMPLATE'], $arTmpUser, $bUseLogin)),
			"ENTITY_PATH" => $path2Entity,
			"CREATED_BY" => $arCreatedBy,
			"AVATAR_SRC" => CSocNetLogTools::FormatEvent_CreateAvatar($arComments, $arParamsTmp)
		);

		$arEvent = CSocNetLogTools::FindLogCommentEventByID($arComments["EVENT_ID"]);
		if (
			$arEvent
			&& array_key_exists("CLASS_FORMAT", $arEvent)
			&& array_key_exists("METHOD_FORMAT", $arEvent)
		)
			$arFIELDS_FORMATTED = call_user_func(array($arEvent["CLASS_FORMAT"], $arEvent["METHOD_FORMAT"]), $arComments, $arParams, false, array());

		$message = (
			$arFIELDS_FORMATTED
			&& array_key_exists("EVENT_FORMATTED", $arFIELDS_FORMATTED)
			&& array_key_exists("MESSAGE", $arFIELDS_FORMATTED["EVENT_FORMATTED"])
				? $arFIELDS_FORMATTED["EVENT_FORMATTED"]["MESSAGE"]
				: $arTmpCommentEvent["MESSAGE"]
		);

		$bToday = ($arTmpCommentEvent["LOG_DATE_DAY"] == ConvertTimeStamp());

		if (
			$bToday
			|| (intval((time() - $timestamp) / 60 / 60) < 24) // last 24h
		)
			$arFIELDS_FORMATTED["EVENT_FORMATTED"]["DATETIME"] = $timeFormated; 
		else
			$arFIELDS_FORMATTED["EVENT_FORMATTED"]["DATETIME"] = $dateTimeFormated;

		$arFIELDS_FORMATTED["EVENT_FORMATTED"]["ALLOW_VOTE"] = CRatings::CheckAllowVote(array(
			"ENTITY_TYPE_ID" => $arComments["RATING_TYPE_ID"],
			"OWNER_ID" => $arComments["USER_ID"]
		));

		$arTmpCommentEvent["EVENT_FORMATTED"] = $arFIELDS_FORMATTED["EVENT_FORMATTED"];

		$arTmpComments[] = $arTmpCommentEvent;
	}
}

if (!function_exists('__SMLFormatDate'))
{
	function __SMLFormatDate($timestamp)
	{
		$days_ago = intval((time() - $timestamp) / 60 / 60 / 24);
		$days_ago = ($days_ago <= 0 ? 1 : $days_ago);

		return str_replace("#DAYS#", $days_ago, GetMessage("SONET_SLM_DATETIME_DAYS"));
	}
}
?>