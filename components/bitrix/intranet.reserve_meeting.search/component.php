<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("intranet"))
{
	ShowError(GetMessage("EC_INTRANET_MODULE_NOT_INSTALLED"));
	return;
}
if (!CModule::IncludeModule("iblock"))
{
	ShowError(GetMessage("EC_IBLOCK_MODULE_NOT_INSTALLED"));
	return;
}

$iblockId = Trim($arParams["IBLOCK_ID"]);

if (intval($iblockId) <= 0)
{
	ShowError(GetMessage("EC_IBLOCK_ID_EMPTY"));
	return;
}

$arParams["PAGE_VAR"] = Trim($arParams["PAGE_VAR"]);
if ($arParams["PAGE_VAR"] == '')
	$arParams["PAGE_VAR"] = "page";

$arParams["MEETING_VAR"] = Trim($arParams["MEETING_VAR"]);
if ($arParams["MEETING_VAR"] == '')
	$arParams["MEETING_VAR"] = "meeting_id";

$arParams["PATH_TO_MEETING"] = Trim($arParams["PATH_TO_MEETING"]);
if ($arParams["PATH_TO_MEETING"] == '')
	$arParams["PATH_TO_MEETING"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=meeting&".$arParams["MEETING_VAR"]."=#meeting_id#");

$arParams["PATH_TO_RESERVE_MEETING"] = Trim($arParams["PATH_TO_RESERVE_MEETING"]);
if ($arParams["PATH_TO_RESERVE_MEETING"] == '')
	$arParams["PATH_TO_RESERVE_MEETING"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=reserve_meeting&".$arParams["MEETING_VAR"]."=#meeting_id#&".$arParams["ITEM_VAR"]."=#item_id#");

$arParams["ITEMS_COUNT"] = intval($arParams["ITEMS_COUNT"]);
if ($arParams["ITEMS_COUNT"] <= 0)
	$arParams["ITEMS_COUNT"] = 20;

$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "Y" ? "Y" : "N");
$arParams["SET_NAVCHAIN"] = ($arParams["SET_NAVCHAIN"] == "Y" ? "Y" : "N");

$arResult["FatalError"] = "";

if (!CIBlockRights::UserHasRightTo($iblockId, $iblockId, 'element_read'))
	$arResult["FatalError"] .= GetMessage("INTS_NO_IBLOCK_PERMS").".";

include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/components/bitrix/intranet.reserve_meeting/init.php");

$ar = __IRM_InitReservation($iblockId);
$arResult["ALLOWED_FIELDS"] = $ar["ALLOWED_FIELDS"];
$arResult["ALLOWED_ITEM_PROPERTIES"] = $ar["ALLOWED_ITEM_PROPERTIES"];

if ($arParams["SET_TITLE"] == "Y")
	$APPLICATION->SetTitle(GetMessage("INTASK_C36_PAGE_TITLE"));

if ($arParams["SET_NAVCHAIN"] == "Y")
	$APPLICATION->AddChainItem(GetMessage("INTASK_C36_PAGE_TITLE"));

if ($arResult["FatalError"] == '')
{
	$arFilter = array("IBLOCK_ID" => $iblockId, "ACTIVE" => "Y");

	foreach ($_REQUEST as $key => $value)
	{
		if (mb_strtoupper(mb_substr($key, 0, 4)) != "FLT_")
			continue;
		if (!Is_Array($value) && $value == '' || Is_Array($value) && Count($value) <= 0)
			continue;

		$key = mb_strtoupper(mb_substr($key, 4));

		$op = "";
		$opTmp = mb_substr($key, 0, 1);
		if (In_Array($opTmp, array("!", "<", ">")))
		{
			$op = $opTmp;
			$key = mb_substr($key, 1);
		}

		if ($key == "UF_PLACE")
		{
			if (!is_numeric($value))
				continue;

			$op = ">";
			$value = $value - 1;
		}

		if (Array_Key_Exists($key, $arResult["ALLOWED_FIELDS"]) && $arResult["ALLOWED_FIELDS"][$key]["FILTERABLE"])
		{
			if ($arResult["ALLOWED_FIELDS"][$key]["TYPE"] == "datetime")
			{
				if ($value == "current")
					$arFilter[$op.$key] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE));
				else
					$arFilter[$op.$key] = $value;
			}
			elseif ($arResult["ALLOWED_FIELDS"][$key]["TYPE"] == "user")
			{
				if ($value == "current")
					$arFilter[$op.$key] = $GLOBALS["USER"]->GetID();
				else
					$arFilter[$op.$key] = $value;
			}
			else
			{
				$arFilter[$op.$key] = $value;
			}
		}
	}

	$arSelectFields = array("IBLOCK_ID");
	foreach ($arResult["ALLOWED_FIELDS"] as $key => $value)
		$arSelectFields[] = $key;

	$arResult["MEETINGS_LIST"] = array();
	$arMeetingId = array();

	$dbMeetingsList = CIBlockSection::GetList(
		array(),
		$arFilter,
		false,
		$arSelectFields
	);
	while ($arMeeting = $dbMeetingsList->GetNext())
	{
		$arMeeting["URI"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MEETING"], array("meeting_id" => $arMeeting["ID"]));

		$arResult["MEETINGS_LIST"][$arMeeting["ID"]] = $arMeeting;
		$arMeetingId[] = $arMeeting["ID"];
	}
	
	if (array_key_exists(">UF_PLACE", $arFilter))
	{
		unset($arFilter[">UF_PLACE"]);
		$arFilter["UF_PLACE"] = false;

		$dbMeetingsList = CIBlockSection::GetList(
			array(),
			$arFilter,
			false,
			$arSelectFields
		);
		while ($arMeeting = $dbMeetingsList->GetNext())
		{
			$arMeeting["URI"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MEETING"], array("meeting_id" => $arMeeting["ID"]));

			$arResult["MEETINGS_LIST"][$arMeeting["ID"]] = $arMeeting;
			$arMeetingId[] = $arMeeting["ID"];
		}
	}
}

if ($arResult["FatalError"] == '')
{
	$fltDateFrom = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE));
	$fltDateTo = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE));
	$fltTimeFrom = "08:00";
	$fltTimeTo = "19:00";
	$fltDuration = "0.5";

	foreach ($_REQUEST as $key => $value)
	{
		if (mb_strtoupper(mb_substr($key, 0, 4)) != "FLT_")
			continue;
		if (Is_Array($value) || $value == '')
			continue;

		$key = mb_strtoupper(mb_substr($key, 4));

		switch ($key)
		{
			case "DATE_FROM":
				$fltDateFromTmp = MakeTimeStamp($value, FORMAT_DATE);
				if ($fltDateFromTmp)
					$fltDateFrom = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $fltDateFromTmp);
				break;
			case "DATE_TO":
				$fltDateToTmp = MakeTimeStamp($value, FORMAT_DATE);
				if ($fltDateToTmp)
					$fltDateTo = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $fltDateToTmp);
				break;
			case "TIME_FROM":
				$fltTimeFromTmp = Explode(":", $value);
				if (Count($fltTimeFromTmp) == 2 && intval($fltTimeFromTmp[0]) >= 0 && intval($fltTimeFromTmp[0]) < 24 && ($fltTimeFromTmp[1] == "00" || $fltTimeFromTmp[1] == "30"))
					$fltTimeFrom = $value;
				break;
			case "TIME_TO":
				$fltTimeToTmp = Explode(":", $value);
				if (Count($fltTimeToTmp) == 2 && intval($fltTimeToTmp[0]) >= 0 && intval($fltTimeToTmp[0]) < 24 && ($fltTimeToTmp[1] == "00" || $fltTimeToTmp[1] == "30"))
					$fltTimeTo = $value;
				break;
			case "DURATION":
				$fltDurationTmp = Explode(".", $value);
				if (Count($fltDurationTmp) == 2 && intval($fltDurationTmp[0]) >= 0 && intval($fltDurationTmp[0]) < 24 && ($fltDurationTmp[1] == "0" || $fltDurationTmp[1] == "5"))
					$fltDuration = $value;
				break;
		}
	}

	$arFltTimeFrom = Explode(":", $fltTimeFrom);
	$arFltTimeTo = Explode(":", $fltTimeTo);

	$fltDateTimeFromTmp = MakeTimeStamp($fltDateFrom, FORMAT_DATE);
	$fltDateTimeFrom = MkTime($arFltTimeFrom[0], $arFltTimeFrom[1], 0, Date("m", $fltDateTimeFromTmp), Date("d", $fltDateTimeFromTmp), Date("Y", $fltDateTimeFromTmp));

	$fltDateTimeToTmp = MakeTimeStamp($fltDateTo, FORMAT_DATE);
	$fltDateTimeTo = MkTime($arFltTimeTo[0], $arFltTimeTo[1], 0, Date("m", $fltDateTimeToTmp), Date("d", $fltDateTimeToTmp), Date("Y", $fltDateTimeToTmp));

	$fltDurationDbl = DoubleVal($fltDuration);

	if (Count($arMeetingId) > 0)
	{
		// Period
		$arWeeklyPeriods = array();
		$arMonthlyPeriods = array();
		$arYearlyPeriods = array();

		$n = intval(Round(($fltDateTimeToTmp - $fltDateTimeFromTmp) / 86400));
		$arWeeklyPeriods[0] = array(
			"year" => Date("Y", $fltDateTimeFromTmp),
			"monthFrom" => Date("n", $fltDateTimeFromTmp),
			"dayFrom" => Date("j", $fltDateTimeFromTmp),
			"week" => Date("W", $fltDateTimeFromTmp),
			"weekDayFrom" => (Date("w", $fltDateTimeFromTmp) == 0 ? 6 : Date("w", $fltDateTimeFromTmp) - 1),
			"weekTimeStart" => MkTime(0, 0, 0, Date("n", $fltDateTimeFromTmp), Date("j", $fltDateTimeFromTmp) - (Date("w", $fltDateTimeFromTmp) == 0 ? 7 : Date("w", $fltDateTimeFromTmp)) + 1, Date("Y", $fltDateTimeFromTmp)),
		);

		$arMonthlyPeriods[0] = array(
			"year" => Date("Y", $fltDateTimeFromTmp),
			"month" => Date("n", $fltDateTimeFromTmp),
			"dayFrom" => Date("j", $fltDateTimeFromTmp),
		);
		$arYearlyPeriods[0] = array(
			"year" => Date("Y", $fltDateTimeFromTmp),
			"monthFrom" => Date("n", $fltDateTimeFromTmp),
			"dayFrom" => Date("j", $fltDateTimeFromTmp),
		);
		if ($n < 1)
		{
			$arWeeklyPeriods[0]["monthTo"] = $arWeeklyPeriods[0]["monthFrom"];
			$arWeeklyPeriods[0]["dayTo"] = $arWeeklyPeriods[0]["dayFrom"];
			$arWeeklyPeriods[0]["weekDayTo"] = $arWeeklyPeriods[0]["weekDayFrom"];
			$arWeeklyPeriods[0]["weekTimeEnd"] = MkTime(0, 0, 0, Date("n", $fltDateTimeFromTmp), Date("j", $fltDateTimeFromTmp) - (Date("w", $fltDateTimeFromTmp) == 0 ? 7 : Date("w", $fltDateTimeFromTmp)) + 1 + 7, Date("Y", $fltDateTimeFromTmp));

			$arMonthlyPeriods[0]["dayTo"] = $arMonthlyPeriods[0]["dayFrom"];

			$arYearlyPeriods[0]["monthTo"] = $arYearlyPeriods[0]["monthFrom"];
			$arYearlyPeriods[0]["dayTo"] = $arYearlyPeriods[0]["dayFrom"];
		}
		else
		{
			$jY = 0;
			$jM = 0;
			$jW = 0;
			for ($i = 1; $i < $n; $i++)
			{
				$t = MkTime(0, 0, 0, Date("n", $fltDateTimeFromTmp), Date("j", $fltDateTimeFromTmp) + $i, Date("Y", $fltDateTimeFromTmp));

				if (Date("Y", $t) != $arYearlyPeriods[$jY]["year"])
				{
					$t1 = MkTime(0, 0, 0, Date("n", $fltDateTimeFromTmp), Date("j", $fltDateTimeFromTmp) + $i - 1, Date("Y", $fltDateTimeFromTmp));
					$arYearlyPeriods[$jY]["monthTo"] = Date("n", $t1);
					$arYearlyPeriods[$jY]["dayTo"] = Date("j", $t1);

					$jY++;

					$arYearlyPeriods[$jY] = array(
						"year" => Date("Y", $t),
						"monthFrom" => Date("n", $t),
						"dayFrom" => Date("j", $t),
					);
				}				
				if (Date("n", $t) != $arMonthlyPeriods[$jM]["month"])
				{
					$t1 = MkTime(0, 0, 0, Date("n", $fltDateTimeFromTmp), Date("j", $fltDateTimeFromTmp) + $i - 1, Date("Y", $fltDateTimeFromTmp));
					$arMonthlyPeriods[$jM]["dayTo"] = Date("j", $t1);

					$jM++;

					$arMonthlyPeriods[$jM] = array(
						"year" => Date("Y", $t),
						"month" => Date("n", $t),
						"dayFrom" => Date("j", $t),
					);
				}
				if (Date("W", $t) != $arWeeklyPeriods[$jW]["week"])
				{
					$t1 = MkTime(0, 0, 0, Date("n", $fltDateTimeFromTmp), Date("j", $fltDateTimeFromTmp) + $i - 1, Date("Y", $fltDateTimeFromTmp));
					$arWeeklyPeriods[$jW]["monthTo"] = Date("n", $t1);
					$arWeeklyPeriods[$jW]["dayTo"] = Date("j", $t1);
					$arWeeklyPeriods[$jW]["weekDayTo"] = (Date("w", $t1) == 0 ? 6 : Date("w", $t1) - 1);;
					$arWeeklyPeriods[$jW]["weekTimeEnd"] = MkTime(0, 0, 0, Date("n", $t1), Date("j", $t1) - (Date("w", $t1) == 0 ? 7 : Date("w", $t1)) + 1 + 7, Date("Y", $t1));

					$jW++;

					$arWeeklyPeriods[$jW] = array(
						"year" => Date("Y", $t),
						"monthFrom" => Date("n", $t),
						"dayFrom" => Date("j", $t),
						"week" => Date("W", $t),
						"weekDayFrom" => (Date("w", $t) == 0 ? 6 : Date("w", $t) - 1),
						"weekTimeStart" => MkTime(0, 0, 0, Date("n", $t), Date("j", $t) - (Date("w", $t) == 0 ? 7 : Date("w", $t)) + 1, Date("Y", $t)),
					);
				}
			}

			$t1 = MkTime(0, 0, 0, Date("n", $fltDateTimeToTmp), Date("j", $fltDateTimeToTmp), Date("Y", $fltDateTimeToTmp));

			$arWeeklyPeriods[$jW]["monthTo"] = Date("n", $t1);
			$arWeeklyPeriods[$jW]["dayTo"] = Date("j", $t1);
			$arWeeklyPeriods[$jW]["weekDayTo"] = (Date("w", $t1) == 0 ? 6 : Date("w", $t1) - 1);
			$arWeeklyPeriods[$jW]["weekTimeEnd"] = MkTime(0, 0, 0, Date("n", $t1), Date("j", $t1) - (Date("w", $t1) == 0 ? 7 : Date("w", $t1)) + 1 + 7, Date("Y", $t1));

			$arMonthlyPeriods[$jM]["dayTo"] = Date("j", $t1);

			$arYearlyPeriods[$jY]["monthTo"] = Date("n", $t1);
			$arYearlyPeriods[$jY]["dayTo"] = Date("j", $t1);
		}

		$arPeriodElements = array();

		$dbElements = CIBlockElement::GetList(
			array("DATE_ACTIVE_FROM" => "ASC"),
			array(
				"ACTIVE" => "Y",
				"IBLOCK_ID" => $iblockId,
				"SECTION_ID" => $arMeetingId,
				"<DATE_ACTIVE_FROM" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), MkTime(0, 0, 0, Date("m", $fltDateTimeToTmp), Date("d", $fltDateTimeToTmp) + 1, Date("Y", $fltDateTimeToTmp))),
				">DATE_ACTIVE_TO" => $fltDateFrom,
				"!PROPERTY_PERIOD_TYPE" => "NONE",
			),
			false,
			false,
			array("ID", "IBLOCK_ID", "NAME", "DATE_ACTIVE_FROM", "IBLOCK_SECTION_ID", "DATE_ACTIVE_TO", "CREATED_BY", "PROPERTY_PERIOD_TYPE", "PROPERTY_PERIOD_COUNT", "PROPERTY_EVENT_LENGTH", "PROPERTY_PERIOD_ADDITIONAL")
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
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = intval($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
				if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
					$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

				if ($fltDateTimeFromTmp > $fromTime || $fltDateTimeToTmp <= $fromTime)
				{
					$dayShift = (intval(Round(($fltDateTimeFromTmp - $fromTimeDateOnly) / 86400)) % $arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
					if ($dayShift > 0)
						$dayShift = $arElement["PROPERTY_PERIOD_COUNT_VALUE"] - $dayShift;

					$fromTimeTmp = MkTime(Date("H", $fromTime), Date("i", $fromTime), Date("s", $fromTime), Date("n", $fltDateTimeFromTmp), Date("j", $fltDateTimeFromTmp) + $dayShift, Date("Y", $fltDateTimeFromTmp));
				}
				else
				{
					$fromTimeTmp = $fromTime;
				}

				while ($fromTimeTmp < $fltDateTimeToTmp + 86400 && $fromTimeTmp < $toTime)
				{
					$toTimeTmp = $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"];
					$arDates[] = array(
						"DATE_ACTIVE_FROM" => $fromTimeTmp,
						"DATE_ACTIVE_TO" => $toTimeTmp,
					);

					$fromTimeTmp = $fromTimeTmp + 86400 * $arElement["PROPERTY_PERIOD_COUNT_VALUE"];
				}
			}
			elseif ($arElement["PROPERTY_PERIOD_TYPE_VALUE"] == "WEEKLY")
			{
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = intval($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
				if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
					$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

				$arPeriodAdditional = array();
				if ($arElement["PROPERTY_PERIOD_ADDITIONAL_VALUE"] <> '')
				{
					$arPeriodAdditionalTmp = Explode(",", $arElement["PROPERTY_PERIOD_ADDITIONAL_VALUE"]);
					foreach ($arPeriodAdditionalTmp as $v)
					{
						$v = intval($v);
						if ($v >= 0)
							$arPeriodAdditional[] = $v;
					}
				}
				if (Count($arPeriodAdditional) <= 0)
				{
					$w = Date("w", $fromTime);
					$arPeriodAdditional[] = ($w == 0 ? 6 : $w - 1);
				}

				foreach ($arWeeklyPeriods as $arPeriod)
				{
					if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] > 1)
					{
						if ($arPeriod["weekTimeStart"] > $fromTime || $arPeriod["weekTimeEnd"] <= $fromTime)
						{
							$wdw = intval(Date("w", $fromTime));
							if ($wdw == 0)
								$wdw = 7;

							$wd = Date("j", $fromTime) - $wdw + 1;
							$wts = MkTime(0, 0, 0, Date("n", $fromTime), $wd, Date("Y", $fromTime));

							$weekShift = intval(Round(($arPeriod["weekTimeStart"] - $wts) / 604800));
							if ($weekShift % $arElement["PROPERTY_PERIOD_COUNT_VALUE"] != 0)
								continue;
						}
					}

					foreach ($arPeriodAdditional as $w)
					{
						if ($w >= $arPeriod["weekDayFrom"] && $w <= $arPeriod["weekDayTo"])
						{
							$fromTimeTmp = MkTime(Date("H", $fromTime), Date("i", $fromTime), Date("s", $fromTime), Date("n", $arPeriod["weekTimeStart"]), Date("j", $arPeriod["weekTimeStart"]) + $w, Date("Y", $arPeriod["weekTimeStart"]));

							if ($fromTime > $fromTimeTmp)
								continue;

							$arDates[] = array(
								"DATE_ACTIVE_FROM" => $fromTimeTmp,
								"DATE_ACTIVE_TO" => $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"],
							);
						}
					}
				}
			}
			elseif ($arElement["PROPERTY_PERIOD_TYPE_VALUE"] == "MONTHLY")
			{
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = intval($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
				if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
					$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

				foreach ($arMonthlyPeriods as $arPeriod)
				{
					$dm = Date("j", $fromTime);
					if ($arPeriod["dayFrom"] > $dm || $arPeriod["dayTo"] < $dm)
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
			}
			elseif ($arElement["PROPERTY_PERIOD_TYPE_VALUE"] == "YEARLY")
			{
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = intval($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
				if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
					$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

				foreach ($arYearlyPeriods as $arPeriod)
				{
					$dm = Date("j", $fromTime);
					$my = Date("n", $fromTime);
					if ($my < $arPeriod["monthFrom"] || $my > $arPeriod["monthTo"])
						continue;

					if ($my == $arPeriod["monthFrom"] && $dm < $arPeriod["dayFrom"]
						|| $my == $arPeriod["monthTo"] && $dm > $arPeriod["dayTo"])
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
			}

			foreach ($arDates as $d)
			{
				$arElement["DATE_ACTIVE_FROM_TIME"] = $d["DATE_ACTIVE_FROM"];
				$arElement["DATE_ACTIVE_TO_TIME"] = $d["DATE_ACTIVE_TO"];
				$arElement["DATE_ACTIVE_FROM"] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $d["DATE_ACTIVE_FROM"]);
				$arElement["DATE_ACTIVE_TO"] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $d["DATE_ACTIVE_TO"]);
				$arPeriodElements[] = $arElement;
			}
		}

		if (Count($arPeriodElements) > 0)
		{
			for ($i = 0; $i < Count($arPeriodElements) - 1; $i++)
			{
				for ($j = $i + 1; $j < Count($arPeriodElements); $j++)
				{
					if ($arPeriodElements[$i]["DATE_ACTIVE_FROM_TIME"] > $arPeriodElements[$j]["DATE_ACTIVE_FROM_TIME"])
					{
						$t = $arPeriodElements[$i];
						$arPeriodElements[$i] = $arPeriodElements[$j];
						$arPeriodElements[$j] = $t;
					}
				}
			}
		}

		// End Period

		$arSimpleElements = array();

		$dbElements = CIBlockElement::GetList(
			array("DATE_ACTIVE_FROM" => "ASC"),
			array(
				"ACTIVE" => "Y",
				"IBLOCK_ID" => $iblockId,
				"SECTION_ID" => $arMeetingId,
				"<DATE_ACTIVE_FROM" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), MkTime(0, 0, 0, Date("m", $fltDateTimeToTmp), Date("d", $fltDateTimeToTmp) + 1, Date("Y", $fltDateTimeToTmp))),
				">DATE_ACTIVE_TO" => $fltDateFrom,
				"PROPERTY_PERIOD_TYPE" => "NONE",
			),
			false,
			false,
			array()
		);
		while ($arElement = $dbElements->GetNext())
		{
			$arElement["DATE_ACTIVE_FROM_TIME"] =  MakeTimeStamp($arElement["DATE_ACTIVE_FROM"], FORMAT_DATETIME);
			$arElement["DATE_ACTIVE_TO_TIME"] = MakeTimeStamp($arElement["DATE_ACTIVE_TO"], FORMAT_DATETIME);
			$arSimpleElements[] = $arElement;
		}

		$i1 = 0;
		$i2 = 0;
		$n1 = Count($arSimpleElements);
		$n2 = Count($arPeriodElements);

		$arIterator = array();
		while ($i1 < $n1 || $i2 < $n2)
		{
			if ($i1 < $n1 && $i2 < $n2)
			{
				if ($arSimpleElements[$i1]["DATE_ACTIVE_FROM_TIME"] < $arPeriodElements[$i2]["DATE_ACTIVE_FROM_TIME"])
				{
					$arElement = $arSimpleElements[$i1];
					$i1++;
				}
				else
				{
					$arElement = $arPeriodElements[$i2];
					$i2++;
				}
			}
			elseif ($i1 >= $n1)
			{
				$arElement = $arPeriodElements[$i2];
				$i2++;
			}
			else
			{
				$arElement = $arSimpleElements[$i1];
				$i1++;
			}

			if (!Array_Key_Exists("TIME_ITEMS", $arResult["MEETINGS_LIST"][$arElement["IBLOCK_SECTION_ID"]]))
			{
				$arResult["MEETINGS_LIST"][$arElement["IBLOCK_SECTION_ID"]]["TIME_ITEMS"] = array();
				$arIterator[$arElement["IBLOCK_SECTION_ID"]] = $fltDateTimeFrom;
			}

			$resTimeFrom = $arElement["DATE_ACTIVE_FROM_TIME"];
			$resTimeTo = $arElement["DATE_ACTIVE_TO_TIME"];

			if ($arIterator[$arElement["IBLOCK_SECTION_ID"]] < $resTimeFrom)
			{
				if (($resTimeFrom - $arIterator[$arElement["IBLOCK_SECTION_ID"]]) / 3600.0 >= $fltDurationDbl)
				{
					if (Date("Y-m-d", $resTimeFrom) == Date("Y-m-d", $arIterator[$arElement["IBLOCK_SECTION_ID"]]))
					{
						if ($resTimeFrom > MkTime($arFltTimeTo[0], $arFltTimeTo[1], 0, Date("m", $resTimeFrom), Date("d", $resTimeFrom), Date("Y", $resTimeFrom)))
							$resTimeFrom = MkTime($arFltTimeTo[0], $arFltTimeTo[1], 0, Date("m", $resTimeFrom), Date("d", $resTimeFrom), Date("Y", $resTimeFrom));

						if ($arIterator[$arElement["IBLOCK_SECTION_ID"]] < $resTimeFrom
							&& ($resTimeFrom - $arIterator[$arElement["IBLOCK_SECTION_ID"]]) / 3600.0 >= $fltDurationDbl)
						{
							$arResult["MEETINGS_LIST"][$arElement["IBLOCK_SECTION_ID"]]["TIME_ITEMS"][] = array(
								"FROM" => $arIterator[$arElement["IBLOCK_SECTION_ID"]],
								"TO" => $resTimeFrom,
							);
						}
					}
					else
					{
						while ($arIterator[$arElement["IBLOCK_SECTION_ID"]] < $resTimeFrom)
						{
							$resTimeFromTmp = MkTime($arFltTimeTo[0], $arFltTimeTo[1], 0, Date("m", $arIterator[$arElement["IBLOCK_SECTION_ID"]]), Date("d", $arIterator[$arElement["IBLOCK_SECTION_ID"]]), Date("Y", $arIterator[$arElement["IBLOCK_SECTION_ID"]]));
							if ($resTimeFromTmp > $resTimeFrom)
								$resTimeFromTmp = $resTimeFrom;

							if ($arIterator[$arElement["IBLOCK_SECTION_ID"]] < $resTimeFromTmp
								&& ($resTimeFromTmp - $arIterator[$arElement["IBLOCK_SECTION_ID"]]) / 3600.0 >= $fltDurationDbl)
							{
								$arResult["MEETINGS_LIST"][$arElement["IBLOCK_SECTION_ID"]]["TIME_ITEMS"][] = array(
									"FROM" => $arIterator[$arElement["IBLOCK_SECTION_ID"]],
									"TO" => $resTimeFromTmp,
								);
							}

							$arIterator[$arElement["IBLOCK_SECTION_ID"]] = MkTime($arFltTimeFrom[0], $arFltTimeFrom[1], 0, Date("m", $arIterator[$arElement["IBLOCK_SECTION_ID"]]), Date("d", $arIterator[$arElement["IBLOCK_SECTION_ID"]]) + 1, Date("Y", $arIterator[$arElement["IBLOCK_SECTION_ID"]]));
						}
					}
				}
			}

			$arIterator[$arElement["IBLOCK_SECTION_ID"]] = $resTimeTo;

			$resTimeToTmp = MkTime($arFltTimeTo[0], $arFltTimeTo[1], 0, Date("m", $resTimeTo), Date("d", $resTimeTo), Date("Y", $resTimeTo));
			$filterTimeFromTmp = mktime($arFltTimeFrom[0], $arFltTimeFrom[1], 0, date('m', $resTimeTo), date('d', $resTimeTo), date('Y', $resTimeTo));

			if ($arIterator[$arElement['IBLOCK_SECTION_ID']] < $filterTimeFromTmp)
			{
				$arIterator[$arElement['IBLOCK_SECTION_ID']] = $filterTimeFromTmp;
			}

			if ($arIterator[$arElement["IBLOCK_SECTION_ID"]] > $resTimeToTmp)
				$arIterator[$arElement["IBLOCK_SECTION_ID"]] = MkTime($arFltTimeFrom[0], $arFltTimeFrom[1], 0, Date("m", $resTimeTo), Date("d", $resTimeTo) + 1, Date("Y", $resTimeTo));
		}

		$arIteratorKeys = Array_Keys($arIterator);
		foreach ($arIteratorKeys as $key)
		{
			while ($arIterator[$key] < $fltDateTimeTo)
			{
				$resTimeFromTmp = MkTime($arFltTimeTo[0], $arFltTimeTo[1], 0, Date("m", $arIterator[$key]), Date("d", $arIterator[$key]), Date("Y", $arIterator[$key]));
				if ($resTimeFromTmp > $fltDateTimeTo)
					$resTimeFromTmp = $fltDateTimeTo;

				if ($arIterator[$key] < $resTimeFromTmp && ($resTimeFromTmp - $arIterator[$key]) / 3600.0 >= $fltDurationDbl)
				{
					$arResult["MEETINGS_LIST"][$key]["TIME_ITEMS"][] = array(
						"FROM" => $arIterator[$key],
						"TO" => $resTimeFromTmp,
					);
				}

				$arIterator[$key] = MkTime($arFltTimeFrom[0], $arFltTimeFrom[1], 0, Date("m", $arIterator[$key]), Date("d", $arIterator[$key]) + 1, Date("Y", $arIterator[$key]));
			}
		}
	}

	$arResult["ITEMS"] = array();
	foreach ($arResult["MEETINGS_LIST"] as $key => $value)
	{
		if (!Array_Key_Exists("TIME_ITEMS", $value))
		{
			$iterator = $fltDateTimeFrom;
			while ($iterator < $fltDateTimeTo)
			{
				$resTimeFromTmp = MkTime($arFltTimeTo[0], $arFltTimeTo[1], 0, Date("m", $iterator), Date("d", $iterator), Date("Y", $iterator));
				if ($resTimeFromTmp > $fltDateTimeTo)
					$resTimeFromTmp = $fltDateTimeTo;

				if ($iterator < $resTimeFromTmp && ($resTimeFromTmp - $iterator) / 3600.0 >= $fltDurationDbl)
				{
					$uri = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_RESERVE_MEETING"], array("meeting_id" => $key, "item_id" => 0));
					$uri .= HtmlSpecialCharsbx((mb_strpos($uri, "?") === false ? "?" : "&")."start_date=".Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $iterator)."&start_time=".Date("H:i", $iterator)."&timeout_time=".$fltDuration);

					$arResult["ITEMS"][] = array(
						"MEETING_ID" => $key,
						"FREE_DATE" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $iterator),
						"FREE_FROM" => Date("H:i", $iterator),
						"FREE_TO" => Date("H:i", $resTimeFromTmp),
						"FREE_FROM_TIME" => $iterator,
						"FREE_TO_TIME" => $resTimeFromTmp,
						"URI" => $uri,
					);
				}

				$iterator = MkTime($arFltTimeFrom[0], $arFltTimeFrom[1], 0, Date("m", $iterator), Date("d", $iterator) + 1, Date("Y", $iterator));
			}
		}
		elseif (Count($value["TIME_ITEMS"]) > 0)
		{
			foreach ($value["TIME_ITEMS"] as $key1 => $value1)
			{
				$uri = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_RESERVE_MEETING"], array("meeting_id" => $key, "item_id" => 0));
				$uri .= HtmlSpecialCharsbx((mb_strpos($uri, "?") === false ? "?" : "&")."start_date=".Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $value1["FROM"])."&start_time=".Date("H:i", $value1["FROM"])."&timeout_time=".$fltDuration);

				$arResult["ITEMS"][] = array(
					"MEETING_ID" => $key,
					"FREE_DATE" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $value1["FROM"]),
					"FREE_FROM" => Date("H:i", $value1["FROM"]),
					"FREE_TO" => Date("H:i", $value1["TO"]),
					"FREE_FROM_TIME" => $value1["FROM"],
					"FREE_TO_TIME" => $value1["TO"],
					"URI" => $uri,
				);
			}
		}
	}

	for ($i = 0; $i < Count($arResult["ITEMS"]) - 1; $i++)
	{
		for ($j = $i + 1; $j < Count($arResult["ITEMS"]); $j++)
		{
			if ($arResult["ITEMS"][$i]["FREE_FROM_TIME"] > $arResult["ITEMS"][$j]["FREE_FROM_TIME"])
			{
				$t = $arResult["ITEMS"][$i];
				$arResult["ITEMS"][$i] = $arResult["ITEMS"][$j];
				$arResult["ITEMS"][$j] = $t;
			}
		}
	}


	$arFilter = array("IBLOCK_ID" => $iblockId, "ACTIVE" => "Y");
	$arSelectFields = array("IBLOCK_ID", "ID", "NAME");
	$arResult["MEETINGS_ALL"] = array();
	$dbMeetingsList = CIBlockSection::GetList(
		array(),
		$arFilter,
		false,
		$arSelectFields
	);
	while ($arMeeting = $dbMeetingsList->GetNext())
		$arResult["MEETINGS_ALL"][$arMeeting["ID"]] = $arMeeting["NAME"];
}

$this->IncludeComponentTemplate();
?>