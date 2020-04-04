<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!Function_Exists("__IRM_InitReservation"))
{
	CComponentUtil::__IncludeLang(BX_PERSONAL_ROOT."/components/bitrix/intranet.reserve_meeting", "init.php");

	function __IRM_InitReservation($iblockId)
	{
		$arResult = array();

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


		$arResult["ALLOWED_ITEM_PROPERTIES"] = array(
			"UF_PERSONS" => array(
				"NAME" => GetMessage("INTASK_C29_UF_PERSONS"),
				"ACTIVE" => "Y",
				"SORT" => 300,
				"CODE" => "UF_PERSONS",
				"PROPERTY_TYPE" => "N",
				"USER_TYPE" => false,
				"ROW_COUNT" => 1,
				"COL_COUNT" => 5,
				"LINK_IBLOCK_ID" => 0,
				"WITH_DESCRIPTION" => "N",
				"FILTRABLE" => "Y",
				"SEARCHABLE" => "Y",
				"MULTIPLE"  => "N",
				"MULTIPLE_CNT" => 5,
				"IS_REQUIRED" => "N",
				"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
				"LIST_TYPE" => "L",
				"IBLOCK_ID" => $iblockId,
			),
			"UF_RES_TYPE" => array(
				"NAME" => GetMessage("INTASK_C29_UF_RES_TYPE"),
				"ACTIVE" => "Y",
				"SORT" => 200,
				"CODE" => "UF_RES_TYPE",
				"PROPERTY_TYPE" => "L",
				"USER_TYPE" => false,
				"ROW_COUNT" => 1,
				"COL_COUNT" => 30,
				"LINK_IBLOCK_ID" => 0,
				"WITH_DESCRIPTION" => "N",
				"FILTRABLE" => "Y",
				"SEARCHABLE" => "Y",
				"MULTIPLE"  => "N",
				"MULTIPLE_CNT" => 5,
				"IS_REQUIRED" => "Y",
				"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
				"LIST_TYPE" => "L",
				"IBLOCK_ID" => $iblockId,
				"VALUES" => array(
					array(
						"VALUE" => GetMessage("INTASK_C29_UF_RES_TYPEA"),
						"DEF" => "Y",
						"SORT" => 100,
						"XML_ID" => "A"
					),
					array(
						"VALUE" => GetMessage("INTASK_C29_UF_RES_TYPEB"),
						"DEF" => "N",
						"SORT" => 200,
						"XML_ID" => "B"
					),
					array(
						"VALUE" => GetMessage("INTASK_C29_UF_RES_TYPEC"),
						"DEF" => "N",
						"SORT" => 200,
						"XML_ID" => "C"
					),
					array(
						"VALUE" => GetMessage("INTASK_C29_UF_RES_TYPED"),
						"DEF" => "N",
						"SORT" => 300,
						"XML_ID" => "D"
					),
				),
			),
			"UF_PREPARE_ROOM" => array(
				"NAME" => GetMessage("INTASK_C29_UF_PREPARE_ROOM"),
				"ACTIVE" => "Y",
				"SORT" => 500,
				"CODE" => "UF_PREPARE_ROOM",
				"PROPERTY_TYPE" => "S",
				"USER_TYPE" => false,
				"DEFAULT_VALUE" => "Y",
				"ROW_COUNT" => 1,
				"COL_COUNT" => 30,
				"LINK_IBLOCK_ID" => 0,
				"WITH_DESCRIPTION" => "N",
				"FILTRABLE" => "N",
				"SEARCHABLE" => "N",
				"MULTIPLE"  => "N",
				"MULTIPLE_CNT" => 5,
				"IS_REQUIRED" => "N",
				"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
				"LIST_TYPE" => "L",
				"IBLOCK_ID" => $iblockId,
			),
			"PERIOD_TYPE" => array(
				"NAME" => GetMessage("INTASK_C29_PERIOD_TYPE"),
				"ACTIVE" => "Y",
				"SORT" => 500,
				"CODE" => "PERIOD_TYPE",
				"PROPERTY_TYPE" => "S",
				"USER_TYPE" => false,
				"DEFAULT_VALUE" => "NONE",
				"ROW_COUNT" => 1,
				"COL_COUNT" => 30,
				"LINK_IBLOCK_ID" => 0,
				"WITH_DESCRIPTION" => "N",
				"FILTRABLE" => "N",
				"SEARCHABLE" => "N",
				"MULTIPLE"  => "N",
				"MULTIPLE_CNT" => 5,
				"IS_REQUIRED" => "N",
				"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
				"LIST_TYPE" => "L",
				"IBLOCK_ID" => $iblockId,
			),
			"PERIOD_COUNT" => array(
				"NAME" => GetMessage("INTASK_C29_PERIOD_COUNT"),
				"ACTIVE" => "Y",
				"SORT" => 500,
				"CODE" => "PERIOD_COUNT",
				"PROPERTY_TYPE" => "N",
				"USER_TYPE" => false,
				"DEFAULT_VALUE" => "",
				"ROW_COUNT" => 1,
				"COL_COUNT" => 30,
				"LINK_IBLOCK_ID" => 0,
				"WITH_DESCRIPTION" => "N",
				"FILTRABLE" => "N",
				"SEARCHABLE" => "N",
				"MULTIPLE"  => "N",
				"MULTIPLE_CNT" => 5,
				"IS_REQUIRED" => "N",
				"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
				"LIST_TYPE" => "L",
				"IBLOCK_ID" => $iblockId,
			),
			"EVENT_LENGTH" => array(
				"NAME" => GetMessage("INTASK_C29_EVENT_LENGTH"),
				"ACTIVE" => "Y",
				"SORT" => 500,
				"CODE" => "EVENT_LENGTH",
				"PROPERTY_TYPE" => "N",
				"USER_TYPE" => false,
				"DEFAULT_VALUE" => "",
				"ROW_COUNT" => 1,
				"COL_COUNT" => 30,
				"LINK_IBLOCK_ID" => 0,
				"WITH_DESCRIPTION" => "N",
				"FILTRABLE" => "N",
				"SEARCHABLE" => "N",
				"MULTIPLE"  => "N",
				"MULTIPLE_CNT" => 5,
				"IS_REQUIRED" => "N",
				"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
				"LIST_TYPE" => "L",
				"IBLOCK_ID" => $iblockId,
			),
			"PERIOD_ADDITIONAL" => array(
				"NAME" => GetMessage("INTASK_C29_PERIOD_ADDITIONAL"),
				"ACTIVE" => "Y",
				"SORT" => 500,
				"CODE" => "PERIOD_ADDITIONAL",
				"PROPERTY_TYPE" => "S",
				"USER_TYPE" => false,
				"DEFAULT_VALUE" => "",
				"ROW_COUNT" => 1,
				"COL_COUNT" => 30,
				"LINK_IBLOCK_ID" => 0,
				"WITH_DESCRIPTION" => "N",
				"FILTRABLE" => "N",
				"SEARCHABLE" => "N",
				"MULTIPLE"  => "N",
				"MULTIPLE_CNT" => 5,
				"IS_REQUIRED" => "N",
				"FILE_TYPE" => "jpg, gif, bmp, png, jpeg",
				"LIST_TYPE" => "L",
				"IBLOCK_ID" => $iblockId,
			),
		);

		$dbIBlockProps = CIBlock::GetProperties($iblockId);
		while ($arIBlockProps = $dbIBlockProps->Fetch())
		{
			if (Array_Key_Exists($arIBlockProps["CODE"], $arResult["ALLOWED_ITEM_PROPERTIES"]))
				$arResult["ALLOWED_ITEM_PROPERTIES"][$arIBlockProps["CODE"]]["ID"] = $arIBlockProps["ID"];
		}

		$keys = Array_Keys($arResult["ALLOWED_ITEM_PROPERTIES"]);
		foreach ($keys as $key)
		{
			if (IntVal($arResult["ALLOWED_ITEM_PROPERTIES"][$key]["ID"]) <= 0)
			{
				$ibp = new CIBlockProperty;
				$arResult["ALLOWED_ITEM_PROPERTIES"][$key]["ID"] = $ibp->Add($arResult["ALLOWED_ITEM_PROPERTIES"][$key]);
			}
		}

		return $arResult;
	}

	function __IRM_SearchPeriodic($fromDate, $toDate, $iblockId, $meeting, $id = 0)
	{
		$iblockId = IntVal($iblockId);
		if ($iblockId <= 0)
			return array();

		if (!Is_Array($meeting))
		{
			if (IntVal($meeting) > 0)
				$meeting = array(IntVal($meeting));
			else
				$meeting = array();
		}
		else
		{
			$meetingTmp = $meeting;
			$meeting = array();
			foreach ($meetingTmp as $m)
			{
				$m = IntVal($m);
				if ($m > 0)
					$meeting[] = $m;
			}
		}

		$arPeriodElements = array();

		$arWeeklyPeriods = array();
		$arMonthlyPeriods = array();
		$arYearlyPeriods = array();

		$y1 = Date("Y", $fromDate);
		$m1 = Date("n", $fromDate);
		$d1 = Date("j", $fromDate);
		$w1 = Date("w", $fromDate);

		$fromDateOnly = MkTime(0, 0, 0, $m1, $d1, $y1);
		$toDateOnly = MkTime(0, 0, 0, Date("n", $toDate), Date("j", $toDate), Date("Y", $toDate));

		$n = IntVal(Round(($toDateOnly - $fromDateOnly) / 86400));

		$arWeeklyPeriods[0] = array(
			"year" => $y1,
			"monthFrom" => $m1,
			"dayFrom" => $d1,
			"week" => Date("W", $fromDate),
			"weekDayFrom" => ($w1 == 0 ? 6 : $w1 - 1),
			"weekTimeStart" => MkTime(0, 0, 0, $m1, $d1 - ($w1 == 0 ? 7 : $w1) + 1, $y1),
		);

		$arMonthlyPeriods[0] = array(
			"year" => $y1,
			"month" => $m1,
			"dayFrom" => $d1,
		);
		$arYearlyPeriods[0] = array(
			"year" => $y1,
			"monthFrom" => $m1,
			"dayFrom" => $d1,
		);
		if ($n < 1)
		{
			$arWeeklyPeriods[0]["monthTo"] = $arWeeklyPeriods[0]["monthFrom"];
			$arWeeklyPeriods[0]["dayTo"] = $arWeeklyPeriods[0]["dayFrom"];
			$arWeeklyPeriods[0]["weekDayTo"] = $arWeeklyPeriods[0]["weekDayFrom"];
			$arWeeklyPeriods[0]["weekTimeEnd"] = MkTime(0, 0, 0, $m1, $d1 - ($w1 == 0 ? 7 : $w1) + 1 + 7, $y1);

			$arMonthlyPeriods[0]["dayTo"] = $arMonthlyPeriods[0]["dayFrom"];

			$arYearlyPeriods[0]["monthTo"] = $arYearlyPeriods[0]["monthFrom"];
			$arYearlyPeriods[0]["dayTo"] = $arYearlyPeriods[0]["dayFrom"];
		}
		else
		{
			$jY = 0;
			$jM = 0;
			$jW = 0;
			for ($i = 1; $i <= $n; $i++)
			{
				$t = MkTime(0, 0, 0, $m1, $d1 + $i, $y1);

				if (Date("Y", $t) != $arYearlyPeriods[$jY]["year"])
				{
					$t1 = MkTime(0, 0, 0, $m1, $d1 + $i - 1, $y1);
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
					$t1 = MkTime(0, 0, 0, $m1, $d1 + $i - 1, $y1);
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
					$t1 = MkTime(0, 0, 0, $m1, $d1 + $i - 1, $y1);
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

			$t1 = MkTime(0, 0, 0, Date("n", $toDate), Date("j", $toDate), Date("Y", $toDate));

			$arWeeklyPeriods[$jW]["monthTo"] = Date("n", $t1);
			$arWeeklyPeriods[$jW]["dayTo"] = Date("j", $t1);
			$arWeeklyPeriods[$jW]["weekDayTo"] = (Date("w", $t1) == 0 ? 6 : Date("w", $t1) - 1);
			$arWeeklyPeriods[$jW]["weekTimeEnd"] = MkTime(0, 0, 0, Date("n", $t1), Date("j", $t1) - (Date("w", $t1) == 0 ? 7 : Date("w", $t1)) + 1 + 7, Date("Y", $t1));

			$arMonthlyPeriods[$jM]["dayTo"] = Date("j", $t1);

			$arYearlyPeriods[$jY]["monthTo"] = Date("n", $t1);
			$arYearlyPeriods[$jY]["dayTo"] = Date("j", $t1);
		}

		$id = IntVal($id);

		$arFilter = array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $iblockId,
			"<DATE_ACTIVE_FROM" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $toDate),
			">DATE_ACTIVE_TO" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $fromDate),
			"!PROPERTY_PERIOD_TYPE" => "NONE",
		);

		if (Count($meeting) > 0)
			$arFilter["SECTION_ID"] = $meeting;

		if ($id > 0)
			$arFilter["!ID"] = $id;

		$dbElements = CIBlockElement::GetList(
			array("DATE_ACTIVE_FROM" => "ASC"),
			$arFilter,
			false,
			false,
			array("ID", "IBLOCK_ID", "NAME", "DATE_ACTIVE_FROM", "IBLOCK_SECTION_ID", "DATE_ACTIVE_TO", "CREATED_BY", "PROPERTY_PERIOD_TYPE", "PROPERTY_PERIOD_COUNT", "PROPERTY_EVENT_LENGTH", "PROPERTY_PERIOD_ADDITIONAL")
		);
		while ($arElement = $dbElements->GetNext())
		{
			$arDates = array();

			$dateActiveFrom = MakeTimeStamp($arElement["DATE_ACTIVE_FROM"], FORMAT_DATETIME);
			$dateActiveTo = MakeTimeStamp($arElement["DATE_ACTIVE_TO"], FORMAT_DATETIME);

			$dateActiveFromDateOnly = MkTime(0, 0, 0, Date("n", $dateActiveFrom), Date("j", $dateActiveFrom), Date("Y", $dateActiveFrom));
			$dateActiveToDateOnly = MkTime(0, 0, 0, Date("n", $dateActiveTo), Date("j", $dateActiveTo), Date("Y", $dateActiveTo));

			if ($arElement["PROPERTY_PERIOD_TYPE_VALUE"] == "DAILY")
			{
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = IntVal($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
				if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
					$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

				if ($fromDate > $dateActiveFrom || $toDate <= $dateActiveFrom)
				{
					$dayShift = (IntVal(Round(($fromDate - $dateActiveFromDateOnly) / 86400)) % $arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
					if ($dayShift > 0)
						$dayShift = $arElement["PROPERTY_PERIOD_COUNT_VALUE"] - $dayShift;

					$fromTimeTmp = MkTime(Date("H", $dateActiveFrom), Date("i", $dateActiveFrom), Date("s", $dateActiveFrom), Date("n", $fromDate), Date("j", $fromDate) + $dayShift, Date("Y", $fromDate));
				}
				else
				{
					$fromTimeTmp = $dateActiveFrom;
				}

				while ($dateActiveFrom <= $fromTimeTmp && $dateActiveTo > $fromTimeTmp
					&& $fromTimeTmp < $toDate
					&& $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"] > $fromDate)
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
					$w = Date("w", $dateActiveFrom);
					$arPeriodAdditional[] = ($w == 0 ? 6 : $w - 1);
				}

				$wscr = MkTime(0, 0, 0, Date("n", $dateActiveFrom), Date("j", $dateActiveFrom) - (Date("w", $dateActiveFrom) == 0 ? 7 : Date("w", $dateActiveFrom)) + 1, Date("Y", $dateActiveFrom));

				foreach ($arWeeklyPeriods as $arPeriod)
				{
					if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] > 1)
					{
						$weekShift = IntVal(Round(($arPeriod["weekTimeStart"] - $wscr) / 604800));
						if ($weekShift % $arElement["PROPERTY_PERIOD_COUNT_VALUE"] != 0)
							continue;
					}

					foreach ($arPeriodAdditional as $w)
					{
						if ($w >= $arPeriod["weekDayFrom"] && $w <= $arPeriod["weekDayTo"])
						{
							$fromTimeTmp = MkTime(Date("H", $dateActiveFrom), Date("i", $dateActiveFrom), Date("s", $dateActiveFrom), Date("n", $arPeriod["weekTimeStart"]), Date("j", $arPeriod["weekTimeStart"]) + $w, Date("Y", $arPeriod["weekTimeStart"]));

							if ($dateActiveFrom > $fromTimeTmp || $dateActiveTo <= $fromTimeTmp
								|| $fromTimeTmp >= $toDate
								|| $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"] <= $fromDate)
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
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = IntVal($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
				if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
					$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

				foreach ($arMonthlyPeriods as $arPeriod)
				{
					$dm = Date("j", $dateActiveFrom);
					if ($arPeriod["dayFrom"] > $dm || $arPeriod["dayTo"] < $dm)
						continue;

					if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] > 1)
					{
						$nm = 0;
						if ($arPeriod["year"] == Date("Y", $dateActiveFrom))
						{
							$nm += $arPeriod["month"] - Date("n", $dateActiveFrom);
						}
						else
						{
							$nm += 12 - Date("n", $dateActiveFrom);
							if ($arPeriod["year"] != Date("Y", $dateActiveFrom) + 1)
								$nm += ($arPeriod["year"] - Date("Y", $dateActiveFrom) - 1) * 12;
							$nm += $arPeriod["month"];
						}

						if ($nm % $arElement["PROPERTY_PERIOD_COUNT_VALUE"] != 0)
							continue;
					}

					$fromTimeTmp = MkTime(Date("H", $dateActiveFrom), Date("i", $dateActiveFrom), Date("s", $dateActiveFrom), $arPeriod["month"], Date("j", $dateActiveFrom), $arPeriod["year"]);

					if ($dateActiveFrom > $fromTimeTmp || $dateActiveTo <= $fromTimeTmp
						|| $fromTimeTmp >= $toDate
						|| $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"] <= $fromDate)
						continue;

					$arDates[] = array(
						"DATE_ACTIVE_FROM" => $fromTimeTmp,
						"DATE_ACTIVE_TO" => $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"],
					);
				}
			}
			elseif ($arElement["PROPERTY_PERIOD_TYPE_VALUE"] == "YEARLY")
			{
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = IntVal($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
				if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
					$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

				foreach ($arYearlyPeriods as $arPeriod)
				{
					$dm = Date("j", $dateActiveFrom);
					$my = Date("n", $dateActiveFrom);
					if ($my < $arPeriod["monthFrom"] || $my > $arPeriod["monthTo"])
						continue;

					if ($my == $arPeriod["monthFrom"] && $dm < $arPeriod["dayFrom"]
						|| $my == $arPeriod["monthTo"] && $dm > $arPeriod["dayTo"])
						continue;

					if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] > 1)
					{
						if (($arPeriod["year"] - Date("Y", $dateActiveFrom)) % $arElement["PROPERTY_PERIOD_COUNT_VALUE"] != 0)
							continue;
					}

					$fromTimeTmp = MkTime(Date("H", $dateActiveFrom), Date("i", $dateActiveFrom), Date("s", $dateActiveFrom), Date("n", $dateActiveFrom), Date("j", $dateActiveFrom), $arPeriod["year"]);

					if ($dateActiveFrom > $fromTimeTmp || $dateActiveTo <= $fromTimeTmp
						|| $fromTimeTmp >= $toDate
						|| $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"] <= $fromDate)
						continue;

					$arDates[] = array(
						"DATE_ACTIVE_FROM" => $fromTimeTmp,
						"DATE_ACTIVE_TO" => $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"],
					);
				}
			}

			if (Is_Array($arDates))
			{
				foreach ($arDates as $d)
				{
					$arElement["DATE_ACTIVE_FROM_TIME"] = $d["DATE_ACTIVE_FROM"];
					$arElement["DATE_ACTIVE_TO_TIME"] = $d["DATE_ACTIVE_TO"];
					$arElement["DATE_ACTIVE_FROM"] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $d["DATE_ACTIVE_FROM"]);
					$arElement["DATE_ACTIVE_TO"] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $d["DATE_ACTIVE_TO"]);
					$arPeriodElements[] = $arElement;
				}
			}
		}

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

		return $arPeriodElements;
	}
}
?>
