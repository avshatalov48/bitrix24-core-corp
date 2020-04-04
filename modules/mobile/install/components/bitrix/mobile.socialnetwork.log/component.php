<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/mobile.socialnetwork.log/include.php");
CPageOption::SetOptionString("main", "nav_page_in_session", "N");

if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SONET_MODULE_NOT_INSTALL"));
	return;
}

if (!$GLOBALS["USER"]->IsAuthorized())
{
	ShowError(GetMessage("SONET_SLM_NOT_AUTHORIZED"));
	return;
}

$arParams["SUBSCRIBE_ONLY"] = (defined("DisableSonetLogVisibleSubscr") && DisableSonetLogVisibleSubscr === true ? "N" : "Y");
if ($_REQUEST["skip_subscribe"] == "Y")
	$arParams["SUBSCRIBE_ONLY"] = "N";

if (!array_key_exists("USE_FOLLOW", $arParams) || strLen($arParams["USE_FOLLOW"]) <= 0)
	$arParams["USE_FOLLOW"] = "Y";

if (defined("DisableSonetLogFollow") && DisableSonetLogFollow === true)
	$arParams["USE_FOLLOW"] = "N";

// rating

CRatingsComponentsMain::GetShowRating($arParams);
$arParams["RATING_TYPE"] = COption::GetOptionString("main", "rating_vote_template", COption::GetOptionString("main", "rating_vote_type", "standart") == "like"? "like": "standart");
if ($arParams["RATING_TYPE"] == "like_graphic")
	$arParams["RATING_TYPE"] = "like";
else if ($arParams["RATING_TYPE"] == "standart")
	$arParams["RATING_TYPE"] = "standart_text";
//fix
$arParams["RATING_TYPE"] = "like";

$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
$arParams["PATH_TO_GROUP"] = trim($arParams["PATH_TO_GROUP"]);
$arParams["PATH_TO_SMILE"] = trim($arParams["PATH_TO_SMILE"]);
if (strlen($arParams["PATH_TO_SMILE"]) <= 0)
	$arParams["PATH_TO_SMILE"] = "/bitrix/images/socialnetwork/smile/";

$arParams["GROUP_ID"] = IntVal($arParams["GROUP_ID"]); // group page
$arParams["USER_ID"] = IntVal($arParams["USER_ID"]); // profile page
$arParams["LOG_ID"] = IntVal($arParams["LOG_ID"]); // log entity page

$arParams["NAME_TEMPLATE"] = $arParams["NAME_TEMPLATE"] ? $arParams["NAME_TEMPLATE"] : CSite::GetNameFormat();
$arParams["NAME_TEMPLATE_WO_NOBR"] = str_replace(
	array("#NOBR#", "#/NOBR#"),
	array("", ""),
	$arParams["NAME_TEMPLATE"]
);
$arParams["NAME_TEMPLATE"] = $arParams["NAME_TEMPLATE_WO_NOBR"];
$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;

$arParams["AVATAR_SIZE"] = (isset($arParams["AVATAR_SIZE"]) ? intval($arParams["AVATAR_SIZE"]) : 100);
$arParams["AVATAR_SIZE_COMMENT"] = (isset($arParams["AVATAR_SIZE_COMMENT"]) ? intval($arParams["AVATAR_SIZE_COMMENT"]) : 100);

$arResult["AJAX_CALL"] = (array_key_exists("AJAX_CALL", $_REQUEST) && $_REQUEST["AJAX_CALL"] == "Y");

$arParams["DESTINATION_LIMIT"] = (isset($arParams["DESTINATION_LIMIT"]) ? intval($arParams["DESTINATION_LIMIT"]) : 3);
$arParams["COMMENTS_IN_EVENT"] = (isset($arParams["COMMENTS_IN_EVENT"]) && intval($arParams["COMMENTS_IN_EVENT"]) > 0 ? $arParams["COMMENTS_IN_EVENT"] : "3");

if (
	$_REQUEST["ACTION"] == "CONVERT"
	&& $arParams["LOG_ID"] <= 0
	&& strlen($_REQUEST["ENTITY_TYPE_ID"]) > 0
	&& intval($_REQUEST["ENTITY_ID"]) > 0
)
{
	$rating_entity_type_id = preg_replace("/[^a-z0-9_-]/i", "", $_REQUEST["ENTITY_TYPE_ID"]);
	$log_event_id = false;

	switch ($rating_entity_type_id)
	{
		case "BLOG_POST":
			$log_type = "log";
			$log_event_id = array("blog_post");
			break;
		case "BLOG_COMMENT":
			$log_type = "comment";
			$log_event_id = array("blog_comment", "photo_comment");
			break;
		case "FORUM_TOPIC":
			$log_type = "log";
			$log_event_id = array("forum");
			break;
		case "FORUM_POST":
			$log_type = "comment";
			$log_event_id = array("forum", "photo_comment", "files_comment", "commondocs_comment", "tasks_comment", "wiki_comment");
			break;
		case "IBLOCK_ELEMENT":
			$log_type = "log";
			$log_event_id = array("photo_photo", "files", "commondocs", "wiki");
			break;
		case "INTRANET_NEW_USER":
			$log_type = "log";
			$log_event_id = array("intranet_new_user");
			break;
		case "INTRANET_NEW_USER_COMMENT":
			$log_type = "comment";
			$log_event_id = array("intranet_new_user_comment");
			break;
		case "BITRIX24_NEW_USER":
			$log_type = "log";
			$log_event_id = array("bitrix24_new_user");
			break;
		case "BITRIX24_NEW_USER_COMMENT":
			$log_type = "comment";
			$log_event_id = array("bitrix24_new_user_comment");
			break;
		default:
	}

	if ($log_type == "log")
	{
		$rsLogSrc = CSocNetLog::GetList(
			array(),
			array(
				"EVENT_ID" => $log_event_id,
				"SOURCE_ID" => $_REQUEST["ENTITY_ID"]
			),
			false,
			false,
			array("ID"),
			array(
				"CHECK_RIGHTS" => "Y",
				"USE_SUBSCRIBE" => "N"
			)
		);
		if ($arLogSrc = $rsLogSrc->Fetch())
			$arParams["LOG_ID"] = $arLogSrc["ID"];
	}
	elseif ($log_type == "comment")
	{
		$rsLogCommentSrc = CSocNetLogComments::GetList(
			array(),
			array(
				"EVENT_ID" => $log_event_id,
				"SOURCE_ID" => $_REQUEST["ENTITY_ID"]
			),
			false,
			false,
			array("ID", "LOG_ID"),
			array(
				"CHECK_RIGHTS" => "Y",
				"USE_SUBSCRIBE" => "N"
			)
		);
		if ($arLogCommentSrc = $rsLogCommentSrc->Fetch())
			$arParams["LOG_ID"] = $arLogCommentSrc["LOG_ID"];
	}
}

$arParams["SET_LOG_CACHE"] = (isset($arParams["SET_LOG_CACHE"]) && $arParams["LOG_ID"] <= 0 && !$arResult["AJAX_CALL"] ? $arParams["SET_LOG_CACHE"] : "N");
$arParams["SET_LOG_COUNTER"] = ($arParams["SET_LOG_CACHE"] == "Y" && !$arResult["AJAX_CALL"] ? "Y" : "N");
$arParams["SET_LOG_PAGE_CACHE"] = ($arParams["LOG_ID"] <= 0 ? "Y" : "N");
$arResult["SHOW_UNREAD"] = $arParams["SHOW_UNREAD"] = ($arParams["SET_LOG_COUNTER"] == "Y" ? "Y" : "N");

if ($arParams["LOG_ID"] > 0)
{
	$arParams["SUBSCRIBE_ONLY"] = "N";
	$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "post-card");
}
else
	$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "lenta-page");

// presets
if ($arParams["LOG_ID"] <= 0)
{
	$arResult["PresetFilters"] = false;
	$arPresetFilters = CUserOptions::GetOption("socialnetwork", "~log_filter_".SITE_ID, $GLOBALS["USER"]->GetID(), false);
	if (!is_array($arPresetFilters))
		$arPresetFilters = CUserOptions::GetOption("socialnetwork", "~log_filter", $GLOBALS["USER"]->GetID(), false);

	if (is_array($arPresetFilters))
	{
		if (!function_exists("__SL_PF_sort"))
		{
			function __SL_PF_sort($a, $b)
			{
				if ($a["SORT"] == $b["SORT"])
					return 0;
				return ($a["SORT"] < $b["SORT"]) ? -1 : 1;
			}
		}
		usort($arPresetFilters, "__SL_PF_sort");

		foreach ($arPresetFilters as $tmp_id_1 => $arPresetFilterTmp)
		{
			if (array_key_exists("NAME", $arPresetFilterTmp))
			{
				switch($arPresetFilterTmp["NAME"])
				{
					case "#WORK#":
						$arPresetFilterTmp["NAME"] = GetMessage("SONET_INSTALL_LOG_PRESET_WORK");
						break;
					case "#FAVORITES#":
						$arPresetFilterTmp["NAME"] = GetMessage("SONET_INSTALL_LOG_PRESET_FAVORITES");
						break;
					case "#MY#":
						$arPresetFilterTmp["NAME"] = GetMessage("SONET_INSTALL_LOG_PRESET_MY");
						break;
				}
			}

			if (
				array_key_exists("FILTER", $arPresetFilterTmp)
				&& is_array($arPresetFilterTmp["FILTER"])
			)
			{
				foreach($arPresetFilterTmp["FILTER"] as $tmp_id_2 => $filterTmp)
				{
					if (
						(!is_array($filterTmp) && $filterTmp == "#CURRENT_USER_ID#")
						|| (is_array($filterTmp) && in_array("#CURRENT_USER_ID#", $filterTmp))
					)
					{
						if (!is_array($filterTmp))
							$arPresetFilterTmp["FILTER"][$tmp_id_2] = $GLOBALS["USER"]->GetID();
						elseif (is_array($filterTmp))
							foreach($filterTmp as $tmp_id_3 => $valueTmp)
								if ($valueTmp == "#CURRENT_USER_ID#")
									$arPresetFilterTmp["FILTER"][$tmp_id_2][$tmp_id_3] = $GLOBALS["USER"]->GetID();
					}
				}
			}

			$arResult["PresetFilters"][$arPresetFilterTmp["ID"]] = $arPresetFilterTmp;
		}

		if ($_REQUEST["preset_filter_id"] == "clearall")
			$preset_filter_id = false;
		elseif(array_key_exists("preset_filter_id", $_REQUEST) && strlen($_REQUEST["preset_filter_id"]) > 0)
			$preset_filter_id = $_REQUEST["preset_filter_id"];

		if (
			strlen($preset_filter_id) > 0
			&& array_key_exists($preset_filter_id, $arResult["PresetFilters"])
			&& is_array($arResult["PresetFilters"][$preset_filter_id])
			&& array_key_exists("FILTER", $arResult["PresetFilters"][$preset_filter_id])
			&& is_array($arResult["PresetFilters"][$preset_filter_id]["FILTER"])
		)
		{
			if (array_key_exists("EVENT_ID", $arResult["PresetFilters"][$preset_filter_id]["FILTER"]))
				$arParams["EVENT_ID"] = $arResult["PresetFilters"][$preset_filter_id]["FILTER"]["EVENT_ID"];

			if (array_key_exists("CREATED_BY_ID", $arResult["PresetFilters"][$preset_filter_id]["FILTER"]))
				$arParams["CREATED_BY_ID"] = $arResult["PresetFilters"][$preset_filter_id]["FILTER"]["CREATED_BY_ID"];

			if (
				array_key_exists("FAVORITES_USER_ID", $arResult["PresetFilters"][$preset_filter_id]["FILTER"])
				&& $arResult["PresetFilters"][$preset_filter_id]["FILTER"]["FAVORITES_USER_ID"] == "Y"
			)
			{
				$arParams["FAVORITES"] = "Y";
				$arParams["SUBSCRIBE_ONLY"] = "N";
			}

			$arResult["PresetFilterActive"] = $preset_filter_id;
			$arParams["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = "N";
			$arParams["USE_FOLLOW"] = "N";
		}
		else
			$arResult["PresetFilterActive"] = false;
	}
}

if (
	array_key_exists("SUBSCRIBE_ONLY", $arParams)
	&& $arParams["SUBSCRIBE_ONLY"] == "Y"
	&& array_key_exists("flt_show_hidden", $_REQUEST)
	&& $_REQUEST["flt_show_hidden"] == "Y"
)
	$arParams["SHOW_HIDDEN"] = true;
elseif ($arParams["FAVORITES"] == "Y")
	$arParams["SHOW_HIDDEN"] = true;
else
	$arParams["SHOW_HIDDEN"] = false;

$arResult["SHOW_HIDDEN"] = $arParams["SHOW_HIDDEN"];
$arParams["PAGE_SIZE"] = (intval($arParams["PAGE_SIZE"]) > 0 ? $arParams["PAGE_SIZE"] : 7);

if(strlen($arParams["PATH_TO_USER_BLOG_POST"]) > 0)
	$arParams["PATH_TO_USER_MICROBLOG_POST"] = $arParams["PATH_TO_USER_BLOG_POST"];

if (intval($arParams["PHOTO_COUNT"]) <= 0)
	$arParams["PHOTO_COUNT"] = 5;
if (intval($arParams["PHOTO_THUMBNAIL_SIZE"]) <= 0)
	$arParams["PHOTO_THUMBNAIL_SIZE"] = 76;

if(
	(
		$arParams["GROUP_ID"] <= 0
		&& CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $GLOBALS["USER"]->GetID(), "blog")
	)
	|| (
		$arParams["GROUP_ID"] > 0
		&& CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $arParams["GROUP_ID"], "blog")
	)
)
	$arResult["MICROBLOG_USER_ID"] = $GLOBALS["USER"]->GetID();

$arResult["TZ_OFFSET"] = CTimeZone::GetOffset();

$GLOBALS["arExtranetGroupID"] = array();
$GLOBALS["arExtranetUserID"] = array();

if(defined("BX_COMP_MANAGED_CACHE"))
	$ttl = 2592000;
else
	$ttl = 600;

$cache_id = 'sonet_ex_gr_'.SITE_ID;
$obCache = new CPHPCache;
$cache_dir = '/bitrix/sonet_log_sg';

if($obCache->InitCache($ttl, $cache_id, $cache_dir))
{
	$tmpVal = $obCache->GetVars();
	$GLOBALS["arExtranetGroupID"] = $tmpVal['EX_GROUP_ID'];
	$GLOBALS["arExtranetUserID"] = $tmpVal['EX_USER_ID'];
	unset($tmpVal);
}
elseif (CModule::IncludeModule("extranet") && !CExtranet::IsExtranetSite())
{
	global $CACHE_MANAGER;
	$CACHE_MANAGER->StartTagCache($cache_dir);
	$dbGroupTmp = CSocNetGroup::GetList(
		array(),
		array(
			"SITE_ID" => CExtranet::GetExtranetSiteID()
		),
		false,
		false,
		array("ID")
	);
	while($arGroupTmp = $dbGroupTmp->Fetch())
	{
		$GLOBALS["arExtranetGroupID"][] = $arGroupTmp["ID"];
		$CACHE_MANAGER->RegisterTag('sonet_group_'.$arGroupTmp["ID"]);
	}

	$rsUsers = CUser::GetList(
		($by="ID"),
		($order="asc"),
		array(
			"GROUPS_ID" => array(CExtranet::GetExtranetUserGroupID()),
			"UF_DEPARTMENT" => false
		),
		array("FIELDS" => array("ID"))
	);
	while($arUser = $rsUsers->Fetch())
	{
		$GLOBALS["arExtranetUserID"][] = $arUser["ID"];
		$CACHE_MANAGER->RegisterTag('sonet_user2group_U'.$arUser["ID"]);
	}
	$CACHE_MANAGER->EndTagCache();
	if($obCache->StartDataCache())
		$obCache->EndDataCache(array(
			'EX_GROUP_ID' => $GLOBALS["arExtranetGroupID"],
			'EX_USER_ID' => $GLOBALS["arExtranetUserID"]
		));
}
unset($obCache);

$arTmpEventsNew = array();

if ($arParams["SET_TITLE"] == "Y")
	$APPLICATION->SetTitle(GetMessage("SONET_SLM_PAGE_TITLE"));

if ($arParams["SET_NAV_CHAIN"] != "N")
	$APPLICATION->AddChainItem(GetMessage("SONET_SLM_PAGE_TITLE"));

$arResult["EventsNew"] = false;

$arFilter = array();

if ($arParams["LOG_ID"] > 0)
	$arFilter["ID"] = $arParams["LOG_ID"];
elseif(
	$arResult["AJAX_CALL"]
	&& intval($arParams["NEW_LOG_ID"]) > 0
)
	$arFilter["ID"] = $arParams["NEW_LOG_ID"];
else
{
	if ($arParams["DESTINATION"] > 0)
		$arFilter["LOG_RIGHTS"] = $arParams["DESTINATION"];
	elseif ($arParams["GROUP_ID"] > 0)
		$arFilter["LOG_RIGHTS"] = "SG".intval($arParams["GROUP_ID"]);
}

if (
	$arParams["LOG_ID"] <= 0
	&& intval($arParams["NEW_LOG_ID"]) <= 0
)
{
	if (isset($arParams["EXACT_EVENT_ID"]))
		$arFilter["EVENT_ID"] = array($arParams["EXACT_EVENT_ID"]);
	elseif (is_array($arParams["EVENT_ID"]))
	{
		$event_id_fullset_tmp = array();
		foreach($arParams["EVENT_ID"] as $event_id_tmp)
			$event_id_fullset_tmp = array_merge($event_id_fullset_tmp, CSocNetLogTools::FindFullSetByEventID($event_id_tmp));
		$arFilter["EVENT_ID"] = array_unique($event_id_fullset_tmp);
	}
	elseif ($arParams["EVENT_ID"])
		$arFilter["EVENT_ID"] = CSocNetLogTools::FindFullSetByEventID($arParams["EVENT_ID"]);

	if (IntVal($arParams["CREATED_BY_ID"]) > 0) // from preset
		$arFilter["USER_ID"] = $arParams["CREATED_BY_ID"];
}

if (
	(
		$arParams["GROUP_ID"] > 0
		|| $arParams["USER_ID"] > 0
	)
	&& !array_key_exists("EVENT_ID", $arFilter)
)
{
	$arFilter["EVENT_ID"] = array();
	$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();

	foreach($arSocNetLogEvents as $event_id_tmp => $arEventTmp)
	{
		if (
			array_key_exists("HIDDEN", $arEventTmp)
			&& $arEventTmp["HIDDEN"]
		)
			continue;

		$arFilter["EVENT_ID"][] = $event_id_tmp;
	}

	$arFeatures = CSocNetFeatures::GetActiveFeatures(($arParams["GROUP_ID"] > 0 ? SONET_ENTITY_GROUP : SONET_ENTITY_GROUP), ($arParams["GROUP_ID"] > 0 ? $arParams["GROUP_ID"] : $arParams["USER_ID"]));
	foreach($arFeatures as $feature_id)
	{
		$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();

		if(
			array_key_exists($feature_id, $arSocNetFeaturesSettings)
			&& array_key_exists("subscribe_events", $arSocNetFeaturesSettings[$feature_id])
		)
		{
			foreach ($arSocNetFeaturesSettings[$feature_id]["subscribe_events"] as $event_id_tmp => $arEventTmp)
			{
				$arFilter["EVENT_ID"][] = $event_id_tmp;
			}
		}
	}
}

if (
	!$arFilter["EVENT_ID"]
	|| (is_array($arFilter["EVENT_ID"]) && count($arFilter["EVENT_ID"]) <= 0)
)
	unset($arFilter["EVENT_ID"]);

if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
	$arFilter["SITE_ID"] = SITE_ID;
else
	$arFilter["SITE_ID"] = array(SITE_ID, false);

$arFilter["<=LOG_DATE"] = "NOW";

if ($arParams["LOG_ID"] <= 0)
{
	if (!$arResult["AJAX_CALL"])
	{
		$arNavStartParams = array("nTopCount" => $arParams["PAGE_SIZE"]);
		$arResult["PAGE_NUMBER"] = 1;
		$bFirstPage = true;
	}
	else
	{
		if (intval($_REQUEST["PAGEN_".($GLOBALS["NavNum"] + 1)]) > 0)
			$arResult["PAGE_NUMBER"] = intval($_REQUEST["PAGEN_".($GLOBALS["NavNum"] + 1)]);

		$arNavStartParams = array(
			"nPageSize" => $arParams["PAGE_SIZE"],
			"bDescPageNumbering" => false,
			"bShowAll" => false,
			"iNavAddRecords" => 1,
			"bSkipPageReset" => true
		);
	}
}

if (
	$arParams["LOG_ID"] <= 0
	&& intval($arParams["NEW_LOG_ID"]) <= 0
	&& $arParams["FAVORITES"] == "Y"
)
	$arFilter[">FAVORITES_USER_ID"] = 0;

if (intval($arParams["GROUP_ID"]) > 0)
{
	$arResult["COUNTER_TYPE"] = "SG".intval($arParams["GROUP_ID"]);
	$arParams["SET_LOG_PAGE_CACHE"] = "N";
	$arParams["USE_FOLLOW"] = "N";
}
else
	$arResult["COUNTER_TYPE"] = "**";

if ($arParams["SET_LOG_COUNTER"] == "Y")
{
	$arResult["LAST_LOG_TS"] = CUserCounter::GetLastDate($GLOBALS["USER"]->GetID(), $arResult["COUNTER_TYPE"]);	
	$counterLastDate = ConvertTimeStamp($arResult["LAST_LOG_TS"], "FULL");

	if($arResult["LAST_LOG_TS"] == 0)
		$arResult["LAST_LOG_TS"] = 1;
	else
	{
		//We substruct TimeZone offset in order to get server time
		//because of template compatibility
		$arResult["LAST_LOG_TS"] -= $arResult["TZ_OFFSET"];
	}
}
elseif (
	($arResult["COUNTER_TYPE"] == "**")
	&& (
		$arParams["LOG_ID"] > 0
		|| $arResult["AJAX_CALL"]
	)
	&& intval($_REQUEST["LAST_LOG_TS"]) > 0
)
	$arResult["LAST_LOG_TS"] = intval($_REQUEST["LAST_LOG_TS"]);

if ($arParams["SET_LOG_PAGE_CACHE"] == "Y")
{
	$rsLogPages = CSocNetLogPages::GetList(
		array(
			"USER_ID" => $GLOBALS["USER"]->GetID(),
			"SITE_ID" => SITE_ID,
			"PAGE_SIZE" => $arParams["PAGE_SIZE"],
			"PAGE_NUM" => $arResult["PAGE_NUMBER"]
		),
		array("PAGE_LAST_DATE")
	);

	if ($arLogPages = $rsLogPages->Fetch())
		$arFilter[">=LOG_UPDATE"] = $arLogPages["PAGE_LAST_DATE"];
}

if ($arParams["SUBSCRIBE_ONLY"] == "Y")
{
	$arSocNetAllowedSubscribeEntityTypesDesc = CSocNetAllowed::GetAllowedEntityTypesDesc();

	foreach($arSocNetAllowedSubscribeEntityTypesDesc as $entity_type_tmp => $arEntityTypeTmp)
	{
		if (
			array_key_exists("HAS_MY", $arEntityTypeTmp)
			&& $arEntityTypeTmp["HAS_MY"] == "Y"
			&& array_key_exists("CLASS_MY", $arEntityTypeTmp)
			&& array_key_exists("METHOD_MY", $arEntityTypeTmp)
			&& strlen($arEntityTypeTmp["CLASS_MY"]) > 0
			&& strlen($arEntityTypeTmp["METHOD_MY"]) > 0
			&& method_exists($arEntityTypeTmp["CLASS_MY"], $arEntityTypeTmp["METHOD_MY"])
		)
		{
			$arMyEntities[$entity_type_tmp] = call_user_func(array($arEntityTypeTmp["CLASS_MY"], $arEntityTypeTmp["METHOD_MY"]));
		}
	}

	$arListParams = array(
		"CHECK_RIGHTS" => "Y",
		"USE_SUBSCRIBE" => "Y",
		"MY_ENTITIES" => $arMyEntities
	);

	if (!$arParams["SHOW_HIDDEN"])
		$arListParams["VISIBLE"] = "Y";
	else
		$arListParams["USE_SUBSCRIBE"] = "N";

	$arOrder = array("LOG_UPDATE" => "DESC");

	$dbEvents = CSocNetLog::GetList(
		$arOrder,
		$arFilter,
		false,
		$arNavStartParams,
		array(),
		$arListParams
	);

	// get current user subscriptions
	$arCurrentUserSubscribe = array(
		"VISIBLE" => array()
	);

	$dbResultTmp = CSocNetLogEvents::GetList(
		array(),
		array("USER_ID" => $GLOBALS["USER"]->GetID())
	);

	while($arSubscribesTmp = $dbResultTmp->Fetch())
		if ($arSubscribesTmp["VISIBLE"] != "I")
			$arCurrentUserSubscribe["VISIBLE"][$arSubscribesTmp["ENTITY_TYPE"]."_".$arSubscribesTmp["ENTITY_ID"]."_".$arSubscribesTmp["EVENT_ID"]."_".$arSubscribesTmp["ENTITY_MY"]."_".$arSubscribesTmp["ENTITY_CB"]] = $arSubscribesTmp["VISIBLE"];
}
else
{
	$arListParams = array(
		"CHECK_RIGHTS" => "Y",
		"USE_SUBSCRIBE" => "N"
	);

	if ($arParams["USE_FOLLOW"] == "Y")
	{
		$arListParams["USE_FOLLOW"] = "Y";
		$arOrder = array("DATE_FOLLOW" => "DESC");

		$dbEventsInit = CSocNetLog::GetList(
			$arOrder,
			$arFilter,
			false,
			$arNavStartParams,
			array("ID", "DATE_FOLLOW"),
			$arListParams
		);

		$arEventsFollowID = array();
		while($arEvents = $dbEventsInit->Fetch())
			$arEventsFollowID[] = $arEvents["ID"];

		if (count($arEventsFollowID) > 0)
		{
			$dbEvents = CSocNetLog::GetList(
				$arOrder,
				array("ID" => $arEventsFollowID),
				false,
				false,
				array(),
				$arListParams
			);
		}
	}
	else
	{
		$arOrder = array("LOG_UPDATE" => "DESC");

		$dbEvents = CSocNetLog::GetList(
			$arOrder,
			$arFilter,
			false,
			$arNavStartParams,
			array(),
			$arListParams
		);
	}
}

if (
	$arParams["LOG_ID"] <= 0
	&& intval($arParams["NEW_LOG_ID"]) <= 0
)
{
	if ($bFirstPage)
	{
		$arResult["PAGE_NAVNUM"] = $GLOBALS["NavNum"] + 1;
		$arResult["PAGE_NAVCOUNT"] = 1000000;
	}
	elseif ($dbEventsInit)
	{
		$arResult["PAGE_NUMBER"] = $dbEventsInit->NavPageNomer;
		$arResult["PAGE_NAVNUM"] = $dbEventsInit->NavNum;
		$arResult["PAGE_NAVCOUNT"] = $dbEventsInit->NavPageCount;
	}
	elseif ($dbEvents)
	{
		$arResult["PAGE_NUMBER"] = $dbEvents->NavPageNomer;
		$arResult["PAGE_NAVNUM"] = $dbEvents->NavNum;
		$arResult["PAGE_NAVCOUNT"] = $dbEvents->NavPageCount;
	}
}

$arLogTmpID = array();
if ($dbEvents)
{
	while ($arEvents = $dbEvents->GetNext())
	{
		$arLogTmpID[] = ($arEvents["TMP_ID"] > 0 ? $arEvents["TMP_ID"] : $arEvents["ID"]);
		__SLMGetLogRecord($arEvents, $arParams, $arCurrentUserSubscribe, $arMyEntities, $arTmpEventsNew);
	}
}

// get comments
if (
	intval($arParams["NEW_LOG_ID"]) <= 0
	&&
	(
		(
			$arParams["LOG_ID"] > 0
			&& count($arLogTmpID) == 1
			&& !in_array($arEvents["EVENT_ID"], array("blog_post", "blog_post_micro"))
		)
		||
		(
			$arParams["LOG_ID"] <= 0
			&& strlen($counterLastDate) > 0
		)
	)
)
{
	$arListParams = array(
		"CHECK_RIGHTS" => "Y"
	);

	if ($arParams["LOG_ID"] > 0)
	{
		$arFilter = array("LOG_ID" => $arParams["LOG_ID"]);
		$arSelect = array(
			"ID", "LOG_ID", "SOURCE_ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "EVENT_ID", "LOG_DATE", "MESSAGE", "TEXT_MESSAGE", "URL", "MODULE_ID",
			"GROUP_NAME", "GROUP_OWNER_ID", "GROUP_VISIBLE", "GROUP_OPENED", "GROUP_IMAGE_ID",
			"USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "USER_PERSONAL_PHOTO", "USER_PERSONAL_GENDER",
			"CREATED_BY_NAME", "CREATED_BY_LAST_NAME", "CREATED_BY_SECOND_NAME", "CREATED_BY_LOGIN", "CREATED_BY_PERSONAL_PHOTO", "CREATED_BY_PERSONAL_GENDER",
			"LOG_SITE_ID", "LOG_SOURCE_ID",
			"RATING_TYPE_ID", "RATING_ENTITY_ID", "RATING_TOTAL_VALUE", "RATING_TOTAL_VOTES", "RATING_TOTAL_POSITIVE_VOTES", "RATING_TOTAL_NEGATIVE_VOTES", "RATING_USER_VOTE_VALUE"
		);
		$arListParams["USE_SUBSCRIBE"] = "N";
	}
	else // get new comments for the feed
	{
		$arFilter = array(
			">LOG_DATE" => $counterLastDate,
			"!USER_ID" => $GLOBALS["USER"]->GetID()
		);
		$arSelect = array(
			"ID", "LOG_ID", "LOG_DATE"
		);
		$arListParams["USE_SUBSCRIBE"] = $arParams["SUBSCRIBE_ONLY"];
	}

	$dbComments = CSocNetLogComments::GetList(
		array("LOG_DATE" => "ASC"),
		$arFilter,
		false,
		false,
		$arSelect,
		$arListParams
	);

	$arTmpComments = array();

	if ($arResult["COUNTER_TYPE"] == "**")
	{
		while($arComments = $dbComments->GetNext())
		{
			if ($arParams["LOG_ID"] > 0)
				__SLMGetLogCommentRecord($arComments, $arParams, $arCurrentUserSubscribe, $arMyEntities, $arTmpComments);
			else
			{
				if (!array_key_exists($arComments["LOG_ID"], $arTmpComments))
					$arTmpComments[$arComments["LOG_ID"]] = 0;
				$arTmpComments[$arComments["LOG_ID"]]++;
			}
		}
	}

	if (
		$arParams["LOG_ID"] > 0 // just for detail
		&& count($arTmpComments) > $arParams["COMMENTS_IN_EVENT"]
	)
	{
		if ( // new comments
			intval($arResult["LAST_LOG_TS"]) > 1
			&& (MakeTimeStamp($arTmpComments[count($arTmpComments)-1]["LOG_DATE"]) - intval($arResult["TZ_OFFSET"])) > $arResult["LAST_LOG_TS"]
		)
		{
			foreach($arTmpComments as $j => $arComment)
				if ((MakeTimeStamp($arComment["LOG_DATE"]) - intval($arResult["TZ_OFFSET"])) > $arResult["LAST_LOG_TS"])
				{
					if ((count($arTmpComments) - $j) <= $arParams["COMMENTS_IN_EVENT"])
						$arTmpComments = array_slice($arTmpComments, -($arParams["COMMENTS_IN_EVENT"]), $arParams["COMMENTS_IN_EVENT"]);
					else
						$arTmpComments = array_slice($arTmpComments, $j);
					break;
				}
		}
		else
			$arTmpComments = array_slice($arTmpComments, -($arParams["COMMENTS_IN_EVENT"]), $arParams["COMMENTS_IN_EVENT"]);
	}
	elseif ($arParams["LOG_ID"] <= 0) // for the feed
		$arResult["NEW_COMMENTS"] = $arTmpComments;

	if ($arParams["LOG_ID"] > 0) // just for detail
	{
		foreach ($arTmpComments as $arComment)
		{
			$bFound = false;
			foreach($arTmpEventsNew as $key => $arTmpEvent)
			{
				if ($arTmpEvent["EVENT"]["ID"] == $arComment["EVENT"]["LOG_ID"])
				{
					$arTmpEventsNew[$key]["COMMENTS"][] = $arComment;
					$bFound = true;
					break;
				}
			}
		}
	}
}

foreach ($arTmpEventsNew as $arTmpEvent)
{
	if (
		!is_array($_SESSION["SONET_LOG_ID"])
		|| !in_array($arTmpEvent["EVENT"]["ID"], $_SESSION["SONET_LOG_ID"])
	)
		$_SESSION["SONET_LOG_ID"][] = $arTmpEvent["EVENT"]["ID"];

	$arResult["EventsNew"][] = $arTmpEvent;
}

if ($arTmpEvent["EVENT"]["DATE_FOLLOW"])
	$dateLastPage = ConvertTimeStamp(MakeTimeStamp($arTmpEvent["EVENT"]["DATE_FOLLOW"], CSite::GetDateFormat("FULL")), "FULL");

$arResult["WORKGROUPS_PAGE"] = COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", SITE_ID);

if (
	$GLOBALS["USER"]->IsAuthorized()
	&& $arParams["SET_LOG_COUNTER"] == "Y"
)
{
	CUserCounter::ClearByUser(
		$GLOBALS["USER"]->GetID(), 
		SITE_ID, 
		$arResult["COUNTER_TYPE"]
	);

	CUserCounter::ClearByUser(
		$GLOBALS["USER"]->GetID(), 
		"**", 
		$arResult["COUNTER_TYPE"]
	);
}

if (
	$GLOBALS["USER"]->IsAuthorized()
	&& $arParams["SET_LOG_PAGE_CACHE"] == "Y"
	&& $dateLastPage
)
{
	CSocNetLogPages::Set(
		$GLOBALS["USER"]->GetID(),
		$dateLastPage,
		$arParams["PAGE_SIZE"],
		$arResult["PAGE_NUMBER"],
		SITE_ID
	);
}

if (
	$GLOBALS["USER"]->IsAuthorized()
	&& $arParams["USE_FOLLOW"] == "Y"
)
{
	$rsFollow = CSocNetLogFollow::GetList(
		array(
			"USER_ID" => $GLOBALS["USER"]->GetID(),
			"CODE" => "**"
		),
		array("TYPE")
	);
	if ($arFollow = $rsFollow->Fetch())
		$arResult["FOLLOW_DEFAULT"] = $arFollow["TYPE"];
	else
		$arResult["FOLLOW_DEFAULT"] = COption::GetOptionString("socialnetwork", "follow_default_type", "Y");
}

$this->IncludeComponentTemplate();
?>