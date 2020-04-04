<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(!CModule::IncludeModule("iblock"))
	return;

$adv_mode = ($arCurrentValues["ADVANCED_MODE_SETTINGS"] == 'Y');
$eventListMode = ($arCurrentValues["EVENT_LIST_MODE"] == 'Y');
$bSocNet = CModule::IncludeModule("socialnetwork");

if($bSocNet)
	$bSocNet = class_exists('CSocNetUserToGroup') && CBXFeatures::IsFeatureEnabled("Calendar");

$hidden = "N";

$arIBlockType = array();
$rsIBlockType = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
while ($arr=$rsIBlockType->Fetch())
{
	if($ar=CIBlockType::GetByIDLang($arr["ID"], LANGUAGE_ID))
		$arIBlockType[$arr["ID"]] = "[".$arr["ID"]."] ".$ar["NAME"];
}

$arIBlock=array();
$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $arCurrentValues["IBLOCK_TYPE"], "ACTIVE"=>"Y"));
while($arr=$rsIBlock->Fetch())
	$arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];

// * * * * * * * * * * * *  Groups * * * * * * * * * * * *
$arComponentParameters = array();
$arComponentParameters["GROUPS"] = array(
	"RESERVE_MEETING" => array("NAME" => GetMessage("EC_GROUP_RESERVE_MEETING"), "SORT" => "350"),
	"VIDEO_MEETING" => array("NAME" => GetMessage("EC_GROUP_VIDEO_MEETING"), "SORT" => "360"),
	"ADDITIONAL_SETTINGS" => array("NAME" => GetMessage("EC_GROUP_ADDITIONAL_SETTINGS"), "SORT" => "900")
);

if ($bSocNet)
{
	$arComponentParameters["GROUPS"]["SUPERPOSE"] = array("NAME" => GetMessage("EC_GROUP_SUPERPOSE"), "SORT" => "300");
	$arComponentParameters["GROUPS"]["MEETING_SETTINGS"] = array("NAME" => GetMessage("EC_GROUP_MEETING_SETTINGS"), "SORT" => "400");
}

//* * * * * * * * * * * Parameters  * * * * * * * * * * *
$arParams = array(); // $arComponentParameters["PARAMETERS"]
$arParams["IBLOCK_TYPE"] = Array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("EC_P_IBLOCK_TYPE"),
	"TYPE" => "LIST",
	"VALUES" => $arIBlockType,
	"REFRESH" => "Y",
);
$arParams["IBLOCK_ID"] = array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("EC_P_IBLOCK"),
	"TYPE" => "LIST",
	"VALUES" => $arIBlock,
	"REFRESH" => "Y",
);

$arParams["EVENT_LIST_MODE"] = Array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("EC_P_EVENT_LIST_MODE"),
	"TYPE" => "CHECKBOX",
	"DEFAULT" => "N",
	"REFRESH" => "Y",
);

if (!$eventListMode)
{
	$arParams["RESERVE_MEETING_READONLY_MODE"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("EC_P_RESERVE_MEETING_READONLY_MODE"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "N",
		"HIDDEN" => "N"
	);

	$arParams["INIT_DATE"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("EC_P_INIT_DATE"),
		"DEFAULT" => '-'.GetMessage("EC_P_SHOW_CUR_DATE").'-',
		"HIDDEN" => $hidden,
	);
	$arParams["WEEK_HOLIDAYS"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("EC_P_WEEK_HOLIDAYS"),
		"TYPE" => "LIST",
		"MULTIPLE" => "Y",
		"VALUES" => array(GetMessage('EC_P_MON_F'),GetMessage('EC_P_TUE_F'),GetMessage('EC_P_WEN_F'),GetMessage('EC_P_THU_F'),GetMessage('EC_P_FRI_F'),GetMessage('EC_P_SAT_F'),GetMessage('EC_P_SAN_F')),
		"DEFAULT" => array(5,6),
		"HIDDEN" => $hidden,
	);
	$arParams["YEAR_HOLIDAYS"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("EC_P_YEAR_HOLIDAYS"),
		"TYPE" => 'STRING',
		"ROWS" => 3,
		"DEFAULT" => '1.01,7.01,23.02,8.03',
		"HIDDEN" => $hidden,
	);
	$arParams["LOAD_MODE"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("EC_P_LOAD_MODE"),
		"TYPE" => "LIST",
		"VALUES" => array('ajax' => GetMessage('EC_P_LOAD_MODE_AJAX'), 'all' => GetMessage('EC_P_LOAD_MODE_ALL')),
		"DEFAULT" => 'ajax',
		"HIDDEN" => $hidden,
	);
}
else
{
	$arParams["B_CUR_USER_LIST"] = Array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("EC_P_CUR_USER_EVENT_LIST"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "N",
	);
	$arParams["DETAIL_URL"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("EC_P_DETAIL_URL"),
		"DEFAULT" => ""
	);
	$arParams["EVENTS_COUNT"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("EC_P_EVENTS_COUNT"),
		"DEFAULT" => "5"
	);
}

$arParams["CACHE_TIME"] = Array(
	"PARENT" => "BASE",
	"NAME" => GetMessage("EC_P_CACHE_TIME"),
	"DEFAULT" => "3600",
);

if ($bSocNet)
{
	$arParams["PATH_TO_USER"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("EC_P_PATH_TO_USER"),
		"DEFAULT" => "/company/personal/user/#user_id#/",
	);

	$arParams["PATH_TO_USER_CALENDAR"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("EC_P_PATH_TO_USER_CALENDAR"),
		"DEFAULT" => "/company/personal/user/#user_id#/calendar/",
	);

	$arParams["REINVITE_PARAMS_LIST"] = array(
		"PARENT" => "MEETING_SETTINGS",
		"NAME" => GetMessage("EC_P_REINVITE_PARAMS_LIST"),
		"TYPE" => "LIST",
		"MULTIPLE" => "Y",
		"VALUES" => array(
			'name' => GetMessage('EC_P_EV_NAME'),
			'desc' => GetMessage('EC_P_EV_DESC'),
			'from' => GetMessage('EC_P_EV_FROM'),
			'to' => GetMessage('EC_P_EV_TO'),
			'location' => GetMessage('EC_P_LOCATION'),
			'guest_list' => GetMessage('EC_P_GUEST_LIST'),
			'repeating' => GetMessage('EC_P_REPEATING'),
			'meet_text' => GetMessage('EC_P_MEET_TEXT'),
			'importance' => GetMessage('EC_P_IMPORTANCE')
		),
		"DEFAULT" => Array("from", "to", "location")
	);
}

$arWorTimeList = array();
for ($i = 0; $i < 24; $i++)
{
	$arWorTimeList[strval($i)] = strval($i).'.00';
	$arWorTimeList[strval($i).'.30'] = strval($i).'.30';
}

$arParams["WORK_TIME_START"] = array(
	"TYPE" => "LIST",
	"PARENT" => "BASE",
	"NAME" => GetMessage("EC_P_WORK_TIME_START"),
	"DEFAULT" => "9",
	"VALUES" => $arWorTimeList
);

$arParams["WORK_TIME_END"] = array(
	"TYPE" => "LIST",
	"PARENT" => "BASE",
	"NAME" => GetMessage("EC_P_WORK_TIME_END"),
	"DEFAULT" => "19",
	"VALUES" => $arWorTimeList
);

//SUPERPOSE
if ($bSocNet)
{
	$arParams["ALLOW_SUPERPOSE"] = Array(
		"PARENT" => "SUPERPOSE",
		"NAME" => GetMessage("EC_P_ALLOW_SUPERPOSE"),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => "N",
		"REFRESH" => "Y",
	);

	if ($arCurrentValues["ALLOW_SUPERPOSE"] == 'Y')
	{
		$arParams["SUPERPOSE_CAL_IDS"] = array(
			"PARENT" => "SUPERPOSE",
			"NAME" => GetMessage("EC_P_SUPERPOSE_CAL_IDS"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => $arIBlock
		);

		$arParams["SUPERPOSE_CAL_DISP_DEFAULT"] = array(
			"PARENT" => "SUPERPOSE",
			"NAME" => GetMessage("EC_P_SUPERPOSE_CAL_DISP_DEFAULT"),
			"TYPE" => "STRING",
			"MULTIPLE" => "Y"
		);

		if ($bSocNet)
		{
			// Cur user
			$arParams["SUPERPOSE_CUR_USER_CALS"] = Array(
				"PARENT" => "SUPERPOSE",
				"NAME" => GetMessage("EC_P_SUPERPOSE_CUR_USER_CALS"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
				"REFRESH" => "Y",
			);
			// Users
			$arParams["SUPERPOSE_USERS_CALS"] = Array(
				"PARENT" => "SUPERPOSE",
				"NAME" => GetMessage("EC_P_SUPERPOSE_USERS_CALS"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
				"REFRESH" => "Y",
			);
			// Groups
			$arParams["SUPERPOSE_GROUPS_CALS"] = Array(
				"PARENT" => "SUPERPOSE",
				"NAME" => GetMessage("EC_P_SUPERPOSE_GROUPS_CALS"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
				"REFRESH" => "Y",
			);
			$arParams["SUPERPOSE_GROUPS_IBLOCK_ID"] = array(
				"PARENT" => "SUPERPOSE",
				"NAME" => GetMessage("EC_P_SUPERPOSE_GROUPS_IBLOCK_ID"),
				"TYPE" => "LIST",
				"VALUES" => $arIBlock
			);
		}
	}
}

/* Reserve Meeting Rooms*/
$arParams["ALLOW_RES_MEETING"] = Array(
	"PARENT" => "RESERVE_MEETING",
	"NAME" => GetMessage("EC_P_ALLOW_RES_MEETING"),
	"TYPE" => "CHECKBOX",
	"DEFAULT" => "Y",
	"REFRESH" => "Y",
);

if ($arCurrentValues["ALLOW_RES_MEETING"] != 'N')
{
	$arParams["RES_MEETING_IBLOCK_ID"] = array(
		"PARENT" => "RESERVE_MEETING",
		"NAME" => GetMessage("EC_P_RES_MEETING_IBLOCK"),
		"TYPE" => "LIST",
		"VALUES" => $arIBlock,
		"REFRESH" => "Y",
	);

	$arParams["PATH_TO_RES_MEETING"] = array(
		"PARENT" => "RESERVE_MEETING",
		"NAME" => GetMessage("EC_P_PATH_TO_RES_MEETING"),
		"DEFAULT" => "",
	);

	/* Access to Reserve Meeting */
	$arUserGroups = array();
	$dbGroups = CGroup::GetList($b = "NAME", $o = "ASC", array("ACTIVE" => "Y"));
	while ($arGroup = $dbGroups->GetNext())
		$arUserGroups[$arGroup["ID"]] = "[".$arGroup["ID"]."] ".$arGroup["NAME"];

	$arParams["RES_MEETING_USERGROUPS"] = array(
		"PARENT" => "RESERVE_MEETING",
		"NAME" => GetMessage("EC_P_RES_MEETING_USERGROUPS"),
		"TYPE" => "LIST",
		"MULTIPLE" => "Y",
		"VALUES" => $arUserGroups,
		"DEFAULT" => Array(1)
	);
}

/* Reserve Video-Meeting Rooms*/
$arParams["ALLOW_VIDEO_MEETING"] = Array(
	"PARENT" => "VIDEO_MEETING",
	"NAME" => GetMessage("EC_P_ALLOW_VIDEO_MEETING"),
	"TYPE" => "CHECKBOX",
	"DEFAULT" => "Y",
	"REFRESH" => "Y",
);

if ($arCurrentValues["ALLOW_VIDEO_MEETING"] != 'N')
{
	$arParams["VIDEO_MEETING_IBLOCK_ID"] = array(
		"PARENT" => "VIDEO_MEETING",
		"NAME" => GetMessage("EC_P_VIDEO_MEETING_IBLOCK"),
		"TYPE" => "LIST",
		"VALUES" => $arIBlock,
		"REFRESH" => "Y",
	);

	$arParams["PATH_TO_VIDEO_MEETING"] = array(
		"PARENT" => "VIDEO_MEETING",
		"NAME" => GetMessage("EC_P_PATH_TO_VIDEO_MEETING"),
		"DEFAULT" => "",
	);

	/* Access to Reserve Video-Meeting */
	$arUserGroups = array();
	$dbGroups = CGroup::GetList($b = "NAME", $o = "ASC", array("ACTIVE" => "Y"));
	while ($arGroup = $dbGroups->GetNext())
		$arUserGroups[$arGroup["ID"]] = "[".$arGroup["ID"]."] ".$arGroup["NAME"];

	$arParams["VIDEO_MEETING_USERGROUPS"] = array(
		"PARENT" => "VIDEO_MEETING",
		"NAME" => GetMessage("EC_P_VIDEO_MEETING_USERGROUPS"),
		"TYPE" => "LIST",
		"MULTIPLE" => "Y",
		"VALUES" => $arUserGroups,
		"DEFAULT" => Array(1)
	);
}


// New calendars
$calendar2 = COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule("calendar");

$arComponentParameters["PARAMETERS"] = $arParams;

if ($calendar2)
{
	$arComponentParameters["GROUPS"] = array(
		"BASE" => array("NAME" => GetMessage("EC_GROUP_BASE_SETTINGS"), "SORT" => "100")
	);

	$arParams["IBLOCK_TYPE"]["PARENT"] = "BASE";
	$arParams["IBLOCK_ID"]["PARENT"] = "BASE";
	$arParams["ALLOW_SUPERPOSE"]["PARENT"] = "BASE";
	$arParams["ALLOW_RES_MEETING"]["PARENT"] = "BASE";

	$arComponentParameters["PARAMETERS"] = array(
		'IBLOCK_TYPE' => $arParams["IBLOCK_TYPE"],
		'IBLOCK_ID' => $arParams["IBLOCK_ID"],
		'ALLOW_SUPERPOSE' => $arParams["ALLOW_SUPERPOSE"],
		'ALLOW_RES_MEETING' => $arParams["ALLOW_RES_MEETING"],
		'RESERVE_MEETING_READONLY_MODE'  => $arParams['RESERVE_MEETING_READONLY_MODE']
	);
}
?>
