<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

__IncludeLang(__DIR__."/lang/".LANGUAGE_ID."/include.php");

$GLOBALS["CurUserCanAddComments"] = array();

if (!function_exists('__SLMGetUFMeta'))
{
	function __SLMGetUFMeta()
	{
		static $arUFMeta;
		if (!$arUFMeta)
		{
			$arUFMeta = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("SONET_COMMENT", 0, LANGUAGE_ID);
		}
		return $arUFMeta;
	}
}

if (!function_exists('__SLMGetLogRecord'))
{
	function __SLMGetLogRecord($logID, $arParams)
	{
		global $CACHE_MANAGER;

		$cache_time = 31536000;
		$arEvent = array();

		$cache = new CPHPCache;

		$arCacheID = array();
		$arKeys = array(
			"AVATAR_SIZE",
			"DESTINATION_LIMIT",
			"CHECK_PERMISSIONS_DEST",
			"NAME_TEMPLATE",
			"NAME_TEMPLATE_WO_NOBR",
			"SHOW_LOGIN",
			"DATE_TIME_FORMAT",
			"PATH_TO_USER",
			"PATH_TO_GROUP",
			"PATH_TO_CONPANY_DEPARTMENT"
		);
		foreach($arKeys as $param_key)
		{
			if (array_key_exists($param_key, $arParams))
				$arCacheID[$param_key] = $arParams[$param_key];
			else
				$arCacheID[$param_key] = false;
		}
		$siteTemplateId = SITE_TEMPLATE_ID;
		if (
			isset($arParams["SITE_TEMPLATE_ID"])
			&& $arParams["SITE_TEMPLATE_ID"] <> ''
		)
		{
			$siteTemplateId = $arParams["SITE_TEMPLATE_ID"];
		}

		$cache_id = "log_post_".$logID."_".md5(serialize($arCacheID))."_".$siteTemplateId."_".SITE_ID."_".LANGUAGE_ID."_".FORMAT_DATETIME."_".CTimeZone::GetOffset();
		$cache_path = "/sonet/log/".intval(intval($logID) / 1000)."/".$logID."/entry/";

		if (
			is_object($cache)
			&& $cache->InitCache($cache_time, $cache_id, $cache_path)
		)
		{
			$arCacheVars = $cache->GetVars();
			$arEvent["FIELDS_FORMATTED"] = $arCacheVars["FIELDS_FORMATTED"];

			if (
				array_key_exists("EVENT", $arEvent["FIELDS_FORMATTED"])
				&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT"])
			)
			{
				$arCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($arEvent["FIELDS_FORMATTED"]["EVENT"]["EVENT_ID"]);
			}

			if (array_key_exists("CACHED_CSS_PATH", $arEvent["FIELDS_FORMATTED"]))
			{
				if (
					!is_array($arEvent["FIELDS_FORMATTED"]["CACHED_CSS_PATH"])
					&& $arEvent["FIELDS_FORMATTED"]["CACHED_CSS_PATH"] <> ''
				)
				{
					$GLOBALS['APPLICATION']->SetAdditionalCSS($arEvent["FIELDS_FORMATTED"]["CACHED_CSS_PATH"]);
				}
				elseif (is_array($arEvent["FIELDS_FORMATTED"]["CACHED_CSS_PATH"]))
				{
					foreach ($arEvent["FIELDS_FORMATTED"]["CACHED_CSS_PATH"] as $css_path)
					{
						$GLOBALS['APPLICATION']->SetAdditionalCSS($css_path);
					}
				}
			}

			if (array_key_exists("CACHED_JS_PATH", $arEvent["FIELDS_FORMATTED"]))
			{
				if (
					!is_array($arEvent["FIELDS_FORMATTED"]["CACHED_JS_PATH"])
					&& $arEvent["FIELDS_FORMATTED"]["CACHED_JS_PATH"] !== ''
				)
				{
					$GLOBALS['APPLICATION']->AddHeadScript($arEvent["FIELDS_FORMATTED"]["CACHED_JS_PATH"]);
				}
				elseif(is_array($arEvent["FIELDS_FORMATTED"]["CACHED_JS_PATH"]))
				{
					foreach($arEvent["FIELDS_FORMATTED"]["CACHED_JS_PATH"] as $js_path)
					{
						$GLOBALS['APPLICATION']->AddHeadScript($js_path);
					}
				}
			}
		}
		else
		{
			if (is_object($cache))
			{
				$cache->StartDataCache($cache_time, $cache_id, $cache_path);
			}

			$arFilter = array(
				"ID" => $logID
			);

			$arListParams = array(
				"CHECK_RIGHTS" => "N",
				"USE_FOLLOW" => "N",
				"USE_SUBSCRIBE" => "N"
			);

			$arSelect = array(
				"ID", "TMP_ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "EVENT_ID", "LOG_DATE", "LOG_UPDATE", "TITLE_TEMPLATE", "TITLE", "MESSAGE", "TEXT_MESSAGE", "URL", "MODULE_ID", "CALLBACK_FUNC", "EXTERNAL_ID", "SITE_ID", "PARAMS",
				"ENABLE_COMMENTS", "SOURCE_ID",
				"GROUP_NAME", "GROUP_OWNER_ID", "GROUP_INITIATE_PERMS", "GROUP_VISIBLE", "GROUP_OPENED", "GROUP_IMAGE_ID",
				"USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "USER_PERSONAL_PHOTO", "USER_PERSONAL_GENDER",
				"CREATED_BY_NAME", "CREATED_BY_LAST_NAME", "CREATED_BY_SECOND_NAME", "CREATED_BY_LOGIN", "CREATED_BY_PERSONAL_PHOTO", "CREATED_BY_PERSONAL_GENDER",
				"RATING_TYPE_ID", "RATING_ENTITY_ID",
				"SOURCE_TYPE"
			);

			$dbEvent = CSocNetLog::GetList(
				array(),
				$arFilter,
				false,
				false,
				$arSelect,
				$arListParams
			);

			if ($arEvent = $dbEvent->GetNext())
			{
				$arCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($arEvent["EVENT_ID"]);

				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->StartTagCache($cache_path);
					$CACHE_MANAGER->RegisterTag("USER_NAME_".intval($arEvent["USER_ID"]));
					$CACHE_MANAGER->RegisterTag("SONET_LOG_".intval($arEvent["ID"]));

					if ($arEvent["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
					{
						$CACHE_MANAGER->RegisterTag("sonet_group_".$arEvent["ENTITY_ID"]);
					}
				}

				$arEvent["EVENT_ID_FULLSET"] = CSocNetLogTools::FindFullSetEventIDByEventID($arEvent["EVENT_ID"]);

				if ($arEvent["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
				{
					static $arSiteWorkgroupsPage;

					if (
						!$arSiteWorkgroupsPage
						&& IsModuleInstalled("extranet")
					)
					{
						$rsSite = CSite::GetList("sort", "desc", Array("ACTIVE" => "Y"));
						while($arSite = $rsSite->Fetch())
						{
							$arSiteWorkgroupsPage[$arSite["ID"]] = COption::GetOptionString("socialnetwork", "workgroups_page", $arSite["DIR"]."workgroups/", $arSite["ID"]);
						}
					}

					if (
						is_set($arEvent["URL"])
						&& isset($arSiteWorkgroupsPage[SITE_ID])
					)
					{
						$arEvent["URL"] = str_replace("#GROUPS_PATH#", $arSiteWorkgroupsPage[SITE_ID], $arEvent["URL"]);
					}
				}

				$arEventTmp = CSocNetLogTools::FindLogEventByID($arEvent["EVENT_ID"]);
				if (
					$arEventTmp
					&& isset($arEventTmp["CLASS_FORMAT"])
					&& isset($arEventTmp["METHOD_FORMAT"])
				)
				{
					$contentId = \Bitrix\Socialnetwork\Livefeed\Provider::getContentId($arEvent);
					if (!empty($contentId['ENTITY_TYPE']))
					{
						if ($postProvider = \Bitrix\Socialnetwork\Livefeed\Provider::getProvider($contentId['ENTITY_TYPE']))
						{
							$sourceAdditonalData = $postProvider->getAdditionalData(array(
								'id' => array($arEvent["SOURCE_ID"])
							));

							if (
								!empty($sourceAdditonalData)
								&& isset($sourceAdditonalData[$arEvent["SOURCE_ID"]])
							)
							{
								$arEvent['ADDITIONAL_DATA'] = $sourceAdditonalData[$arEvent["SOURCE_ID"]];
							}
						}
					}

					$arParams["MOBILE"] = "Y";
					$arParams["NEW_TEMPLATE"] = "Y";

					$arEvent["UF"] = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("SONET_LOG", $arEvent["ID"], LANGUAGE_ID);
					$arEvent["FIELDS_FORMATTED"] = call_user_func(array($arEventTmp["CLASS_FORMAT"], $arEventTmp["METHOD_FORMAT"]), $arEvent, $arParams);

					if (is_array($arEvent["FIELDS_FORMATTED"]))
					{
						if (
							isset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							&& isset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE"])
						)
						{
							$arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE"] = CSocNetTextParser::closetags(htmlspecialcharsback($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["MESSAGE"]));
						}

						if (
							isset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
							&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
						)
						{
							$arFields2Cache = array(
								"URL",
								"STYLE",
								"DESTINATION",
								"DESTINATION_MORE",
								"TITLE",
								"TITLE_24",
								"TITLE_24_2",
								"IS_IMPORTANT",
								"MESSAGE",
								"DATETIME_FORMATTED",
								"LOG_DATE_FORMAT",
								"DESCRIPTION",
								"DESCRIPTION_STYLE",
								"AVATAR_STYLE",
								"HAS_COMMENTS",
								"COMMENT_URL"
							);
							foreach ($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"] as $field => $value)
							{
								if (!in_array($field, $arFields2Cache))
								{
									unset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"][$field]);
								}
							}
						}

						if (
							isset($arEvent["FIELDS_FORMATTED"]["EVENT"])
							&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT"])
						)
						{
							$arFields2Cache = array(
								"ID",
								"URL",
								"USER_ID",
								"ENTITY_TYPE",
								"ENTITY_ID",
								"EVENT_ID",
								"EVENT_ID_FULLSET",
								"TITLE",
								"SOURCE_ID",
								"MODULE_ID",
								"PARAMS",
								"RATING_TYPE_ID",
								"RATING_ENTITY_ID"
							);
							foreach ($arEvent["FIELDS_FORMATTED"]["EVENT"] as $field => $value)
							{
								if (!in_array($field, $arFields2Cache))
								{
									unset($arEvent["FIELDS_FORMATTED"]["EVENT"][$field]);
								}
							}
						}

						if (
							isset($arEvent["FIELDS_FORMATTED"]["CREATED_BY"])
							&& is_array($arEvent["FIELDS_FORMATTED"]["CREATED_BY"])
						)
						{
							$arFields2Cache = array(
								"TOOLTIP_FIELDS",
								"FORMATTED",
								"URL"
							);
							foreach ($arEvent["FIELDS_FORMATTED"]["CREATED_BY"] as $field => $value)
							{
								if (!in_array($field, $arFields2Cache))
								{
									unset($arEvent["FIELDS_FORMATTED"]["CREATED_BY"][$field]);
								}
							}

							if (
								isset($arEvent["FIELDS_FORMATTED"]["CREATED_BY"]["TOOLTIP_FIELDS"])
								&& is_array($arEvent["FIELDS_FORMATTED"]["CREATED_BY"]["TOOLTIP_FIELDS"])
							)
							{
								$arFields2Cache = array(
									"ID",
									"PATH_TO_SONET_USER_PROFILE",
									"NAME",
									"LAST_NAME",
									"SECOND_NAME",
									"LOGIN",
									"EMAIL"
								);
								foreach ($arEvent["FIELDS_FORMATTED"]["CREATED_BY"]["TOOLTIP_FIELDS"] as $field => $value)
								{
									if (!in_array($field, $arFields2Cache))
									{
										unset($arEvent["FIELDS_FORMATTED"]["CREATED_BY"]["TOOLTIP_FIELDS"][$field]);
									}
								}
							}
						}

						if (
							isset($arEvent["FIELDS_FORMATTED"]["ENTITY"])
							&& is_array($arEvent["FIELDS_FORMATTED"]["ENTITY"])
						)
						{
							$arFields2Cache = array(
								"TOOLTIP_FIELDS",
								"FORMATTED",
								"URL"
							);
							foreach ($arEvent["FIELDS_FORMATTED"]["ENTITY"] as $field => $value)
							{
								if (!in_array($field, $arFields2Cache))
								{
									unset($arEvent["FIELDS_FORMATTED"]["ENTITY"][$field]);
								}
							}

							if (
								isset($arEvent["FIELDS_FORMATTED"]["ENTITY"]["TOOLTIP_FIELDS"])
								&& is_array($arEvent["FIELDS_FORMATTED"]["ENTITY"]["TOOLTIP_FIELDS"])
							)
							{
								$arFields2Cache = array(
									"ID",
									"PATH_TO_SONET_USER_PROFILE",
									"NAME",
									"LAST_NAME",
									"SECOND_NAME",
									"LOGIN",
									"EMAIL"
								);
								foreach ($arEvent["FIELDS_FORMATTED"]["ENTITY"]["TOOLTIP_FIELDS"] as $field => $value)
									if (!in_array($field, $arFields2Cache))
										unset($arEvent["FIELDS_FORMATTED"]["ENTITY"]["TOOLTIP_FIELDS"][$field]);
							}
						}

						$arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["UF"] = $arEvent["UF"];
					}
				}

				if (
					!isset($arEvent["FIELDS_FORMATTED"]["HAS_COMMENTS"])
					|| $arEvent["FIELDS_FORMATTED"]["HAS_COMMENTS"] != "N"
				)
				{
					$arEvent["FIELDS_FORMATTED"]["HAS_COMMENTS"] = (
						$arCommentEvent
						&& (
							!isset($arEvent["ENABLE_COMMENTS"])
							|| $arEvent["ENABLE_COMMENTS"] != "N"
						)
							? "Y"
							: "N"
					);
				}

				$arEvent["FIELDS_FORMATTED"]["LOG_UPDATE_TS"] = MakeTimeStamp($arEvent["LOG_UPDATE"]);
				$arEvent["FIELDS_FORMATTED"]["LOG_DATE_TS"] = MakeTimeStamp($arEvent["LOG_DATE"]);
				$arEvent["FIELDS_FORMATTED"]["LOG_DATE_DAY"] = ConvertTimeStamp(MakeTimeStamp($arEvent["LOG_DATE"]), "SHORT");
				$arEvent["FIELDS_FORMATTED"]["LOG_UPDATE_DAY"] = ConvertTimeStamp(MakeTimeStamp($arEvent["LOG_UPDATE"]), "SHORT");
			}

			if (is_object($cache))
			{
				$arCacheData = Array(
					"FIELDS_FORMATTED" => $arEvent["FIELDS_FORMATTED"]
				);
				$cache->EndDataCache($arCacheData);
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->EndTagCache();
				}
			}
		}

		if (!isset($arEvent["FIELDS_FORMATTED"]["COMMENTS_PARAMS"]))
		{
			$arEvent["FIELDS_FORMATTED"]["COMMENTS_PARAMS"] = \Bitrix\Socialnetwork\ComponentHelper::getLFCommentsParams([
				"ID" => $arEvent["FIELDS_FORMATTED"]["EVENT"]["ID"],
				"EVENT_ID" => $arEvent["FIELDS_FORMATTED"]["EVENT"]["EVENT_ID"],
				"ENTITY_TYPE" => $arEvent["FIELDS_FORMATTED"]["EVENT"]["ENTITY_TYPE"],
				"ENTITY_ID" => $arEvent["FIELDS_FORMATTED"]["EVENT"]["ENTITY_ID"],
				"SOURCE_ID" => $arEvent["FIELDS_FORMATTED"]["EVENT"]["SOURCE_ID"],
				"PARAMS" => $arEvent["FIELDS_FORMATTED"]["EVENT"]["PARAMS"]
			]);
		}

		$timestamp = MakeTimeStamp(
			isset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"])
			&& isset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["LOG_DATE_FORMAT"])
				? $arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["LOG_DATE_FORMAT"]
				: (
					isset($arParams["FIELDS_FORMATTED"]["EVENT"]["LOG_DATE_FORMAT"])
					? $arEvent["FIELDS_FORMATTED"]["EVENT"]["LOG_DATE_FORMAT"]
					: $arParams["EVENT"]["LOG_DATE"]
				)
		);

		$timeFormated = FormatDate(GetMessage("SONET_SLM_FORMAT_TIME"), $timestamp);

		if ($arParams["DATE_TIME_FORMAT"] == '')
			$dateTimeFormated = __SMLFormatDate($timestamp);
		else
			$dateTimeFormated = FormatDate(
				(
					$arParams["DATE_TIME_FORMAT"] == "FULL"
						? $GLOBALS["DB"]->DateFormatToPHP(str_replace(":SS", "", FORMAT_DATETIME))
						: $arParams["DATE_TIME_FORMAT"]
				),
				$timestamp
			);

		if (strcasecmp(LANGUAGE_ID, 'EN') !== 0 && strcasecmp(LANGUAGE_ID, 'DE') !== 0)
			$dateTimeFormated = mb_strtolower($dateTimeFormated);

		// strip current year
		if (
			!empty($arParams["DATE_TIME_FORMAT"])
			&& (
				$arParams["DATE_TIME_FORMAT"] == "j F Y G:i"
				|| $arParams["DATE_TIME_FORMAT"] == "j F Y g:i a"
			)
		)
		{
			$dateTimeFormated = ltrim($dateTimeFormated, "0");
			$curYear = date("Y");
			$dateTimeFormated = str_replace(array("-".$curYear, "/".$curYear, " ".$curYear, ".".$curYear), "", $dateTimeFormated);
		}

		$arEvent["FIELDS_FORMATTED"]["LOG_TIME_FORMAT"] = $timeFormated;

		if ($arParams["DATE_TIME_FORMAT"] == '') // list
		{
			if (isset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["LOG_DATE_FORMAT"]))
			{
				$bToday = (ConvertTimeStamp(MakeTimeStamp($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["LOG_DATE_FORMAT"]), "SHORT") == ConvertTimeStamp());
				if ($bToday)
					$arEvent["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = $timeFormated;
				else
					$arEvent["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = $dateTimeFormated;
			}
			else
			{
				$bToday = ($arEvent["FIELDS_FORMATTED"]["LOG_DATE_DAY"] == ConvertTimeStamp());
				if ($bToday)
					$arEvent["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = $timeFormated;
				else
					$arEvent["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = $dateTimeFormated;
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
			$arEvent["FIELDS_FORMATTED"]["DATETIME_FORMATTED"] = FormatDate($arFormat, $timestamp);
		}

		if (is_array($arEvent["FIELDS_FORMATTED"]["EVENT"]))
		{
			if (
				$arCommentEvent
				&& array_key_exists("OPERATION_ADD", $arCommentEvent)
				&& $arCommentEvent["OPERATION_ADD"] == "log_rights"
			)
				$arEvent["FIELDS_FORMATTED"]["CAN_ADD_COMMENTS"] = (CSocNetLogRights::CheckForUser($arEvent["FIELDS_FORMATTED"]["EVENT"]["ID"], $GLOBALS["USER"]->GetID()) ? "Y" : "N");
			else
			{
				$array_key = $arEvent["FIELDS_FORMATTED"]["EVENT"]["ENTITY_TYPE"]."_".$arEvent["FIELDS_FORMATTED"]["EVENT"]["ENTITY_ID"]."_".$arEvent["FIELDS_FORMATTED"]["EVENT"]["EVENT_ID"];

				if (array_key_exists($array_key, $GLOBALS["CurUserCanAddComments"]))
					$arEvent["FIELDS_FORMATTED"]["CAN_ADD_COMMENTS"] = (
						$GLOBALS["CurUserCanAddComments"][$array_key] == "Y"
						&& $arEvent["FIELDS_FORMATTED"]["HAS_COMMENTS"] == "Y"
							? "Y"
							: "N"
					);
				else
				{
					$feature = CSocNetLogTools::FindFeatureByEventID($arEvent["FIELDS_FORMATTED"]["EVENT"]["EVENT_ID"]);
					if (
						$feature
						&& $arCommentEvent
						&& array_key_exists("OPERATION_ADD", $arCommentEvent)
						&& $arCommentEvent["OPERATION_ADD"] <> ''
					)
						$GLOBALS["CurUserCanAddComments"][$array_key] = (
							CSocNetFeaturesPerms::CanPerformOperation(
								$GLOBALS["USER"]->GetID(),
								$arEvent["FIELDS_FORMATTED"]["EVENT"]["ENTITY_TYPE"],
								$arEvent["FIELDS_FORMATTED"]["EVENT"]["ENTITY_ID"],
								($feature == "microblog" ? "blog" : $feature),
								$arCommentEvent["OPERATION_ADD"]
							)
								? "Y"
								: "N"
						);
					else
						$GLOBALS["CurUserCanAddComments"][$array_key] = "Y";

					$arEvent["FIELDS_FORMATTED"]["CAN_ADD_COMMENTS"] = (
						$GLOBALS["CurUserCanAddComments"][$array_key] == "Y"
						&& $arEvent["FIELDS_FORMATTED"]["HAS_COMMENTS"] == "Y"
							? "Y"
							: "N"
					);
				}
			}
		}

		$arEvent["FIELDS_FORMATTED"]["FAVORITES"] = $arParams["EVENT"]["FAVORITES"];

		if ($arParams["USE_FOLLOW"] == "Y")
		{
			$arEvent["FIELDS_FORMATTED"]["EVENT"]["FOLLOW"] = $arParams["EVENT"]["FOLLOW"];
			$arEvent["FIELDS_FORMATTED"]["EVENT"]["DATE_FOLLOW_X1"] = $arParams["EVENT"]["DATE_FOLLOW_X1"] ?? null;
			$arEvent["FIELDS_FORMATTED"]["EVENT"]["DATE_FOLLOW"] = $arParams["EVENT"]["DATE_FOLLOW"];
		}

		if (
			$arParams["CHECK_PERMISSIONS_DEST"] == "N"
			&& is_object($GLOBALS["USER"])
			&& (
				(
					isset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"])
					&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"])
				)
				|| (
					isset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_CODE"])
					&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_CODE"])
				)
			)
		)
		{
			$arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_HIDDEN"] = 0;
			$bExtranetUser = (CModule::IncludeModule("extranet") && !CExtranet::IsIntranetUser());
			$arGroupID = CSocNetLogTools::GetAvailableGroups(($bExtranetUser ? "Y" : "N"));

			if (
				isset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"])
				&& is_array($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"])
			)
			{
				foreach($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"] as $key => $arDestination)
				{
					if (
						array_key_exists("TYPE", $arDestination)
						&& array_key_exists("ID", $arDestination)
						&& (
							(
								$arDestination["TYPE"] == "SG"
								&& !in_array(intval($arDestination["ID"]), $arGroupID)
							)
							|| (
								$arDestination["TYPE"] == "DR"
								&& $bExtranetUser
							)
						)
					)
					{
						unset($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"][$key]);
						$arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_HIDDEN"]++;
					}
				}

				if (
					intval($arParams["DESTINATION_LIMIT_SHOW"]) > 0
					&& count($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"]) > $arParams["DESTINATION_LIMIT_SHOW"]
				)
				{
					$arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_MORE"] = count($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"]) + $arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION_HIDDEN"] - $arParams["DESTINATION_LIMIT_SHOW"];
					$arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"] = array_slice($arEvent["FIELDS_FORMATTED"]["EVENT_FORMATTED"]["DESTINATION"], 0, $arParams["DESTINATION_LIMIT_SHOW"]);
				}
			}
		}

		if (
			$arParams["SHOW_RATING"] == "Y"
			&& $arEvent["FIELDS_FORMATTED"]["EVENT"]["RATING_TYPE_ID"] <> ''
			&& intval($arEvent["FIELDS_FORMATTED"]["EVENT"]["RATING_ENTITY_ID"]) > 0
		)
			$arEvent["FIELDS_FORMATTED"]["RATING"] = CRatings::GetRatingVoteResult($arEvent["FIELDS_FORMATTED"]["EVENT"]["RATING_TYPE_ID"], $arEvent["FIELDS_FORMATTED"]["EVENT"]["RATING_ENTITY_ID"]);

		if (isset($arEvent["FAVORITES_USER_ID"]) && intval($arEvent["FAVORITES_USER_ID"]) > 0)
			$arEvent["FIELDS_FORMATTED"]["FAVORITES"] = "Y";
		else
			$arEvent["FIELDS_FORMATTED"]["FAVORITES"] = "N";

		return $arEvent["FIELDS_FORMATTED"];
	}
}

if (!function_exists('__SLMGetLogCommentRecord'))
{
	function __SLMGetLogCommentRecord($arComments, $arParams)
	{
		$arParams["MOBILE"] = "Y";
		$arParams["NEW_TEMPLATE"] = "Y";

		$dateFormated = FormatDate(
			$GLOBALS['DB']->DateFormatToPHP(FORMAT_DATE),
			MakeTimeStamp(array_key_exists("LOG_DATE_FORMAT", $arComments) ? $arComments["LOG_DATE_FORMAT"] : $arComments["LOG_DATE"])
		);
		$timestamp = MakeTimeStamp(array_key_exists("LOG_DATE_FORMAT", $arComments) ? $arComments["LOG_DATE_FORMAT"] : $arComments["LOG_DATE"]);
		$timeFormated = FormatDate(GetMessage("SONET_SLM_FORMAT_TIME"), $timestamp);
		$dateTimeFormated = FormatDate(
			(
			$arParams["DATE_TIME_FORMAT"] == "FULL"
					? $GLOBALS["DB"]->DateFormatToPHP(str_replace(":SS", "", FORMAT_DATETIME))
					: $arParams["DATE_TIME_FORMAT"]
			),
			$timestamp
		);

		if (
			strcasecmp(LANGUAGE_ID, 'EN') !== 0
			&& strcasecmp(LANGUAGE_ID, 'DE') !== 0
		)
		{
			$dateFormated = mb_strtolower($dateFormated);
			$dateTimeFormated = mb_strtolower($dateTimeFormated);
		}
		// strip current year
		if (
			!empty($arParams['DATE_TIME_FORMAT'])
			&& (
				$arParams['DATE_TIME_FORMAT'] == 'j F Y G:i'
				|| $arParams['DATE_TIME_FORMAT'] == 'j F Y g:i a')
			)
		{
			$dateTimeFormated = ltrim($dateTimeFormated, '0');
			$curYear = date('Y');
			$dateTimeFormated = str_replace(array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), '', $dateTimeFormated);
		}

		$title = "";

		$path2Entity = (
			$arComments["ENTITY_TYPE"] == SONET_ENTITY_GROUP
				? CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $arComments["ENTITY_ID"]))
				: CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arComments["ENTITY_ID"]))
		);

		if (intval($arComments["USER_ID"]) > 0)
		{
			$suffix = (is_array($GLOBALS["arExtranetUserID"]) && in_array($arComments["USER_ID"], $GLOBALS["arExtranetUserID"]) ? GetMessage("SONET_SLM_EXTRANET_SUFFIX") : "");

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
			$arCreatedBy = array("FORMATTED" => GetMessage("SONET_SLM_CREATED_BY_ANONYMOUS"));

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
		{
			$arFIELDS_FORMATTED = call_user_func(array($arEvent["CLASS_FORMAT"], $arEvent["METHOD_FORMAT"]), $arComments, $arParams, false, array());
		}

		$arFIELDS_FORMATTED["EVENT_FORMATTED"]["DATETIME"] = (
			$arTmpCommentEvent["LOG_DATE_DAY"] == ConvertTimeStamp() // today
			|| (intval((time() - $timestamp) / 60 / 60) < 24) // last 24h
				? $timeFormated
				: $dateTimeFormated
		);

		$arFIELDS_FORMATTED["EVENT_FORMATTED"]["ALLOW_VOTE"] = CRatings::CheckAllowVote(array(
			"ENTITY_TYPE_ID" => $arComments["RATING_TYPE_ID"],
			"OWNER_ID" => $arComments["USER_ID"]
		));

		$arTmpCommentEvent["EVENT_FORMATTED"] = $arFIELDS_FORMATTED["EVENT_FORMATTED"];
		$arTmpCommentEvent["UF"] = $arComments["UF"];

		if (
			$commentAuxProvider = \Bitrix\Socialnetwork\CommentAux\Base::findProvider(
			array(
				'POST_TEXT' => $arComments['MESSAGE'],
				'SHARE_DEST' => $arComments['SHARE_DEST'],
				'SOURCE_ID' => (int)$arComments['SOURCE_ID'],
				'EVENT_ID' => $arComments['EVENT_ID'],
				'RATING_TYPE_ID' => $arComments['RATING_TYPE_ID'],
			),
			array(
				'eventId' => $arComments['EVENT_ID']
			)
		)
		)
		{
			$commentAuxProvider->setOptions(array(
				'suffix' => (!empty($arParams['COMMENT_ENTITY_SUFFIX']) ? $arParams['COMMENT_ENTITY_SUFFIX'] : ''),
				'logId' => $arComments['LOG_ID'],
				'cache' => true
			));

			$arTmpCommentEvent["EVENT_FORMATTED"]["MESSAGE"] = $commentAuxProvider->getText();
		}

		if (!empty($arTmpCommentEvent["UF"]["UF_SONET_COM_URL_PRV"]))
		{
			$urlPreviewText = \Bitrix\Socialnetwork\ComponentHelper::getUrlPreviewContent($arTmpCommentEvent["UF"]["UF_SONET_COM_URL_PRV"], array(
				"MOBILE" => "Y",
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"PATH_TO_USER" => $arParams["~PATH_TO_USER"]
			));

			if (!empty($urlPreviewText))
			{
				$arTmpCommentEvent["EVENT_FORMATTED"]["MESSAGE"] .= $urlPreviewText;
			}

			unset($arTmpCommentEvent["UF"]["UF_SONET_COM_URL_PRV"]);
		}

		return $arTmpCommentEvent;
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

if (!function_exists('__SLMAjaxGetComment'))
{
	function __SLMAjaxGetComment($comment_id, $arParams, $bCheckRights = false)
	{
		if ($arComment = CSocNetLogComments::GetByID($comment_id))
		{
			if ($bCheckRights)
			{
				if (
					mb_strpos($arComment["ENTITY_TYPE"], "CRM") === 0
					&& IsModuleInstalled("crm")
				)
				{
					$arListParams = array("IS_CRM" => "Y", "CHECK_CRM_RIGHTS" => "Y");
				}
				else
				{
					$arListParams = array("CHECK_RIGHTS" => "Y", "USE_SUBSCRIBE" => "N");
				}

				if (
					intval($arComment["LOG_ID"]) <= 0
					|| !($rsLog = CSocNetLog::GetList(array(), array("ID" => $arComment["LOG_ID"]), false, false, array("ID"), $arListParams))
					|| !($arLog = $rsLog->Fetch())
				)
				{
					return false;
				}
			}

			$arParams["DATE_TIME_FORMAT"] = $_REQUEST["dtf"];

			$dateFormated = FormatDate(
				$GLOBALS['DB']->DateFormatToPHP(FORMAT_DATE),
				MakeTimeStamp($arComment["LOG_DATE"])
			);
			$timeFormated = FormatDateFromDB($arComment["LOG_DATE"], (mb_stripos($arParams["DATE_TIME_FORMAT"], 'a') || ($arParams["DATE_TIME_FORMAT"] == 'FULL' && IsAmPmMode()) !== false ? 'H:MI T' : 'HH:MI'));
			$dateTimeFormated = FormatDate(
				(!empty($arParams['DATE_TIME_FORMAT']) ? ($arParams['DATE_TIME_FORMAT'] == 'FULL' ? $GLOBALS['DB']->DateFormatToPHP(str_replace(':SS', '', FORMAT_DATETIME)) : $arParams['DATE_TIME_FORMAT']) : $GLOBALS['DB']->DateFormatToPHP(FORMAT_DATETIME)),
				MakeTimeStamp($arComment["LOG_DATE"])
			);
			if (strcasecmp(LANGUAGE_ID, 'EN') !== 0 && strcasecmp(LANGUAGE_ID, 'DE') !== 0)
			{
				$dateFormated = mb_strtolower($dateFormated);
				$dateTimeFormated = mb_strtolower($dateTimeFormated);
			}
			// strip current year
			if (!empty($arParams['DATE_TIME_FORMAT']) && ($arParams['DATE_TIME_FORMAT'] == 'j F Y G:i' || $arParams['DATE_TIME_FORMAT'] == 'j F Y g:i a'))
			{
				$dateTimeFormated = ltrim($dateTimeFormated, '0');
				$curYear = date('Y');
				$dateTimeFormated = str_replace(array('-'.$curYear, '/'.$curYear, ' '.$curYear, '.'.$curYear), '', $dateTimeFormated);
			}

			if (intval($arComment["USER_ID"]) > 0)
			{
				$arParams = array(
					"PATH_TO_USER" => $_REQUEST["p_user"],
					"NAME_TEMPLATE" => $_REQUEST["nt"],
					"SHOW_LOGIN" => $_REQUEST["sl"],
					"AVATAR_SIZE" => (isset($_REQUEST["as"]) ? intval($_REQUEST["as"]) : 100),
					"PATH_TO_SMILE" => $_REQUEST["p_smile"]
				);

				$arUser = array(
					"ID" => $arComment["USER_ID"],
					"NAME" => $arComment["~CREATED_BY_NAME"],
					"LAST_NAME" => $arComment["~CREATED_BY_LAST_NAME"],
					"SECOND_NAME" => $arComment["~CREATED_BY_SECOND_NAME"],
					"LOGIN" => $arComment["~CREATED_BY_LOGIN"],
					"PERSONAL_PHOTO" => $arComment["~CREATED_BY_PERSONAL_PHOTO"],
					"PERSONAL_GENDER" => $arComment["~CREATED_BY_PERSONAL_GENDER"],
				);
				$bUseLogin = $arParams["SHOW_LOGIN"] != "N" ? true : false;
				$arCreatedBy = array(
					"FORMATTED" => CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, $bUseLogin),
					"URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arComment["USER_ID"], "id" => $arComment["USER_ID"]))
				);

			}
			else
			{
				$arCreatedBy = array("FORMATTED" => GetMessage("SONET_SLM_CREATED_BY_ANONYMOUS"));
			}

			$arTmpCommentEvent = array(
				"LOG_DATE" => $arComment["LOG_DATE"],
				"LOG_DATE_FORMAT" => $arComment["LOG_DATE_FORMAT"],
				"LOG_DATE_DAY" => ConvertTimeStamp(MakeTimeStamp($arComment["LOG_DATE"]), "SHORT"),
				"LOG_TIME_FORMAT" => $timeFormated,
				"MESSAGE" => $arComment["MESSAGE"],
				"MESSAGE_FORMAT" => $arComment["~MESSAGE"],
				"CREATED_BY" => $arCreatedBy,
				"AVATAR_SRC" => CSocNetLogTools::FormatEvent_CreateAvatar($arUser, $arParams, ""),
				"USER_ID" => $arComment["USER_ID"]
			);

			$arEventTmp = CSocNetLogTools::FindLogCommentEventByID($arComment["EVENT_ID"]);
			if (
				$arEventTmp
				&& array_key_exists("CLASS_FORMAT", $arEventTmp)
				&& array_key_exists("METHOD_FORMAT", $arEventTmp)
			)
			{
				$arComment["UF"] = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("SONET_COMMENT", $arComment["ID"], LANGUAGE_ID);

				if (!empty($arComment["UF"]["UF_SONET_COM_URL_PRV"]))
				{
					$urlPreviewTextMobile = \Bitrix\Socialnetwork\ComponentHelper::getUrlPreviewContent($arComment["UF"]["UF_SONET_COM_URL_PRV"], array(
						"MOBILE" => "Y",
						"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
						"PATH_TO_USER" => $arParams["~PATH_TO_USER"]
					));

					$urlPreviewTextWeb = \Bitrix\Socialnetwork\ComponentHelper::getUrlPreviewContent($arComment["UF"]["UF_SONET_COM_URL_PRV"], array(
						"MOBILE" => "N",
						"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
						"PATH_TO_USER" => $arParams["~PATH_TO_USER"]
					));

					unset($arComment["UF"]["UF_SONET_COM_URL_PRV"]);
				}

				$arFIELDS_FORMATTED = call_user_func(
					array($arEventTmp["CLASS_FORMAT"], $arEventTmp["METHOD_FORMAT"]),
					$arComment,
					array_merge(
						$arParams,
						array(
							"MOBILE" => "N",
							"PATH_TO_USER" => COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", SITE_DIR."company/personal/user/#user_id#/", SITE_ID)
						)
					)
				);
				$arTmpCommentEvent["MESSAGE_FORMAT"] = htmlspecialcharsback($arFIELDS_FORMATTED["EVENT_FORMATTED"]["MESSAGE"]);
				if (!empty($urlPreviewTextWeb))
				{
					$arTmpCommentEvent["MESSAGE_FORMAT"] .= $urlPreviewTextWeb;
				}

				$arFIELDS_FORMATTED = call_user_func(
					array($arEventTmp["CLASS_FORMAT"], $arEventTmp["METHOD_FORMAT"]),
					$arComment,
					array_merge(
						$arParams,
						array(
							"MOBILE" => "Y",
							"PATH_TO_USER" => SITE_DIR."mobile/users/?user_id=#user_id#"
						)
					)
				);
				$arTmpCommentEvent["MESSAGE_FORMAT_MOBILE"] = htmlspecialcharsback($arFIELDS_FORMATTED["EVENT_FORMATTED"]["MESSAGE"]);

				if (!empty($urlPreviewTextMobile))
				{
					$arTmpCommentEvent["MESSAGE_FORMAT_MOBILE"] .= $urlPreviewTextMobile;
				}
			}

			return $arTmpCommentEvent;
		}
	}
}

if (!function_exists("__logUFfileShowMobile"))
{
	function __logUFfileShowMobile($arResult, $arParams)
	{
		$result = false;
		if ($arParams["arUserField"]["FIELD_NAME"] == "UF_SONET_LOG_DOC" || mb_strpos($arParams["arUserField"]["FIELD_NAME"], "UF_SONET_COMMENT_DOC") === 0)
		{
			if (sizeof($arResult["VALUE"]) > 0)
			{
				?><div class="post-item-attached-file-wrap"><?

				foreach ($arResult["VALUE"] as $fileID)
				{
					$arFile = CFile::GetFileArray($fileID);
					if($arFile)
					{
						$name = $arFile["ORIGINAL_NAME"];
						$ext = '';
						$dotpos = mb_strrpos($name, ".");
						if (($dotpos !== false) && ($dotpos + 1 < mb_strlen($name)))
							$ext = mb_substr($name, $dotpos + 1);
						if (mb_strlen($ext) < 3 || mb_strlen($ext) > 5)
							$ext = '';
						$arFile["EXTENSION"] = $ext;
						$arFile["LINK"] = "/bitrix/components/bitrix/socialnetwork.log.ex/show_file.php?bp_fid=".$fileID;
						$arFile["FILE_SIZE"] = CFile::FormatSize($arFile["FILE_SIZE"]);
						?><div class="post-item-attached-file"><?
							?><a onclick="app.openDocument({'url' : '<?=$arFile["LINK"]?>'});" href="javascript:void()" class="post-item-attached-file-link"><span><?=htmlspecialcharsbx($arFile["ORIGINAL_NAME"])?></span><span>(<?=$arFile["FILE_SIZE"]?>)</span></a><?
						?></div><?
					}
				}

				?></div><?
			}
			$result = true;
		}
		return $result;
	}
}
