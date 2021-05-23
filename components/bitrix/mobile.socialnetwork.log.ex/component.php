<?

use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Bitrix\Socialnetwork\ComponentHelper;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */


require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/mobile.socialnetwork.log.ex/include.php");

CPageOption::SetOptionString("main", "nav_page_in_session", "N");

if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SONET_MODULE_NOT_INSTALL"));
	return;
}

if (!$USER->IsAuthorized())
{
	ShowError(GetMessage("SONET_SLM_NOT_AUTHORIZED"));
	return;
}

if (
	!array_key_exists("USE_FOLLOW", $arParams) 
	|| strLen($arParams["USE_FOLLOW"]) <= 0
)
{
	$arParams["USE_FOLLOW"] = "Y";
}

// rating
$arParams["RATING_TYPE"] = "like";

$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
$arParams["PATH_TO_GROUP"] = trim($arParams["PATH_TO_GROUP"]);
$arParams["PATH_TO_SMILE"] = trim($arParams["PATH_TO_SMILE"]);
if (strlen($arParams["PATH_TO_SMILE"]) <= 0)
{
	$arParams["PATH_TO_SMILE"] = "/bitrix/images/socialnetwork/smile/";
}

$moduleVersion = (defined("MOBILE_MODULE_VERSION") ? MOBILE_MODULE_VERSION : "default");
$arParams["PATH_TO_LOG_ENTRY_EMPTY"] .= (strpos($arParams["PATH_TO_LOG_ENTRY_EMPTY"], "?") !== false ? "&" : "?")."version=".$moduleVersion;

$arParams["GROUP_ID"] = IntVal($arParams["GROUP_ID"]); // group page
$arParams["USER_ID"] = IntVal($arParams["USER_ID"]); // profile page
$arParams["LOG_ID"] = IntVal($arParams["LOG_ID"]); // log entity pag

$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$arParams['FIND'] = ($request->get('FIND') ? trim($request->get('FIND')) : '');

$arParams["NAME_TEMPLATE"] = $arParams["NAME_TEMPLATE"] ? $arParams["NAME_TEMPLATE"] : CSite::GetNameFormat();
$arParams["SHOW_RATING"] = (isset($arParams["SHOW_RATING"]) ? $arParams["SHOW_RATING"] : "Y");

$arParams["NAME_TEMPLATE_WO_NOBR"] = str_replace(
	array("#NOBR#", "#/NOBR#"),
	array("", ""),
	$arParams["NAME_TEMPLATE"]
);
$arParams["NAME_TEMPLATE"] = $arParams["NAME_TEMPLATE_WO_NOBR"];
if (!isset($arParams["SHOW_LOGIN"]))
{
	$arParams["SHOW_LOGIN"] = $arParams["SHOW_LOGIN"] != "N" ? "Y" : "N";
}

$bUseLogin = $arParams["SHOW_LOGIN"] != "N" ? true : false;

$arParams["AVATAR_SIZE"] = (isset($arParams["AVATAR_SIZE"]) ? intval($arParams["AVATAR_SIZE"]) : 100);
$arParams["AVATAR_SIZE_COMMENT"] = (isset($arParams["AVATAR_SIZE_COMMENT"]) ? intval($arParams["AVATAR_SIZE_COMMENT"]) : 100);

$arResult["AJAX_CALL"] = (array_key_exists("AJAX_CALL", $_REQUEST) && $_REQUEST["AJAX_CALL"] == "Y" && ($_REQUEST["RELOAD"] != "Y" || $_REQUEST["ACTION"] == "EDIT_POST"));
$arResult["RELOAD"] = ($_REQUEST["RELOAD"] == "Y");
$arResult["RELOAD_JSON"] = (
	$arResult["RELOAD"]
	&& $_REQUEST["RELOAD_JSON"] == "Y"
);

$arParams["EMPTY_PAGE"] = ((array_key_exists("empty", $_REQUEST) && $_REQUEST["empty"] == "Y") ? "Y" : "N");

$arParams["COMMENTS_IN_EVENT"] = (isset($arParams["COMMENTS_IN_EVENT"]) && intval($arParams["COMMENTS_IN_EVENT"]) > 0 ? $arParams["COMMENTS_IN_EVENT"] : "3");
$arParams["DESTINATION_LIMIT"] = (isset($arParams["DESTINATION_LIMIT"]) ? intval($arParams["DESTINATION_LIMIT"]) : 100);
$arParams["DESTINATION_LIMIT_SHOW"] = (isset($arParams["DESTINATION_LIMIT_SHOW"]) ? intval($arParams["DESTINATION_LIMIT_SHOW"]) : 3);

if (CModule::IncludeModule("mobileapp"))
{
	$min_dimension = min(
		array(
			intval(CMobile::getInstance()->getDevicewidth()), 
			intval(CMobile::getInstance()->getDeviceheight())
		)
	);

	if ($min_dimension < 650)
	{
		$min_dimension = 650;
	}
	elseif ($min_dimension < 1300)
	{
		$min_dimension = 1300;
	}
	else
	{
		$min_dimension = 2050;
	}

	$arParams["IMAGE_MAX_WIDTH"] = intval(($min_dimension - 100) / 2);
}

if (
	$_REQUEST["ACTION"] == "CONVERT"
	&& $arParams["LOG_ID"] <= 0
)
{
	$arConvertRes = CSocNetLogTools::GetDataFromRatingEntity($_REQUEST["ENTITY_TYPE_ID"], $_REQUEST["ENTITY_ID"], false);
	if (
		is_array($arConvertRes)
		&& $arConvertRes["LOG_ID"] > 0
	)
	{
		$arParams["LOG_ID"] = $arConvertRes["LOG_ID"];
	}
}

$arParams["SET_LOG_CACHE"] = (
	isset($arParams["SET_LOG_CACHE"]) 
	&& $arParams["LOG_ID"] <= 0 
	&& !$arResult["AJAX_CALL"] 
		? $arParams["SET_LOG_CACHE"] 
		: "N"
);

$arParams["SET_LOG_COUNTER"] = (
	$arParams["SET_LOG_CACHE"] == "Y" 
	&& (
		(
			!$arResult["AJAX_CALL"] 
			&& \Bitrix\Main\Page\Frame::isAjaxRequest()
		)
		|| $arResult["RELOAD"]
	)
		? "Y" 
		: "N"
);

$arParams["SET_LOG_PAGE_CACHE"] = ($arParams["LOG_ID"] <= 0 ? "Y" : "N");
$arParams["PAGE_SIZE"] = (intval($arParams["PAGE_SIZE"]) > 0 ? $arParams["PAGE_SIZE"] : 7);

if (array_key_exists("pplogid", $_REQUEST))
{
	$arPrevPageLogID = explode("|", trim($_REQUEST["pplogid"]));
	if (is_array($arPrevPageLogID))
	{
		foreach($arPrevPageLogID as $key => $val)
		{
			preg_match('/^(\d+)$/', $val, $matches);
			if (count($matches) <= 0)
				unset($arPrevPageLogID[$key]);
		}
		$arPrevPageLogID = array_unique($arPrevPageLogID);
	}
}

if(strlen($arParams["PATH_TO_USER_BLOG_POST"]) > 0)
	$arParams["PATH_TO_USER_MICROBLOG_POST"] = $arParams["PATH_TO_USER_BLOG_POST"];

if (intval($arParams["PHOTO_COUNT"]) <= 0)
	$arParams["PHOTO_COUNT"] = 5;
if (intval($arParams["PHOTO_THUMBNAIL_SIZE"]) <= 0)
	$arParams["PHOTO_THUMBNAIL_SIZE"] = 76;

$APPLICATION->SetPageProperty("BodyClass", ($arParams["LOG_ID"] > 0 || $arParams["EMPTY_PAGE"] == "Y" ? "post-card" : "lenta-page"));

if(
	(
		$arParams["GROUP_ID"] <= 0
		&& CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $USER->GetID(), "blog")
	)
	|| (
		$arParams["GROUP_ID"] > 0
		&& CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $arParams["GROUP_ID"], "blog")
	)
)
{
	$arResult["MICROBLOG_USER_ID"] = $USER->GetID();
}

$arResult["TZ_OFFSET"] = CTimeZone::GetOffset();

if ($arParams["EMPTY_PAGE"] != "Y")
{
	CSocNetTools::InitGlobalExtranetArrays();

	$config = \Bitrix\Main\Application::getConnection()->getConfiguration();
	$arResult["ftMinTokenSize"] = (isset($config["ft_min_token_size"]) ? $config["ft_min_token_size"] : \CSQLWhere::FT_MIN_TOKEN_SIZE);

	$arResult["Events"] = false;

	$arFilter = array();

	if ($arParams["LOG_ID"] > 0)
	{
		$arFilter["ID"] = $arParams["LOG_ID"];
	}
	elseif(
		$arResult["AJAX_CALL"]
		&& intval($arParams["NEW_LOG_ID"]) > 0
	)
	{
		$arFilter["ID"] = $arParams["NEW_LOG_ID"];
	}
	else
	{
		if ($arParams["DESTINATION"] > 0)
		{
			$arFilter["LOG_RIGHTS"] = $arParams["DESTINATION"];
		}
		elseif ($arParams["GROUP_ID"] > 0)
		{
			$arFilter["LOG_RIGHTS"] = "SG".intval($arParams["GROUP_ID"]);
			$arFilter["LOG_RIGHTS_SG"] = "OSG".intval($arParams["GROUP_ID"]).'_'.($USER->IsAuthorized() ? SONET_ROLES_AUTHORIZED : SONET_ROLES_ALL);

			$rsSonetGroup = CSocNetGroup::GetList(
				array(),
				array(
					"ID" => intval($arParams["GROUP_ID"]),
					"CHECK_PERMISSIONS" => $USER->GetId()
				),
				false,
				false,
				array("ID", "NAME", "OPENED")
			);
			if ($arSonetGroup = $rsSonetGroup->Fetch())
			{
				$arResult["GROUP_NAME"] = $arSonetGroup["NAME"];
				if (
					$arSonetGroup['OPENED'] == 'Y'
					&& $USER->IsAuthorized()
					&& !CSocNetUser::IsCurrentUserModuleAdmin()
					&& !in_array(CSocNetUserToGroup::GetUserRole($USER->GetId(), $arSonetGroup["ID"]), array(SONET_ROLES_OWNER, SONET_ROLES_MODERATOR, SONET_ROLES_USER))
				)
				{
					$arResult["GROUP_READ_ONLY"] = 'Y';
				}
			}
		}

		if (strlen($arParams['FIND']) > 0)
		{
			$fullTextEnabled = \Bitrix\Socialnetwork\LogIndexTable::getEntity()->fullTextIndexEnabled('CONTENT');
			$operation = ($fullTextEnabled ? '*' : '*%');
			if (
				!$fullTextEnabled
				|| strlen($arParams['FIND']) >= $arResult["ftMinTokenSize"]
			)
			{
				$arFilter[$operation.'CONTENT'] = \Bitrix\Socialnetwork\Item\LogIndex::prepareToken($arParams['FIND']);
			}
		}

		if ($arParams["IS_CRM"] != "Y")
		{
			$arFilter["!MODULE_ID"] = ( // can't use !@MODULE_ID because of null
				COption::GetOptionString("crm", "enable_livefeed_merge", "N") == "Y"
				|| (
					!empty($arFilter["LOG_RIGHTS"])
					&& !is_array($arFilter["LOG_RIGHTS"])
					&& preg_match('/^SG(\d+)$/', $arFilter["LOG_RIGHTS"], $matches)
				)
					? array('crm')
					: array('crm', 'crm_shared')
			);
		}
	}

	if (
		$arParams["LOG_ID"] <= 0
		&& intval($arParams["NEW_LOG_ID"]) <= 0
	)
	{
		if (isset($arParams["EXACT_EVENT_ID"]))
		{
			$arFilter["EVENT_ID"] = array($arParams["EXACT_EVENT_ID"]);
		}
		elseif (is_array($arParams["EVENT_ID"]))
		{
			$event_id_fullset_tmp = array();
			foreach($arParams["EVENT_ID"] as $event_id_tmp)
			{
				$event_id_fullset_tmp = array_merge($event_id_fullset_tmp, CSocNetLogTools::FindFullSetByEventID($event_id_tmp));
			}
			$arFilter["EVENT_ID"] = array_unique($event_id_fullset_tmp);
		}
		elseif ($arParams["EVENT_ID"])
		{
			$arFilter["EVENT_ID"] = CSocNetLogTools::FindFullSetByEventID($arParams["EVENT_ID"]);
		}

		if (IntVal($arParams["CREATED_BY_ID"]) > 0) // from preset
		{
			$arFilter["USER_ID"] = $arParams["CREATED_BY_ID"];
		}
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
			{
				continue;
			}

			$arFilter["EVENT_ID"][] = $event_id_tmp;
		}

		$arFeatures = CSocNetFeatures::GetActiveFeatures(($arParams["GROUP_ID"] > 0 ? SONET_ENTITY_GROUP : SONET_ENTITY_GROUP), ($arParams["GROUP_ID"] > 0 ? $arParams["GROUP_ID"] : $arParams["USER_ID"]));
		foreach($arFeatures as $feature_id)
		{
			$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();

			if (
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
	{
		unset($arFilter["EVENT_ID"]);
	}

	$arFilter["SITE_ID"] = (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite() ? SITE_ID : array(SITE_ID, false));

	if (
		$arParams["IS_CRM"] == "Y"
		&& (strlen($arParams["CRM_ENTITY_TYPE"]) > 0)
	)
	{
		$arParams["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = "N";
	}

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
			{
				$arResult["PAGE_NUMBER"] = intval($_REQUEST["PAGEN_".($GLOBALS["NavNum"] + 1)]);
			}

			$arNavStartParams = array(
				"nPageSize" => (intval($_REQUEST["pagesize"]) > 0 ? intval($_REQUEST["pagesize"]) : $arParams["PAGE_SIZE"]),
				"bDescPageNumbering" => false,
				"bShowAll" => false,
				"iNavAddRecords" => 1,
				"bSkipPageReset" => true,
				"nRecordCount" => 1000000
			);
		}
	}

	if (
		$arParams["LOG_ID"] <= 0
		&& intval($arParams["NEW_LOG_ID"]) <= 0
		&& in_array($arParams["FILTER"], array("favorites", "my", "important", "work", "bizproc", "blog"))
	)
	{
		$arParams["SET_LOG_COUNTER"] = $arParams["SET_LOG_PAGE_CACHE"] = $arParams["USE_FOLLOW"] = "N";
		if ($arParams["FILTER"] == "favorites")
		{
			$arFilter[">FAVORITES_USER_ID"] = 0;
		}
		elseif ($arParams["FILTER"] == "my")
		{
			$arFilter["USER_ID"] = $USER->GetID();
		}
		elseif ($arParams["FILTER"] == "important")
		{
			$arFilter["EVENT_ID"] = "blog_post_important";
		}
		elseif ($arParams["FILTER"] == "work")
		{
			$arFilter["EVENT_ID"] = array("tasks", "timeman_entry", "report");
		}
		elseif ($arParams["FILTER"] == "bizproc")
		{
			$arFilter["EVENT_ID"] = "lists_new_element";
		}
		elseif ($arParams["FILTER"] == "blog")
		{
			$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;
			$arFilter["EVENT_ID"] = $blogPostLivefeedProvider->getEventId();
		}
	}

	if (!ComponentHelper::checkLivefeedTasksAllowed())
	{
		$eventIdFilter = $arFilter['EVENT_ID'];
		$notEventIdFilter = $arFilter['!EVENT_ID'];

		if (empty($notEventIdFilter))
		{
			$notEventIdFilter = [];
		}
		elseif(!is_array($notEventIdFilter))
		{
			$notEventIdFilter = [ $notEventIdFilter ];
		}

		if (empty($eventIdFilter))
		{
			$eventIdFilter = [];
		}
		elseif(!is_array($eventIdFilter))
		{
			$eventIdFilter = [ $eventIdFilter ];
		}

		if (ModuleManager::isModuleInstalled('tasks'))
		{
			$notEventIdFilter = array_merge($notEventIdFilter, [ 'tasks' ]);
			$eventIdFilter = array_filter($eventIdFilter, function($eventId) { return ($eventId != 'tasks'); });
		}
		if (
			ModuleManager::isModuleInstalled('crm')
			&& Option::get('crm', 'enable_livefeed_merge', 'N') == 'Y'
		)
		{
			$notEventIdFilter = array_merge($notEventIdFilter, [ 'crm_activity_add' ]);
			$eventIdFilter = array_filter($eventIdFilter, function($eventId) { return ($eventId != 'crm_activity_add'); });
		}

		if (!empty($notEventIdFilter))
		{
			$arFilter['!EVENT_ID'] = $notEventIdFilter;
		}

		$arFilter['EVENT_ID'] = $eventIdFilter;
	}

	if (intval($arParams["GROUP_ID"]) > 0)
	{
		$arResult["COUNTER_TYPE"] = "SG".intval($arParams["GROUP_ID"]);
		$arParams["SET_LOG_PAGE_CACHE"] = "Y";
		$arParams["USE_FOLLOW"] = "N";
		$arParams["SET_LOG_COUNTER"] = "N";
	}
	elseif(
		$arParams["IS_CRM"] == "Y"
		&& $arParams["SET_LOG_COUNTER"] != "N"
	)
	{
		$arResult["COUNTER_TYPE"] = "CRM_**";
	}
	elseif (strlen($arParams['FIND']) > 0)
	{
		$arParams['SET_LOG_COUNTER'] = 'N';
		$arParams['SET_LOG_PAGE_CACHE'] = 'N';
		$arParams['USE_FOLLOW'] = 'N';
	}
	else
	{
		$arResult["COUNTER_TYPE"] = "**";
	}

	if ($arParams["SET_LOG_COUNTER"] == "Y")
	{
		$arResult["LAST_LOG_TS"] = CUserCounter::GetLastDate($USER->GetID(), $arResult["COUNTER_TYPE"]);
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
	{
		$arResult["LAST_LOG_TS"] = intval($_REQUEST["LAST_LOG_TS"]);
	}

	$arListParams = array(
		"CHECK_RIGHTS" => "Y",
		"CHECK_VIEW" => ($arParams["LOG_ID"] <= 0 ? "Y" : "N"),
		"USE_SUBSCRIBE" => "N"
	);

	if ($arParams['LOG_ID'] > 0)
	{
		$arListParams['CHECK_RIGHTS_OSG'] = 'Y';
	}

	if (
		CModule::IncludeModule('extranet')
		&& CExtranet::IsExtranetSite()
	)
	{
		$arListParams["MY_GROUPS_ONLY"] = "Y";
	}

	if (intval($_REQUEST["pagesize"]) > 0)
	{
		$arParams["SET_LOG_PAGE_CACHE"] = "N";
	}

	if ($arParams["SET_LOG_PAGE_CACHE"] == "Y")
	{
		$groupCode = (strlen($arResult["COUNTER_TYPE"]) > 0 ? $arResult["COUNTER_TYPE"] : "**");
		$rsLogPages = \Bitrix\Socialnetwork\LogPageTable::getList(array(
			'order' => array(),
			'filter' => array(
				"USER_ID" => $user_id,
				"=SITE_ID" => SITE_ID,
				"=GROUP_CODE" => $groupCode,
				"PAGE_SIZE" => $arParams["PAGE_SIZE"],
				"PAGE_NUM" => $arResult["PAGE_NUMBER"]
			),
			'select' => array('PAGE_LAST_DATE')
		));

		if ($arLogPages = $rsLogPages->Fetch())
		{
			$dateLastPageStart = $arLogPages["PAGE_LAST_DATE"];
			$arFilter[">=LOG_UPDATE"] = ConvertTimeStamp(MakeTimeStamp($arLogPages["PAGE_LAST_DATE"], CSite::GetDateFormat("FULL")) - 60*60*24*4, "FULL");
		}
		elseif (
			$groupCode != '**'
			|| $arResult["MY_GROUPS_ONLY"] != 'Y'
		)
		{
			$rsLogPages = \Bitrix\Socialnetwork\LogPageTable::getList(array(
				'order' => array(
					'PAGE_LAST_DATE' => 'DESC'
				),
				'filter' => array(
					"=SITE_ID" => SITE_ID,
					"=GROUP_CODE" => $groupCode,
					"PAGE_SIZE" => $arParams["PAGE_SIZE"],
					"PAGE_NUM" => $arResult["PAGE_NUMBER"]
				),
				'select' => array('PAGE_LAST_DATE')
			));

			if ($arLogPages = $rsLogPages->Fetch())
			{
				$dateLastPageStart = $arLogPages["PAGE_LAST_DATE"];
				$arFilter[">=LOG_UPDATE"] = ConvertTimeStamp(MakeTimeStamp($arLogPages["PAGE_LAST_DATE"], CSite::GetDateFormat("FULL")) - 60*60*24*4, "FULL");
				$bNeedSetLogPage = true;
			}
		}
	}

	if ($arParams["USE_FOLLOW"] == "Y")
	{
		$arListParams["USE_FOLLOW"] = "Y";
		$arOrder = array("DATE_FOLLOW" => "DESC");
	}
	else
	{
		$arOrder = array("LOG_UPDATE" => "DESC");
	}
	$arOrder["ID"] = "DESC";

	$arSelectFields = array(
		"ID",
		"LOG_DATE", "LOG_UPDATE", "DATE_FOLLOW",
		"ENTITY_TYPE", "ENTITY_ID", "EVENT_ID", "SOURCE_ID", "USER_ID", "COMMENTS_COUNT",
		"FOLLOW", "FAVORITES_USER_ID",
		"RATING_TYPE_ID", "RATING_ENTITY_ID"
	);

	$arCrmActivityId = array();
	$arResult["arLogTmpID"] = array();
	$arTmpEventsNew = array();

	__MSLLogGetIds(
		$arOrder, $arFilter, $arNavStartParams, $arSelectFields, $arListParams, $bFirstPage, $arParams,
		$arResult, $arCrmActivityId, $arTmpEventsNew
	);

	if (
		count($arResult["arLogTmpID"]) <= 0
		&& $bNeedSetLogPage // no log pages for user
	)
	{
		unset($dateLastPageStart);
		unset($arFilter[">=LOG_UPDATE"]);

		__MSLLogGetIds(
			$arOrder, $arFilter, $arNavStartParams, $arSelectFields, $arListParams, $bFirstPage, $arParams,
			$arResult, $arCrmActivityId, $arTmpEventsNew
		);
	}

	$cnt = count($arResult["arLogTmpID"]);

	if (
		$cnt == 0
		&& isset($dateLastPageStart)
		&& $USER->IsAuthorized()
		&& $arParams["SET_LOG_PAGE_CACHE"] == "Y"
	)
	{
		CSocNetLogPages::DeleteEx($USER->GetID(), SITE_ID, $arParams["PAGE_SIZE"], (strlen($arResult["COUNTER_TYPE"]) > 0 ? $arResult["COUNTER_TYPE"] : "**"));
	}

	if (
		$cnt < $arParams["PAGE_SIZE"]
		&& 	isset($arFilter[">=LOG_UPDATE"])
	)
	{
		$arResult["NEXT_PAGE_SIZE"] = $cnt;
	}
	elseif (intval($_REQUEST["pagesize"]) > 0)
	{
		$arResult["NEXT_PAGE_SIZE"] = intval($_REQUEST["pagesize"]);
	}

	foreach ($arTmpEventsNew as $key => $arTmpEvent)
	{
		if (
			!is_array($arPrevPageLogID)
			|| !in_array($arTmpEvent["ID"], $arPrevPageLogID)
		)
		{
			$arTmpEventsNew[$key]["EVENT_ID_FULLSET"] = CSocNetLogTools::FindFullSetEventIDByEventID($arTmpEvent["EVENT_ID"]);
		}
		else
		{
			unset($arTmpEventsNew[$key]);
		}
	}

	$arResult["Events"] = $arTmpEventsNew;

	foreach ($arResult["Events"] as $i => $eventFields)
	{
		$event = new \Bitrix\Main\Event(
			'mobile',
			'onGetContentId',
			array(
				'logEventFields' => $eventFields
			)
		);
		$event->send();

		foreach($event->getResults() as $eventResult)
		{
			if($eventResult->getType() == \Bitrix\Main\EventResult::SUCCESS)
			{
				$eventParams = $eventResult->getParameters();

				if (
					is_array($eventParams)
					&& isset($eventParams['contentId'])
				)
				{
					$arResult["Events"][$i]['CONTENT_ID'] = $eventParams['contentId']['ENTITY_TYPE'].'-'.intval($eventParams['contentId']['ENTITY_ID']);
				}
			}
		}
	}


	if (
		$arParams["USE_FOLLOW"] == "N"
		&& $arTmpEvent["LOG_UPDATE"]
	)
	{
		$arResult["dateLastPageTS"] = MakeTimeStamp($arTmpEvent["LOG_UPDATE"], CSite::GetDateFormat("FULL"));
		$dateLastPage = ConvertTimeStamp($arResult["dateLastPageTS"], "FULL");
	}
	elseif ($arTmpEvent["DATE_FOLLOW"])
	{
		$arResult["dateLastPageTS"] = MakeTimeStamp($arTmpEvent["DATE_FOLLOW"], CSite::GetDateFormat("FULL"));
		$dateLastPage = ConvertTimeStamp($arResult["dateLastPageTS"], "FULL");
	}

	if (
		$arParams["LOG_ID"] <= 0
		&& intval($arParams["NEW_LOG_ID"]) <= 0
		&& $USER->IsAuthorized()
	)
	{
		$arCounters = CUserCounter::GetValues($USER->GetID(), SITE_ID);
		if (isset($arCounters[$arResult["COUNTER_TYPE"]]))
		{
			$arResult["LOG_COUNTER"] = intval($arCounters[$arResult["COUNTER_TYPE"]]);
		}
		else
		{
			$bEmptyCounter = true;
			$arResult["LOG_COUNTER"] = 0;
		}
	}

	$arResult["COUNTER_TO_CLEAR"] = false;

	if (
		$USER->IsAuthorized()
		&& $arParams["SET_LOG_COUNTER"] == "Y"
	)
	{
		if (
			intval($arResult["LOG_COUNTER"]) > 0
			|| $bEmptyCounter
		)
		{
			CUserCounter::ClearByUser(
				$USER->getID(),
				array(SITE_ID, "**"),
				$arResult["COUNTER_TYPE"],
				true,
				false
			);

			$arResult["COUNTER_TO_CLEAR"] = $arResult["COUNTER_TYPE"];

			$db_events = GetModuleEvents("socialnetwork", "OnSonetLogCounterClear");
			while ($arEvent = $db_events->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array($arResult["COUNTER_TYPE"], intval($arResult["LAST_LOG_TS"])));
			}
		}
		elseif ($arResult["COUNTER_TYPE"] == CUserCounter::LIVEFEED_CODE)
		{
			$arResult["COUNTER_TO_CLEAR"] = $arResult["COUNTER_TYPE"];
		}

		if (
			$arResult["COUNTER_TYPE"] == CUserCounter::LIVEFEED_CODE
			&& \Bitrix\Main\Loader::includeModule('pull')
		)
		{
			\Bitrix\Pull\Event::add($USER->getID(), Array(
				'module_id' => 'main',
				'command' => 'user_counter',
				'expiry' => 3600,
				'params' => array(
					SITE_ID => array(
						CUserCounter::LIVEFEED_CODE => 0
					)
				),
			));

			$arResult["COUNTER_TO_CLEAR"] = $arResult["COUNTER_TYPE"];
		}
	}

	if ($arResult["COUNTER_TO_CLEAR"])
	{
		$arResult["COUNTER_SERVER_TIME"] = date('c');
		$arResult["COUNTER_SERVER_TIME_UNIX"] = microtime(true);
	}

	if (
		$USER->IsAuthorized()
		&& $arParams["SET_LOG_PAGE_CACHE"] == "Y"
		&& $dateLastPage
		&& (
			!$dateLastPageStart
			|| $dateLastPageStart != $dateLastPage
			|| $bNeedSetLogPage
		)
	)
	{
		CSocNetLogPages::Set(
			$USER->GetID(),
			ConvertTimeStamp(MakeTimeStamp($dateLastPage, CSite::GetDateFormat("FULL")) - $arResult["TZ_OFFSET"], "FULL"),
			$arParams["PAGE_SIZE"],
			$arResult["PAGE_NUMBER"],
			SITE_ID,
			(strlen($arResult["COUNTER_TYPE"]) > 0 ? $arResult["COUNTER_TYPE"] : "**")
		);
	}
}
else
{
	$rsCurrentUser = CUser::GetByID($USER->GetID());
	if ($arCurrentUser = $rsCurrentUser->Fetch())
	{
		$arResult["EmptyComment"] = array(
			"AVATAR_SRC" => CSocNetLogTools::FormatEvent_CreateAvatar($arCurrentUser, $arParams, ""),
			"AUTHOR_NAME" => CUser::FormatName($arParams["NAME_TEMPLATE"], $arCurrentUser, $bUseLogin)
		);
	}
}

if (
	$USER->IsAuthorized()
	&& $arParams["USE_FOLLOW"] == "Y"
)
{
	$rsFollow = CSocNetLogFollow::GetList(
		array(
			"USER_ID" => $USER->GetID(),
			"CODE" => "**"
		),
		array("TYPE")
	);
	if ($arFollow = $rsFollow->Fetch())
	{
		$arResult["FOLLOW_DEFAULT"] = $arFollow["TYPE"];
	}
	else
	{
		$arResult["FOLLOW_DEFAULT"] = COption::GetOptionString("socialnetwork", "follow_default_type", "Y");
	}
}

$arResult["SHOW_EXPERT_MODE"] = (
	ComponentHelper::checkLivefeedTasksAllowed()
	&& ModuleManager::isModuleInstalled('tasks')
	&& $USER->isAuthorized()
		? 'Y'
		: 'N'
);

if ($arResult["SHOW_EXPERT_MODE"] == 'Y')
{
	$arResult["EXPERT_MODE"] = 'N';
	$rs = \Bitrix\Socialnetwork\LogViewTable::getList(array(
		'order' => array(),
		'filter' => array(
			"USER_ID" => $USER->GetID(),
			"EVENT_ID" => 'tasks'
		),
		'select' => array('TYPE')
	));
	if ($ar = $rs->Fetch())
	{
		$arResult["EXPERT_MODE"] = ($ar['TYPE'] == "N" ? "Y" : "N");
	}
}

$bAllowToAll = ComponentHelper::getAllowToAllDestination();

$arResult["bExtranetSite"] = (CModule::IncludeModule("extranet") && CExtranet::IsExtranetSite());
$arResult["extranetSiteId"] = (
	ModuleManager::isModuleInstalled('extranet')
		? Option::get('extranet', 'extranet_site', false)
		: false
);
if ($arResult["extranetSiteId"])
{
	$res = \Bitrix\Main\SiteTable::getList(array(
		'filter' => array('=LID' => $arResult["extranetSiteId"]),
		'select' => array('DIR')
	));
	if ($site = $res->fetch())
	{
		$arResult["extranetSiteDir"] = $site['DIR'];
	}
}
else
{
	$arResult["extranetSiteDir"] = '';
}

$arResult["bDenyToAll"] = ($arResult["bExtranetSite"] || !$bAllowToAll);
$arResult["bDefaultToAll"] = (
	$bAllowToAll
		? (COption::GetOptionString("socialnetwork", "default_livefeed_toall", "Y") == "Y")
		: false
);

if ($arResult["bExtranetSite"])
{
	$arResult["arAvailableGroup"] = CSocNetLogDestination::GetSocnetGroup(
		array(
			'features' => array(
				"blog",
				array("premoderate_post", "moderate_post", "write_post", "full_post")
			)
		)
	);
}

$arResult["bDiskInstalled"] = (
	Option::get('disk', 'successfully_converted', false)
	&& IsModuleInstalled('disk')
);

$arResult["bWebDavInstalled"] = IsModuleInstalled('webdav');

$arResult["postFormUFCode"] = (
	$arResult["bDiskInstalled"]
	|| IsModuleInstalled('webdav')
		? "UF_BLOG_POST_FILE"
		: "UF_BLOG_POST_DOC"
);

if (
	!empty($arCrmActivityId)
	&& COption::GetOptionString("crm", "enable_livefeed_merge", "N") == "Y"
	&& CModule::IncludeModule('crm')
)
{
	$arResult["CRM_ACTIVITY2TASK"] = array();

	$dbCrmActivity = CCrmActivity::GetList(
		array(),
		array(
			'TYPE_ID' => CCrmActivityType::Task,
			'ID' => $arCrmActivityId,
			'CHECK_PERMISSIONS' => 'N'
		),
		false,
		false,
		array('ID', 'ASSOCIATED_ENTITY_ID')
	);
	while ($arCrmActivity = $dbCrmActivity->Fetch())
	{
		$arResult["CRM_ACTIVITY2TASK"][$arCrmActivity['ID']] = $arCrmActivity['ASSOCIATED_ENTITY_ID'];
	}
}

$arResult["USE_FRAMECACHE"] = ($arParams["SET_LOG_COUNTER"] == "Y");

// knowledge for group
$arResult["KNOWLEDGE_PATH"] = "";
if (
	$arParams["GROUP_ID"] > 0 &&
	\Bitrix\Main\Loader::includeModule("landing") &&
	\Bitrix\Landing\Connector\SocialNetwork::userInGroup($arParams["GROUP_ID"])
)
{
	$arResult["KNOWLEDGE_PATH"] = \Bitrix\Landing\Connector\SocialNetwork::getSocNetMenuUrl(
		$arParams["GROUP_ID"],
		false
	);
}

$this->IncludeComponentTemplate();
?>