<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (strlen($arParams["NAME_TEMPLATE"]) <= 0)
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();

$arParams["TITLE_NAME_TEMPLATE"] = str_replace(
	array("#NOBR#", "#/NOBR#"),
	array("", ""),
	$arParams["NAME_TEMPLATE"]
);
$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;

$calendar2 = COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule("calendar");
if ($calendar2)
{
	$type = CCalendar::GetTypeByExternalId('iblock_'.$arParams['IBLOCK_ID']);
	if ($type)
	{
		if ($arParams['EVENT_LIST_MODE'] == 'Y')
		{
			return $APPLICATION->IncludeComponent("bitrix:calendar.events.list", "", array(
				"CALENDAR_TYPE" => $type,
				"B_CUR_USER_LIST" => $arParams['B_CUR_USER_LIST'],
				"INIT_DATE" => $arParams['INIT_DATE'],
				"FUTURE_MONTH_COUNT" => "2",
				"DETAIL_URL" => $arParams['DETAIL_URL'],
				"EVENTS_COUNT" => $arParams['EVENTS_COUNT'],
				"CACHE_TYPE" => $arParams['CACHE_TIME'] > 0 ? 'Y' : 'N',
				"CACHE_TIME" => $arParams['CACHE_TIME']
				),
				false,
				array("HIDE_ICONS" => "Y")
			);
		}
		else
		{
			return $APPLICATION->IncludeComponent("bitrix:calendar.grid", "", Array(
				"CALENDAR_TYPE" => $type,
				"OWNER_ID" => $arParams["OWNER_ID"],
				"ALLOW_SUPERPOSE" => $arParams["ALLOW_SUPERPOSE"] != "N" ? "Y" : "N",
				"ALLOW_RES_MEETING" => $arParams["ALLOW_RES_MEETING"] != "N" ? "Y" : "N"
			));
		}
	}
}

// Stub for event list
if ($arParams['EVENT_LIST_MODE'] == 'Y')
{
	return $APPLICATION->IncludeComponent("bitrix:intranet.event_list", ".default", array(
		"B_CUR_USER_LIST" => $arParams['B_CUR_USER_LIST'],
		"IBLOCK_TYPE" => $arParams['IBLOCK_TYPE'],
		"IBLOCK_ID" => $arParams['IBLOCK_ID'],
		"IBLOCK_SECTION_ID" => "",
		"INIT_DATE" => $arParams['INIT_DATE'],
		"FUTURE_MONTH_COUNT" => "2",
		"DETAIL_URL" => $arParams['DETAIL_URL'],
		"EVENTS_COUNT" => $arParams['EVENTS_COUNT'],
		"CACHE_TYPE" => $arParams['CACHE_TIME'] > 0 ? 'Y' : 'N',
		"CACHE_TIME" => $arParams['CACHE_TIME']
		),
		false,
		array("HIDE_ICONS" => "Y")
	);
}

if (!CModule::IncludeModule("intranet"))
	return ShowError(GetMessage("EC_INTRANET_MODULE_NOT_INSTALLED"));
if(!CModule::IncludeModule("iblock"))
	return ShowError(GetMessage("EC_IBLOCK_MODULE_NOT_INSTALLED"));
CModule::IncludeModule("socialnetwork");


if ($USER->IsAuthorized() && $USER->CanDoOperation('edit_php') && !$calendar2)
{
	if (CModule::IncludeModule("calendar"))
	{
		?><div style="border-radius: 3px; background: #FFE87F; border: 1px solid #C9C9C9; padding: 10px; margin: 10px 0;"><?= GetMessage('EC_CALENDAR_OLD_VERSION_INST');?></div><?
	}
	else
	{
		?><div style="border-radius: 3px; background: #FFE87F; border: 1px solid #C9C9C9; padding: 10px; margin: 10px 0;"><?= GetMessage('EC_CALENDAR_OLD_VERSION');?></div><?
	}
}

// All params
$Params = array(
	'iblockId' => $arParams["IBLOCK_ID"],
	'ownerType' => $arParams['OWNER_TYPE'],
	'ownerId' => $arParams["OWNER_ID"],
	'cacheTime' => isset($arParams['CACHE_TIME']) ? $arParams['CACHE_TIME'] : 3600 * 24 * 10, // 10 days
	'pageUrl' => htmlspecialcharsback(POST_FORM_ACTION_URI),
	'allowSuperpose' => $arParams["ALLOW_SUPERPOSE"] == 'Y',
	//'allowSuperpose' => false,
	'allowResMeeting' => $arParams["ALLOW_RES_MEETING"] != 'N',
	'allowVideoMeeting' => $arParams["ALLOW_VIDEO_MEETING"] != 'N',
	'reserveMeetingReadonlyMode' => $arParams['RESERVE_MEETING_READONLY_MODE'] == "Y",
	'pathToUser' => $arParams["PATH_TO_USER"],
	'pathToUserCalendar' => isset($arParams["PATH_TO_USER_CALENDAR"]) ? $arParams["PATH_TO_USER_CALENDAR"] : "/company/personal/user/#user_id#/calendar/",
	'pathToGroupCalendar' => isset($arParams["PATH_TO_GROUP_CALENDAR"]) ? $arParams["PATH_TO_GROUP_CALENDAR"] : '',

	'userIblockId' => isset($arParams["USERS_IBLOCK_ID"]) ? $arParams["USERS_IBLOCK_ID"] : COption::GetOptionInt("intranet", 'iblock_calendar'),

	'reinviteParamsList' => isset($arParams["REINVITE_PARAMS_LIST"]) ? $arParams["REINVITE_PARAMS_LIST"] : Array('from', 'to', 'location')
);

// Set superpose params
if ($Params["allowSuperpose"])
{
	$Params['superposeGroupsCals'] = $arParams["SUPERPOSE_GROUPS_CALS"] != 'N';
	$Params['superposeUsersCals'] = $arParams["SUPERPOSE_USERS_CALS"] != 'N';
	$Params['superposeCurUserCals'] = $arParams["SUPERPOSE_CUR_USER_CALS"] != 'N';
	$Params['arSPIblIds'] = $arParams["SUPERPOSE_CAL_IDS"];
	$Params['spGroupsIblId'] = $arParams["SUPERPOSE_GROUPS_IBLOCK_ID"];
	$Params['arSPCalDispDef'] = $arParams["SUPERPOSE_CAL_DISP_DEFAULT"];
	$Params['addCurUserCalDispByDef'] = 'Y';
}

// Set Reserve Meeting params
if ($Params["allowResMeeting"])
{
	$RMiblockId = isset($arParams["RES_MEETING_IBLOCK_ID"]) ? $arParams["RES_MEETING_IBLOCK_ID"] : 0;
	if (!$RMiblockId)
	{
		$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $arParams["IBLOCK_TYPE"], "ACTIVE"=>"Y", "CODE" => 'meeting_rooms'));
		if ($arr=$rsIBlock->Fetch())
			$RMiblockId = $arr["ID"] > 0 ? $arr["ID"] : 0;
	}

	$Params['RMiblockId'] = $RMiblockId;
	$Params['RMPath'] = isset($arParams["PATH_TO_RES_MEETING"]) ? $arParams["PATH_TO_RES_MEETING"] : '';
	$Params['RMUserGroups'] = isset($arParams["RES_MEETING_USERGROUPS"]) ? $arParams["RES_MEETING_USERGROUPS"] : Array(1);
}

// Set Reserve Video Meeting params
if ($Params["allowVideoMeeting"])
{
	$VMiblockId = isset($arParams["VIDEO_MEETING_IBLOCK_ID"]) ? $arParams["VIDEO_MEETING_IBLOCK_ID"] : 0;
	if (!$VMiblockId)
	{
		$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $arParams["IBLOCK_TYPE"], "ACTIVE"=>"Y", "CODE" => 'video-meeting'));
		if ($arr=$rsIBlock->Fetch())
			$VMiblockId = $arr["ID"] > 0 ? $arr["ID"] : 0;
	}

	$Params['VMiblockId'] = $VMiblockId;
	$Params['VMPath'] = isset($arParams["PATH_TO_VIDEO_MEETING"]) ? $arParams["PATH_TO_VIDEO_MEETING"] : '';
	$Params['VMUserGroups'] = isset($arParams["VIDEO_MEETING_USERGROUPS"]) ? $arParams["VIDEO_MEETING_USERGROUPS"] : Array(1);
}

$APPLICATION->ResetException();

// Create new instance of Event Calendar object
$EC = new CEventCalendar;
$EC->Init($Params); // Init with $Params array

if (isset($_REQUEST['action']))
	$EC->Request($_REQUEST['action']);
else
	$EC->Show(array(
		'initDate' => $arParams["INIT_DATE"],
		'weekHolidays' => $arParams['WEEK_HOLIDAYS'],
		'workTime' => array(intVal($arParams['WORK_TIME_START']), intVal($arParams['WORK_TIME_END'])),
		'yearHolidays' => $arParams['YEAR_HOLIDAYS'],
	));

if($ex = $APPLICATION->GetException())
	return ShowError($ex->GetString());

// Set title and navigation
$arParams["SET_TITLE"] = $arParams["SET_TITLE"] == "Y" ? "Y" : "N";
$arParams["SET_NAV_CHAIN"] = $arParams["SET_NAV_CHAIN"] == "Y" ? "Y" : "N"; //Turn OFF by default

if ($arParams["STR_TITLE"])
	$arParams["STR_TITLE"] = trim($arParams["STR_TITLE"]);
else
{
	if ($arParams['OWNER_TYPE'] == "GROUP" || $arParams['OWNER_TYPE'] == "USER")
	{
		$feature = "calendar";
		$arEntityActiveFeatures = CSocNetFeatures::GetActiveFeaturesNames((($arParams['OWNER_TYPE'] == "GROUP") ? SONET_ENTITY_GROUP : SONET_ENTITY_USER), $arParams['OWNER_ID']);
		$strFeatureTitle = ((array_key_exists($feature, $arEntityActiveFeatures) && StrLen($arEntityActiveFeatures[$feature]) > 0) ? $arEntityActiveFeatures[$feature] : GetMessage("EC_SONET_CALENDAR"));
		$arParams["STR_TITLE"] = $strFeatureTitle;
	}
	else
		$arParams["STR_TITLE"] = GetMessage("EC_SONET_CALENDAR");
}


if ($arParams["SET_TITLE"] == "Y" || ($EC->bOwner && $arParams["SET_NAV_CHAIN"] == "Y"))
{
	$ownerName = '';
	if ($EC->bOwner)
	{
		if($EC->ownerType == 'GROUP')
		{
			if (!$arGroup = CSocNetGroup::GetByID($EC->ownerId))
				return ShowError(GetMessage("EC_GROUP_NOT_FOUND"));
			$ownerName = $arGroup["NAME"];
		}
		else
		{
			if ($USER->IsAuthorized() && $EC->ownerId == $USER->GetID())
			{
				$arTmpUser = array(
					"NAME" => $USER->GetFirstName(),
					"LAST_NAME" => $USER->GetLastName(),
					"SECOND_NAME" => $USER->GetParam("SECOND_NAME"),
					"LOGIN" => $USER->GetLogin(),
				);
			}
			else
			{
				$dbUser = CUser::GetByID($EC->ownerId);
				if (!$arUser = $dbUser->Fetch())
					return ShowError(GetMessage("EC_USER_NOT_FOUND"));

				$arTmpUser = array(
					"NAME" => $arUser["NAME"],
					"LAST_NAME" => $arUser["LAST_NAME"],
					"SECOND_NAME" => $arUser["SECOND_NAME"],
					"LOGIN" => $arUser["LOGIN"],
				);
			}
			$ownerName = CUser::FormatName($arParams['TITLE_NAME_TEMPLATE'], $arTmpUser, $bUseLogin);
		}
		$ownerName = trim($ownerName);
	}

	if($arParams["SET_TITLE"] == "Y")
	{
		$title = ($ownerName ? $ownerName.': ' : '').(empty($arParams["STR_TITLE"]) ? GetMessage("WD_TITLE") : $arParams["STR_TITLE"]);
		$APPLICATION->SetTitle($title);
	}

	if ($EC->bOwner && $arParams["SET_NAV_CHAIN"] == "Y")
	{
		if($EC->ownerType == 'GROUP')
		{
			$APPLICATION->AddChainItem($ownerName, CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP"], array("group_id" => $EC->ownerId)));
			$APPLICATION->AddChainItem($arParams["STR_TITLE"], CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_CALENDAR"], array("group_id" => $EC->ownerId, "path" => "")));
		}
		else
		{
			$APPLICATION->AddChainItem(htmlspecialcharsEx($ownerName), CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $EC->ownerId)));
			$APPLICATION->AddChainItem($arParams["STR_TITLE"], CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_CALENDAR"], array("user_id" => $EC->ownerId, "path" => "")));
		}
	}
}
?>