<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("intranet"))
	return ShowError(GetMessage("EC_INTRANET_MODULE_NOT_INSTALLED"));
if (!CModule::IncludeModule("iblock"))
	return ShowError(GetMessage("EC_IBLOCK_MODULE_NOT_INSTALLED"));

$iblockId = IntVal($arParams["IBLOCK_ID"]);

$arParams["PAGE_VAR"] = Trim($arParams["PAGE_VAR"]);
if (StrLen($arParams["PAGE_VAR"]) <= 0)
	$arParams["PAGE_VAR"] = "page";

$arParams["MEETING_VAR"] = Trim($arParams["MEETING_VAR"]);
if (StrLen($arParams["MEETING_VAR"]) <= 0)
	$arParams["MEETING_VAR"] = "meeting_id";

$arParams["ITEM_VAR"] = Trim($arParams["ITEM_VAR"]);
if (StrLen($arParams["ITEM_VAR"]) <= 0)
	$arParams["ITEM_VAR"] = "item_id";

$meetingId = IntVal($arParams["MEETING_ID"]);
if ($meetingId <= 0)
	$meetingId = IntVal($_REQUEST[$arParams["MEETING_VAR"]]);

$arParams["PATH_TO_MEETING"] = Trim($arParams["PATH_TO_MEETING"]);
if (StrLen($arParams["PATH_TO_MEETING"]) <= 0)
	$arParams["PATH_TO_MEETING"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=meeting&".$arParams["MEETING_VAR"]."=#meeting_id#");

$arParams["PATH_TO_VIEW_ITEM"] = Trim($arParams["PATH_TO_VIEW_ITEM"]);
if (StrLen($arParams["PATH_TO_VIEW_ITEM"]) <= 0)
	$arParams["PATH_TO_VIEW_ITEM"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=view_item&".$arParams["MEETING_VAR"]."=#meeting_id#&".$arParams["ITEM_VAR"]."=#item_id#");

$arParams["PATH_TO_MEETING_LIST"] = Trim($arParams["PATH_TO_MEETING_LIST"]);
if (StrLen($arParams["PATH_TO_MEETING_LIST"]) <= 0)
	$arParams["PATH_TO_MEETING_LIST"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage());

$arParams["PATH_TO_RESERVE_MEETING"] = Trim($arParams["PATH_TO_RESERVE_MEETING"]);
if (StrLen($arParams["PATH_TO_RESERVE_MEETING"]) <= 0)
	$arParams["PATH_TO_RESERVE_MEETING"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=reserve_meeting&".$arParams["MEETING_VAR"]."=#meeting_id#&".$arParams["ITEM_VAR"]."=#item_id#");

if (!Is_Array($arParams["USERGROUPS_CLEAR"]))
{
	if (IntVal($arParams["USERGROUPS_CLEAR"]) > 0)
		$arParams["USERGROUPS_CLEAR"] = array($arParams["USERGROUPS_CLEAR"]);
	else
		$arParams["USERGROUPS_CLEAR"] = array();
}

if (!Is_Array($arParams["WEEK_HOLIDAYS"]))
{
	if (StrLen($arParams["WEEK_HOLIDAYS"]) > 0 && $arParams["WEEK_HOLIDAYS"] >= 0 && $arParams["WEEK_HOLIDAYS"] < 7)
		$arParams["WEEK_HOLIDAYS"] = array(IntVal($arParams["WEEK_HOLIDAYS"]));
	else
		$arParams["WEEK_HOLIDAYS"] = array(5, 6);
}

$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "Y" ? "Y" : "N");
$arParams["SET_NAVCHAIN"] = ($arParams["SET_NAVCHAIN"] == "Y" ? "Y" : "N");

$arParams['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'] ? $arParams['NAME_TEMPLATE'] : CSite::GetNameFormat(false);
$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;

$arParams["NAME_TEMPLATE_WO_NOBR"] = str_replace(
	array("#NOBR#", "#/NOBR#"),
	array("", ""),
	$arParams["NAME_TEMPLATE"]
);


$weekStart = Trim($_REQUEST["week_start"]);
if (StrLen($weekStart) > 0 && StrLen($weekStart) != 8)
{
	$weekStartTmp = MakeTimeStamp($weekStart, FORMAT_DATETIME);
	$weekStart = Date("Y", $weekStartTmp).Date("m", $weekStartTmp).Date("d", $weekStartTmp);
}
if (StrLen($weekStart) != 8)
	$weekStart = Date("Ymd");


$weekYear = IntVal(SubStr($weekStart, 0, 4));
$weekMonth = IntVal(SubStr($weekStart, 4, 2));
$weekDay = IntVal(SubStr($weekStart, 6, 2));

$weekTime = MkTime(0, 0, 0, $weekMonth, $weekDay, $weekYear);
if ($weekTime === false || $weekTime == -1)
	$weekTime = Time();

$weekYear = IntVal(Date("Y", $weekTime));
$weekMonth = IntVal(Date("m", $weekTime));
$weekDay = IntVal(Date("d", $weekTime));

$weekDoW = IntVal(Date("w", $weekTime));
if ($weekDoW == 0)
	$weekDoW = 7;

$weekDay = $weekDay - $weekDoW + 1;

$weekTimeStart = MkTime(0, 0, 0, $weekMonth, $weekDay, $weekYear);
$weekTimeEnd = MkTime(0, 0, 0, $weekMonth, $weekDay + 7, $weekYear);
$weekTimeEndPrint = MkTime(0, 0, 0, $weekMonth, $weekDay + 6, $weekYear);

$arResult["FatalError"] = "";

if (!CIBlockRights::UserHasRightTo($iblockId, $iblockId, 'element_read'))
	$arResult["FatalError"] .= GetMessage("INTS_NO_IBLOCK_PERMS").".";

if (StrLen($arResult["FatalError"]) <= 0)
{
	$arResult["ALLOWED_FIELDS"] = array(
		"ID" => array(
			"NAME" => GetMessage("INAF_F_ID"),
			"ORDERABLE" => true,
			"FILTERABLE" => true,
			"TYPE" => "int",
			"IS_FIELD" => true,
		),
		"NAME" => array(
			"NAME" => GetMessage("INAF_F_NAME"),
			"ORDERABLE" => true,
			"FILTERABLE" => true,
			"TYPE" => "string",
			"IS_FIELD" => true,
		),
		"DESCRIPTION" => array(
			"NAME" => GetMessage("INAF_F_DESCRIPTION"),
			"ORDERABLE" => false,
			"FILTERABLE" => false,
			"TYPE" => "text",
			"IS_FIELD" => true,
		),
		"UF_FLOOR" => array(
			"NAME" => GetMessage("INAF_F_FLOOR"),
			"ORDERABLE" => true,
			"FILTERABLE" => true,
			"TYPE" => "integer",
			"IS_FIELD" => false,
		),
		"UF_PLACE" => array(
			"NAME" => GetMessage("INAF_F_PLACE"),
			"ORDERABLE" => true,
			"FILTERABLE" => true,
			"TYPE" => "integer",
			"IS_FIELD" => false,
		),
		"UF_PHONE" => array(
			"NAME" => GetMessage("INAF_F_PHONE"),
			"ORDERABLE" => false,
			"FILTERABLE" => false,
			"TYPE" => "string",
			"IS_FIELD" => false,
		),
	);

	$arUserFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("IBLOCK_".$iblockId."_SECTION", 0, LANGUAGE_ID);

	$arKeys = Array_Keys($arResult["ALLOWED_FIELDS"]);
	foreach ($arKeys as $key)
	{
		if (!$arResult["ALLOWED_FIELDS"][$key]["IS_FIELD"])
		{
			if (!Array_Key_Exists($key, $arUserFields))
			{
				$arFields = Array(
					"ENTITY_ID" => "IBLOCK_".$iblockId."_SECTION",
					"FIELD_NAME" => $key,
					"USER_TYPE_ID" => $arResult["ALLOWED_FIELDS"][$key]["TYPE"],
				);

				$obUserField = new CUserTypeEntity;
				$obUserField->Add($arFields);
			}
		}
	}
}

if (StrLen($arResult["FatalError"]) <= 0)
{
	if ($arParams["SET_TITLE"] == "Y")
		$APPLICATION->SetTitle(GetMessage("INTASK_C36_PAGE_TITLE"));

	if ($arParams["SET_NAVCHAIN"] == "Y")
		$APPLICATION->AddChainItem(GetMessage("INTASK_C36_PAGE_TITLE1"), CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MEETING_LIST"], array()));

	$arSelectFields = array("IBLOCK_ID");
	foreach ($arResult["ALLOWED_FIELDS"] as $key => $value)
		$arSelectFields[] = $key;

	$dbMeeting = CIBlockSection::GetList(
		array(),
		array("ID" => $meetingId, "ACTIVE" => "Y", "IBLOCK_ID" => $iblockId),
		false,
		$arSelectFields
	);
	$arMeeting = $dbMeeting->GetNext();

	if (!$arMeeting)
		$arResult["FatalError"] = GetMessage("INAF_MEETING_NOT_FOUND");
}

if (StrLen($arResult["FatalError"]) <= 0)
{
	$clearId = IntVal($_REQUEST["clear_id"]);
	if ($clearId > 0 && check_bitrix_sessid())
	{
		$dbElements = CIBlockElement::GetList(
			array(),
			array(
				"IBLOCK_ID" => $arMeeting["IBLOCK_ID"],
				"SECTION_ID" => $arMeeting["ID"],
				"ID" => $clearId,
			),
			false,
			false,
			array("ID", "IBLOCK_ID", "CREATED_BY")
		);
		if ($arElement = $dbElements->GetNext())
		{
			if ($GLOBALS["USER"]->IsAuthorized()
				&& ($GLOBALS["USER"]->IsAdmin()
					|| Count(Array_Intersect($GLOBALS["USER"]->GetUserGroupArray(), $arParams["USERGROUPS_CLEAR"])) > 0
					|| $arElement["CREATED_BY"] == $GLOBALS["USER"]->GetID()))
			{
				CIBlockElement::Delete($arElement["ID"]);
			}
		}
	}
}

if (!Function_Exists("__RM_PrepateDate"))
{
	function __RM_PrepateDate($time, $startTime, $endTime)
	{
		if ($time < $startTime)
			$time = $startTime;
		if ($time > $endTime)
			$time = $endTime;

		if ($time >= $endTime)
		{
			$timeDoW = 8;
		}
		else
		{
			$timeDoW = IntVal(Date("w", $time));
			if ($timeDoW == 0)
				$timeDoW = 7;
		}

		$timeHour = IntVal(Date("H", $time));
		$timeMinute = IntVal(Date("i", $time));

		if ($timeMinute < 15)
		{
			$timeMinute = 0;
		}
		elseif ($timeMinute >= 15 && $timeMinute < 45)
		{
			$timeMinute = 30;
		}
		else
		{
			$timeHour++;
			$timeMinute = 0;
		}

		$time = MkTime($timeHour, $timeMinute, 0, Date("m", $time), Date("d", $time), Date("Y", $time));

		if ($time >= $endTime)
		{
			$timeDoW = 8;
		}
		else
		{
			$timeDoW = IntVal(Date("w", $time));
			if ($timeDoW == 0)
				$timeDoW = 7;
		}

		$timeHour = IntVal(Date("H", $time));
		$timeMinute = IntVal(Date("i", $time));

		return array("Time" => $time, "DayOfWeek" => $timeDoW, "Hour" => $timeHour, "Minute" => $timeMinute);
	}

	function __RM_MkT($i)
	{
		$aMpM = IsAmPmMode();
		$h1 = IntVal($i / 2);
		if ($aMpM)
		{
			if ($h1 >= 12)
			{
				$mt1 = 'pm';
				if ($h1 > 12)
				{
					$h1 -= 12;
				}
			}
			else
			{
				$mt1 = 'am';
			}
		}
		else
		{
			if ($h1 < 10)
			{
				$h1 = "0".$h1;
			}
		}

		
		$i1 = ($i % 2 != 0 ? "30" : "00");

		$h2 = IntVal(($i + 1) / 2);
		if ($aMpM)
		{
			if ($h2 >= 12)
			{
				$mt2 = 'pm';
				if ($h2 > 12)
				{
					$h2 -= 12;
				}
			}
			else
			{
				$mt2 = 'am';
			}
		}
		else
		{
			if ($h2 < 10)
			{
				$h2 = "0".$h2;
			}
		}

		$i2 = (($i + 1) % 2 != 0 ? "30" : "00");

		return $h1.":".$i1.(!empty($mt1) ? ' '.$mt1: '')."-".$h2.":".$i2.(!empty($mt2) ? ' '.$mt2: '');
	}
}

if (StrLen($arResult["FatalError"]) <= 0)
{
	$arResult["MEETING"] = $arMeeting;

	$arResult["CellClickUri"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_RESERVE_MEETING"], array("meeting_id" => $arMeeting["ID"], "item_id" => 0));
	$arResult["CellClickUri"] .= HtmlSpecialCharsbx(StrPos($arResult["CellClickUri"], "?") === false ? "?" : "&");

	if ($arParams["SET_TITLE"] == "Y")
		$APPLICATION->SetTitle(GetMessage("INTASK_C36_PAGE_TITLE").": ".$arMeeting["NAME"]);

	if ($arParams["SET_NAVCHAIN"] == "Y")
		$APPLICATION->AddChainItem($arMeeting["NAME"]);

	$arResult["WEEK_START"] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $weekTimeStart);
	$arResult["WEEK_END"] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $weekTimeEndPrint);
	$arResult["NEXT_WEEK"] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $weekTimeEnd);
	$arResult["PRIOR_WEEK"] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), MkTime(0, 0, 0, $weekMonth, $weekDay - 7, $weekYear));
	$arResult["WEEK_START_ARRAY"] = array("m" => $weekMonth, "d" => $weekDay, "Y" => $weekYear);

	$arResult["MEETING_URI"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MEETING"], array("meeting_id" => $arMeeting["ID"]));
	$fl = (StrPos($arResult["MEETING_URI"], "?") === false);
	$pwt = MkTime(0, 0, 0, $weekMonth, $weekDay - 7, $weekYear);
	$arResult["PRIOR_WEEK_URI"] = $arResult["MEETING_URI"].($fl ? "?" : "&")."week_start=".Date("Y", $pwt).Date("m", $pwt).Date("d", $pwt);
	$arResult["NEXT_WEEK_URI"] = $arResult["MEETING_URI"].($fl ? "?" : "&")."week_start=".Date("Y", $weekTimeEnd).Date("m", $weekTimeEnd).Date("d", $weekTimeEnd);


	$dbElements = CIBlockElement::GetList(
		array("DATE_ACTIVE_FROM" => "ASC"),
		array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $arMeeting["IBLOCK_ID"],
			"SECTION_ID" => $arMeeting["ID"],
			"<DATE_ACTIVE_FROM" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $weekTimeEnd),
			">=DATE_ACTIVE_TO" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $weekTimeStart),
			"PROPERTY_PERIOD_TYPE" => "NONE",
		),
		false,
		false,
		array()
	);

	$arResult["ITEMS"] = array();
	$arResult["ITEMS_MATRIX"] = array();
	$arResult["LIMITS"] = array("FROM" => 16, "TO" => 37);
	$arConflict = array();

	while ($arElement = $dbElements->GetNext())
	{
		$arElement["CREATED_BY_NAME"] = "-";
		$dbUser = CUser::GetByID($arElement["CREATED_BY"]);
		if ($arUser = $dbUser->GetNext())
		{
			$arElement["CREATED_BY_NAME"] = CUser::FormatName($arParams['NAME_TEMPLATE_WO_NOBR'], $arUser, $bUseLogin);
			$arElement["CREATED_BY_FIRST_NAME"] = $arUser["NAME"];
			$arElement["CREATED_BY_LAST_NAME"] = $arUser["LAST_NAME"];
			$arElement["CREATED_BY_SECOND_NAME"] = $arUser["SECOND_NAME"];
			$arElement["CREATED_BY_LOGIN"] = $arUser["LOGIN"];
		}

		if ($GLOBALS["USER"]->IsAuthorized()
			&& ($GLOBALS["USER"]->IsAdmin()
				|| Count(Array_Intersect($GLOBALS["USER"]->GetUserGroupArray(), $arParams["USERGROUPS_CLEAR"])) > 0
				|| $arElement["CREATED_BY"] == $GLOBALS["USER"]->GetID()))
		{
			$arElement["CLEAR_URI"] = $APPLICATION->GetCurPageParam("", array("clear_id"));
			$arElement["CLEAR_URI"] .= (StrPos($arElement["CLEAR_URI"], "?") === false ? "?" : "&")."clear_id=".$arElement["ID"]."&".bitrix_sessid_get();
		}

		$arElement["VIEW_ITEM_URI"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_VIEW_ITEM"], array("meeting_id" => $arMeeting["ID"], "item_id" => $arElement["ID"]));
		$arElement["VIEW_ITEM_URI"] .= (StrPos($arElement["VIEW_ITEM_URI"], "?") === false ? "?" : "&")."week_start=".UrlEncode($arResult["WEEK_START"]);

		if ($GLOBALS["USER"]->IsAuthorized()
			&& ($GLOBALS["USER"]->IsAdmin() || $arElement["CREATED_BY"] == $GLOBALS["USER"]->GetID()))
		{
			$arElement["EDIT_ITEM_URI"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_RESERVE_MEETING"], array("meeting_id" => $arMeeting["ID"], "item_id" => $arElement["ID"]));
			$arElement["EDIT_ITEM_URI"] .= (StrPos($arElement["EDIT_ITEM_URI"], "?") === false ? "?" : "&")."week_start=".UrlEncode($arResult["WEEK_START"]);
		}

		$arResult["ITEMS"][$arElement["ID"]] = $arElement;

		$from = $arElement["DATE_ACTIVE_FROM"];
		$to = $arElement["DATE_ACTIVE_TO"];

		$fromTime = MakeTimeStamp($from, FORMAT_DATETIME);
		$toTime = MakeTimeStamp($to, FORMAT_DATETIME);

		if (IsAmPmMode())
		{
			$arResult["ITEMS"][$arElement["ID"]]["DATE_ACTIVE_FROM_TIME"] = Date("g:i a", $fromTime);
			$arResult["ITEMS"][$arElement["ID"]]["DATE_ACTIVE_TO_TIME"] = Date("g:i a", $toTime);
		}
		else
		{
			$arResult["ITEMS"][$arElement["ID"]]["DATE_ACTIVE_FROM_TIME"] = Date("H:i", $fromTime);
			$arResult["ITEMS"][$arElement["ID"]]["DATE_ACTIVE_TO_TIME"] = Date("H:i", $toTime);
		}

		$from = __RM_PrepateDate($fromTime, $weekTimeStart, $weekTimeEnd);
		$to = __RM_PrepateDate($toTime, $weekTimeStart, $weekTimeEnd);

		if ($from["DayOfWeek"] == $to["DayOfWeek"])
		{
			$i1 = $from["Hour"] * 2;
			if ($from["Minute"] == 30)
				$i1++;

			$i2 = $to["Hour"] * 2;
			if ($to["Minute"] == 30)
				$i2++;

			if ($i1 < $arResult["LIMITS"]["FROM"])
				$arResult["LIMITS"]["FROM"] = $i1;
			if ($i2 > $arResult["LIMITS"]["TO"])
				$arResult["LIMITS"]["TO"] = $i2;

			for ($i = $i1; $i < $i2; $i++)
			{
				if ($arResult["ITEMS_MATRIX"][$from["DayOfWeek"]][$i])
				{
					$cId = $arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$from["DayOfWeek"]][$i]];
					if (!In_Array($arElement["ID"]."-".$cId["ID"], $arConflict))
					{
						$arResult["ErrorMessage"] .= Str_Replace(
							array("#TIME#", "#RES1#", "#RES2#"),
							array(
								Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $from["Time"])." ".__RM_MkT($i),
								$cId["NAME"],
								$arElement["NAME"],
							),
							GetMessage("INTASK_C25_CONFLICT1").". "
						);
						$arConflict[] = $arElement["ID"]."-".$cId["ID"];
					}
				}
				else
				{
					$arResult["ITEMS_MATRIX"][$from["DayOfWeek"]][$i] = $arElement["ID"];
				}
			}
		}
		else
		{
			for ($i = $from["DayOfWeek"]; $i <= $to["DayOfWeek"]; $i++)
			{
				if ($i == $from["DayOfWeek"])
				{
					$j1 = $from["Hour"] * 2;
					if ($from["Minute"] == 30)
						$j1++;
					$j2 = 48;

					if ($j1 < $arResult["LIMITS"]["FROM"])
						$arResult["LIMITS"]["FROM"] = $j1;
				}
				elseif ($i == $to["DayOfWeek"])
				{
					$j1 = 0;
					$j2 = $to["Hour"] * 2;
					if ($to["Minute"] == 30)
						$j2++;

					if ($j2 > $arResult["LIMITS"]["TO"])
						$arResult["LIMITS"]["TO"] = $j2;
				}
				else
				{
					$j1 = 0;
					$j2 = 48;
				}
				$arResult["LIMITS"]["FROM"] = 0;
				$arResult["LIMITS"]["TO"] = 48;

				for ($j = $j1; $j < $j2; $j++)
				{
					if ($arResult["ITEMS_MATRIX"][$i][$j])
					{
						$cId = $arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$i][$j]];
						if (!In_Array($arElement["ID"]."-".$cId["ID"], $arConflict))
						{
							$arResult["ErrorMessage"] .= Str_Replace(
								array("#TIME#", "#RES1#", "#RES2#"),
								array(
									Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), MkTime(0, 0, 0, $weekMonth, $weekDay + $i - 1, $weekYear))." ".__RM_MkT($j),
									$cId["NAME"],
									$arElement["NAME"],
								),
								GetMessage("INTASK_C25_CONFLICT2").". "
							);
							$arConflict[] = $arElement["ID"]."-".$cId["ID"];
						}
					}
					else
					{
						$arResult["ITEMS_MATRIX"][$i][$j] = $arElement["ID"];
					}
				}
			}
		}
	}

	// Period
	$arMonthlyPeriods = array();
	if (Date("n", $weekTimeStart) == Date("n", $weekTimeEnd))
		$arMonthlyPeriods = array(
			0 => array(
				"year" => Date("Y", $weekTimeStart),
				"month" => Date("n", $weekTimeStart),
				"from" => Date("j", $weekTimeStart),
				"to" => Date("j", $weekTimeEnd) - 1,
			),
		);
	else
		$arMonthlyPeriods = array(
			0 => array(
				"year" => Date("Y", $weekTimeStart),
				"month" => Date("n", $weekTimeStart),
				"from" => Date("j", $weekTimeStart),
				"to" => Date("t", $weekTimeStart),
			),
			1 => array(
				"year" => Date("Y", $weekTimeEnd),
				"month" => Date("n", $weekTimeEnd),
				"from" => 1,
				"to" => Date("j", $weekTimeEnd) - 1,
			),
		);

	$dbElements = CIBlockElement::GetList(
		array("DATE_ACTIVE_FROM" => "ASC"),
		array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $arMeeting["IBLOCK_ID"],
			"SECTION_ID" => $arMeeting["ID"],
			"<DATE_ACTIVE_FROM" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $weekTimeEnd),
			">=DATE_ACTIVE_TO" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $weekTimeStart),
			"!PROPERTY_PERIOD_TYPE" => "NONE",
		),
		false,
		false,
		array("ID", "IBLOCK_ID", "NAME", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO", "CREATED_BY", "PROPERTY_PERIOD_TYPE", "PROPERTY_PERIOD_COUNT", "PROPERTY_EVENT_LENGTH", "PROPERTY_PERIOD_ADDITIONAL")
	);

	while ($arElement = $dbElements->GetNext())
	{
		$arDates = array();

		$from = $arElement["DATE_ACTIVE_FROM"];
		$to = $arElement["DATE_ACTIVE_TO"];

		$fromTime = MakeTimeStamp($from, FORMAT_DATETIME);
		$toTime = MakeTimeStamp($to, FORMAT_DATETIME);

		$fromTimeDateOnly = MkTime(0, 0, 0, Date("n", $fromTime), Date("j", $fromTime), Date("Y", $fromTime));
		$toTimeDateOnly = MkTime(0, 0, 0, Date("n", $toTime), Date("j", $toTime), Date("Y", $toTime));

		if ($arElement["PROPERTY_PERIOD_TYPE_VALUE"] == "DAILY")
		{
			$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = IntVal($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
			if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

			if ($weekTimeStart > $fromTime || $weekTimeEnd <= $fromTime)
			{
				$dayDiff = date_diff(
					date_create_from_format('U', $weekTimeStart),
					date_create_from_format('U', $fromTimeDateOnly)
				);
				$dayShift = $dayDiff->format('%a') % $arElement["PROPERTY_PERIOD_COUNT_VALUE"];
				if ($dayShift > 0)
					$dayShift = $arElement["PROPERTY_PERIOD_COUNT_VALUE"] - $dayShift;

				$fromTimeTmp = MkTime(
					Date("H", $fromTime),
					Date("i", $fromTime),
					Date("s", $fromTime),
					Date("n", $weekTimeStart),
					Date("j", $weekTimeStart) + $dayShift,
					Date("Y", $weekTimeStart)
				);
			}
			else
			{
				$fromTimeTmp = $fromTime;
			}

			while ($fromTimeTmp < $weekTimeEnd && $fromTimeTmp < $toTime)
			{
				$toTimeTmp = $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"];
				$arDates[] = array(
					"DATE_ACTIVE_FROM" => $fromTimeTmp,
					"DATE_ACTIVE_TO" => $toTimeTmp,
				);

				$fromTimeTmp = strtotime(sprintf('+%u days', $arElement["PROPERTY_PERIOD_COUNT_VALUE"]), $fromTimeTmp);
			}
		}
		elseif ($arElement["PROPERTY_PERIOD_TYPE_VALUE"] == "WEEKLY")
		{
			$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = IntVal($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
			if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

			$arPeriodAdditional = array();
			if (StrLen($arElement["PROPERTY_PERIOD_ADDITIONAL_VALUE"]) > 0)
			{
				$arPeriodAdditionalTmp = Explode(",", $arElement["PROPERTY_PERIOD_ADDITIONAL_VALUE"]);
				foreach ($arPeriodAdditionalTmp as $v)
				{
					$v = IntVal($v);
					if ($v >= 0)
						$arPeriodAdditional[] = $v;
				}
			}
			if (Count($arPeriodAdditional) <= 0)
			{
				$w = Date("w", $fromTime);
				$arPeriodAdditional[] = ($w == 0 ? 6 : $w - 1);
			}

			if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] > 1)
			{
				if ($weekTimeStart > $fromTime || $weekTimeEnd <= $fromTime)
				{
					$wdw = IntVal(Date("w", $fromTime));
					if ($wdw == 0)
						$wdw = 7;

					$wd = Date("j", $fromTime) - $wdw + 1;
					$wts = MkTime(0, 0, 0, Date("n", $fromTime), $wd, Date("Y", $fromTime));

					$dayDiff = date_diff(
						date_create_from_format('U', $weekTimeStart),
						date_create_from_format('U', $wts)
					);
					$weekShift = $dayDiff->format('%a') / 7;

					if ($weekShift % $arElement["PROPERTY_PERIOD_COUNT_VALUE"] != 0)
						continue;
				}
			}

			foreach ($arPeriodAdditional as $w)
			{
				$fromTimeTmp = MkTime(Date("H", $fromTime), Date("i", $fromTime), Date("s", $fromTime), Date("n", $weekTimeStart), Date("j", $weekTimeStart) + $w, Date("Y", $weekTimeStart));

				if ($fromTime > $fromTimeTmp || $toTime < $fromTimeTmp)
					continue;

				$arDates[] = array(
					"DATE_ACTIVE_FROM" => $fromTimeTmp,
					"DATE_ACTIVE_TO" => $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"],
				);
			}
		}
		elseif ($arElement["PROPERTY_PERIOD_TYPE_VALUE"] == "MONTHLY")
		{
			$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = IntVal($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
			if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

			$arPeriod = false;
			$dm = Date("j", $fromTime);
			foreach ($arMonthlyPeriods as $arP)
			{
				if ($arP["from"] <= $dm && $arP["to"] >= $dm)
				{
					$arPeriod = $arP;
					break;
				}
			}

			if (!$arPeriod)
				continue;

			if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] > 1)
			{
				$nm = 0;
				if ($arPeriod["year"] == Date("Y", $fromTime))
				{
					$nm += $arPeriod["month"] - Date("n", $fromTime);
				}
				else
				{
					$nm += 12 - Date("n", $fromTime);
					if ($arPeriod["year"] != Date("Y", $fromTime) + 1)
						$nm += ($arPeriod["year"] - Date("Y", $fromTime) - 1) * 12;
					$nm += $arPeriod["month"];
				}

				if ($nm % $arElement["PROPERTY_PERIOD_COUNT_VALUE"] != 0)
					continue;
			}

			$fromTimeTmp = MkTime(Date("H", $fromTime), Date("i", $fromTime), Date("s", $fromTime), $arPeriod["month"], Date("j", $fromTime), $arPeriod["year"]);

			$arDates[] = array(
				"DATE_ACTIVE_FROM" => $fromTimeTmp,
				"DATE_ACTIVE_TO" => $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"],
			);
		}
		elseif ($arElement["PROPERTY_PERIOD_TYPE_VALUE"] == "YEARLY")
		{
			$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = IntVal($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
			if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

			$arPeriod = false;
			$dm = Date("j", $fromTime);
			$my = Date("n", $fromTime);
			foreach ($arMonthlyPeriods as $arP)
			{
				if ($my == $arP["month"] && $arP["from"] <= $dm && $arP["to"] >= $dm)
				{
					$arPeriod = $arP;
					break;
				}
			}

			if (!$arPeriod)
				continue;

			if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] > 1)
			{
				if (($arPeriod["year"] - Date("Y", $fromTime)) % $arElement["PROPERTY_PERIOD_COUNT_VALUE"] != 0)
					continue;
			}

			$fromTimeTmp = MkTime(Date("H", $fromTime), Date("i", $fromTime), Date("s", $fromTime), Date("n", $fromTime), Date("j", $fromTime), $arPeriod["year"]);

			$arDates[] = array(
				"DATE_ACTIVE_FROM" => $fromTimeTmp,
				"DATE_ACTIVE_TO" => $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"],
			);
		}

		$arElement["CREATED_BY_NAME"] = "-";
		$dbUser = CUser::GetByID($arElement["CREATED_BY"]);
		if ($arUser = $dbUser->GetNext())
		{
			$arElement["CREATED_BY_NAME"] = CUser::FormatName($arParams['NAME_TEMPLATE_WO_NOBR'], $arUser, $bUseLogin);
			$arElement["CREATED_BY_FIRST_NAME"] = $arUser["NAME"];
			$arElement["CREATED_BY_LAST_NAME"] = $arUser["LAST_NAME"];
			$arElement["CREATED_BY_SECOND_NAME"] = $arUser["SECOND_NAME"];
			$arElement["CREATED_BY_LOGIN"] = $arUser["LOGIN"];
		}

		if ($GLOBALS["USER"]->IsAuthorized()
			&& ($GLOBALS["USER"]->IsAdmin()
				|| Count(Array_Intersect($GLOBALS["USER"]->GetUserGroupArray(), $arParams["USERGROUPS_CLEAR"])) > 0
				|| $arElement["CREATED_BY"] == $GLOBALS["USER"]->GetID()))
		{
			$arElement["CLEAR_URI"] = $APPLICATION->GetCurPageParam("", array("clear_id"));
			$arElement["CLEAR_URI"] .= (StrPos($arElement["CLEAR_URI"], "?") === false ? "?" : "&")."clear_id=".$arElement["ID"]."&".bitrix_sessid_get();
		}

		$arElement["VIEW_ITEM_URI"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_VIEW_ITEM"], array("meeting_id" => $arMeeting["ID"], "item_id" => $arElement["ID"]));
		$arElement["VIEW_ITEM_URI"] .= (StrPos($arElement["VIEW_ITEM_URI"], "?") === false ? "?" : "&")."week_start=".UrlEncode($arResult["WEEK_START"]);

		if ($GLOBALS["USER"]->IsAuthorized()
			&& ($GLOBALS["USER"]->IsAdmin() || $arElement["CREATED_BY"] == $GLOBALS["USER"]->GetID()))
		{
			$arElement["EDIT_ITEM_URI"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_RESERVE_MEETING"], array("meeting_id" => $arMeeting["ID"], "item_id" => $arElement["ID"]));
			$arElement["EDIT_ITEM_URI"] .= (StrPos($arElement["EDIT_ITEM_URI"], "?") === false ? "?" : "&")."week_start=".UrlEncode($arResult["WEEK_START"]);
		}

		for ($counter = 0; $counter < Count($arDates); $counter++)
		{
			//echo Date("d.m.Y H:i:s", $arDates[$counter]["DATE_ACTIVE_FROM"])." - ".Date("d.m.Y H:i:s", $arDates[$counter]["DATE_ACTIVE_TO"])."<br>";

			$arResult["ITEMS"][$arElement["ID"]."-".$counter] = $arElement;

			$arResult["ITEMS"][$arElement["ID"]."-".$counter]["DATE_ACTIVE_FROM_TIME"] = Date("H:i", $arDates[$counter]["DATE_ACTIVE_FROM"]);
			$arResult["ITEMS"][$arElement["ID"]."-".$counter]["DATE_ACTIVE_TO_TIME"] = Date("H:i", $arDates[$counter]["DATE_ACTIVE_TO"]);

			$from = __RM_PrepateDate($arDates[$counter]["DATE_ACTIVE_FROM"], $weekTimeStart, $weekTimeEnd);
			$to = __RM_PrepateDate($arDates[$counter]["DATE_ACTIVE_TO"], $weekTimeStart, $weekTimeEnd);

			if ($from["DayOfWeek"] == $to["DayOfWeek"])
			{
				$i1 = $from["Hour"] * 2;
				if ($from["Minute"] == 30)
					$i1++;

				$i2 = $to["Hour"] * 2;
				if ($to["Minute"] == 30)
					$i2++;

				if ($i1 < $arResult["LIMITS"]["FROM"])
					$arResult["LIMITS"]["FROM"] = $i1;
				if ($i2 > $arResult["LIMITS"]["TO"])
					$arResult["LIMITS"]["TO"] = $i2;

				for ($i = $i1; $i < $i2; $i++)
				{
					if ($arResult["ITEMS_MATRIX"][$from["DayOfWeek"]][$i])
					{
						$cId = $arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$from["DayOfWeek"]][$i]];
						if (!In_Array($arElement["ID"]."-".$counter."-".$cId["ID"], $arConflict))
						{
							$arResult["ErrorMessage"] .= Str_Replace(
								array("#TIME#", "#RES1#", "#RES2#"),
								array(
									Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $from["Time"])." ".__RM_MkT($i),
									$cId["NAME"],
									$arElement["NAME"],
								),
								GetMessage("INTASK_C25_CONFLICT1").". "
							);
							$arConflict[] = $arElement["ID"]."-".$counter."-".$cId["ID"];
						}
					}
					else
					{
						$arResult["ITEMS_MATRIX"][$from["DayOfWeek"]][$i] = $arElement["ID"]."-".$counter;
					}
				}
			}
			else
			{
				for ($i = $from["DayOfWeek"]; $i <= $to["DayOfWeek"]; $i++)
				{
					if ($i == $from["DayOfWeek"])
					{
						$j1 = $from["Hour"] * 2;
						if ($from["Minute"] == 30)
							$j1++;
						$j2 = 48;

						if ($j1 < $arResult["LIMITS"]["FROM"])
							$arResult["LIMITS"]["FROM"] = $j1;
					}
					elseif ($i == $to["DayOfWeek"])
					{
						$j1 = 0;
						$j2 = $to["Hour"] * 2;
						if ($to["Minute"] == 30)
							$j2++;

						if ($j2 > $arResult["LIMITS"]["TO"])
							$arResult["LIMITS"]["TO"] = $j2;
					}
					else
					{
						$j1 = 0;
						$j2 = 48;
					}
					$arResult["LIMITS"]["FROM"] = 0;
					$arResult["LIMITS"]["TO"] = 48;

					for ($j = $j1; $j < $j2; $j++)
					{
						if ($arResult["ITEMS_MATRIX"][$i][$j])
						{
							$cId = $arResult["ITEMS"][$arResult["ITEMS_MATRIX"][$i][$j]];
							if (!In_Array($arElement["ID"]."-".$counter."-".$cId["ID"], $arConflict))
							{
								$arResult["ErrorMessage"] .= Str_Replace(
									array("#TIME#", "#RES1#", "#RES2#"),
									array(
										Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), MkTime(0, 0, 0, $weekMonth, $weekDay + $i - 1, $weekYear))." ".__RM_MkT($j),
										$cId["NAME"],
										$arElement["NAME"],
									),
									GetMessage("INTASK_C25_CONFLICT2").". "
								);
								$arConflict[] = $arElement["ID"]."-".$counter."-".$cId["ID"];
							}
						}
						else
						{
							$arResult["ITEMS_MATRIX"][$i][$j] = $arElement["ID"]."-".$counter;
						}
					}
				}
			}
		}
	}
	// End Period

	$ar = array();
	foreach ($arParams["WEEK_HOLIDAYS"] as $v)
	{
		if (!Array_Key_Exists($v + 1, $arResult["ITEMS_MATRIX"]))
			$ar[] = $v;
	}
	$arParams["WEEK_HOLIDAYS"] = $ar;
}

//echo "<pre>".print_r($arResult, true)."</pre>";

$this->IncludeComponentTemplate();
?>