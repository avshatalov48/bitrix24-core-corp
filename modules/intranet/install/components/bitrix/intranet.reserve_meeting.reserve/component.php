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

$itemId = IntVal($arParams["ITEM_ID"]);
if ($itemId <= 0)
	$itemId = IntVal($_REQUEST[$arParams["ITEM_VAR"]]);

$arParams["PATH_TO_MEETING"] = Trim($arParams["PATH_TO_MEETING"]);
if (StrLen($arParams["PATH_TO_MEETING"]) <= 0)
	$arParams["PATH_TO_MEETING"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=meeting&".$arParams["MEETING_VAR"]."=#meeting_id#");

$arParams["PATH_TO_MEETING_LIST"] = Trim($arParams["PATH_TO_MEETING_LIST"]);
if (StrLen($arParams["PATH_TO_MEETING_LIST"]) <= 0)
	$arParams["PATH_TO_MEETING_LIST"] = HtmlSpecialCharsbx($APPLICATION->GetCurPage());

$arParams['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'] ? $arParams['NAME_TEMPLATE'] : CSite::GetNameFormat(false);
$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;

$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "Y" ? "Y" : "N");
$arParams["SET_NAVCHAIN"] = ($arParams["SET_NAVCHAIN"] == "Y" ? "Y" : "N");

if (!Is_Array($arParams["USERGROUPS_RESERVE"]))
{
	if (IntVal($arParams["USERGROUPS_RESERVE"]) > 0)
		$arParams["USERGROUPS_RESERVE"] = array($arParams["USERGROUPS_RESERVE"]);
	else
		$arParams["USERGROUPS_RESERVE"] = array();
}


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
	$APPLICATION->AddChainItem(GetMessage("INTASK_C36_PAGE_TITLE1"), CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MEETING_LIST"], array()));

if (!$GLOBALS["USER"]->IsAuthorized())
	$arResult["FatalError"] = GetMessage("INTASK_C29_NEED_AUTH").". ";

if (StrLen($arResult["FatalError"]) <= 0)
{
	if (!$GLOBALS["USER"]->IsAdmin()
		&& Count(Array_Intersect($GLOBALS["USER"]->GetUserGroupArray(), $arParams["USERGROUPS_RESERVE"])) <= 0)
	{
		$arResult["FatalError"] = GetMessage("INTASK_C29_NO_RPERMS").". ";
	}
}

if (StrLen($arResult["FatalError"]) <= 0)
{
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
		$arResult["FatalError"] = GetMessage("INAF_MEETING_NOT_FOUND")." ";
}

if (StrLen($arResult["FatalError"]) <= 0)
{
	$arItem = false;

	if ($itemId > 0)
	{
		$arSelectFields = array("ID", "NAME", "IBLOCK_ID", "CREATED_BY", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO", "DETAIL_TEXT");
		foreach ($arResult["ALLOWED_ITEM_PROPERTIES"] as $key => $value)
			$arSelectFields[] = "PROPERTY_".$key;

		$dbElements = CIBlockElement::GetList(
			array(),
			array(
				"ACTIVE" => "Y",
				"IBLOCK_ID" => $iblockId,
				"SECTION_ID" => $meetingId,
				"ID" => $itemId,
			),
			false,
			false,
			$arSelectFields
		);
		
		$arItem = $dbElements->GetNext();

		if (!$arItem)
			$arResult["FatalError"] .= GetMessage("INAF_ITEM_NOT_FOUND")." ";
	}
}

if (StrLen($arResult["FatalError"]) <= 0)
{
	if ($arItem)
	{
		if (!$GLOBALS["USER"]->IsAdmin() && $GLOBALS["USER"]->GetID() != $arItem["CREATED_BY"])
			$arResult["FatalError"] .= GetMessage("INTASK_C29_NO_RPERMS_MODIFY").". ";
	}
}

if (StrLen($arResult["FatalError"]) <= 0)
{
	$bVarsFromForm = false;
	if ($_SERVER["REQUEST_METHOD"] == "POST" && StrLen($_POST["save"]) > 0 && check_bitrix_sessid())
	{
		$errorMessage = "";

		$startDateV = $_REQUEST["start_date"];
		$startTimeV = $_REQUEST["start_time"];
		$timeoutTimeV = $_REQUEST["timeout_time"];
		$personsV = $_REQUEST["persons"];
		$resTypeV = $_REQUEST["res_type"];
		$descriptionV = $_REQUEST["description"];
		$prepareRoomV = $_REQUEST["prepare_room"];
		$nameV = $_REQUEST["name"];

		$regularityV = $_REQUEST["regularity"];
		$regularityCountV = $_REQUEST["regularity_count"];
		$regularityEndV = $_REQUEST["regularity_end"];
		$regularityAdditionalV = $_REQUEST["regularity_additional"];

		if (StrLen($startDateV) <= 0)
		{
			$errorMessage .= GetMessage("INTASK_C29_EMPTY_DATE").". ";
		}
		else
		{
			$startDateVTmp = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), MakeTimeStamp($startDateV, FORMAT_DATE));
			if ($startDateVTmp != $startDateV)
				$errorMessage .= Str_Replace("#FORMAT#", $GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), GetMessage("INTASK_C29_WRONG_DATE")).". ";
		}

		if (StrLen($startTimeV) <= 0)
		{
			$errorMessage .= GetMessage("INTASK_C29_EMPTY_TIME").". ";
		}
		else
		{
			if (IsAmPmMode())
			{
				$startTimeV = str_replace(':', ' ', $startTimeV);
				$arStartTimeVTmp = Explode(" ", $startTimeV);
				if ($arStartTimeVTmp[0] < 12 && $arStartTimeVTmp[2] == 'pm')
				{
					$arStartTimeVTmp[0] += 12;
				}
				elseif ($arStartTimeVTmp[0] == 12 && $arStartTimeVTmp[2] == 'am')
				{
					$arStartTimeVTmp[0] = 0;
				}
				unset($arStartTimeVTmp[2]);
			}
			else
			{
				$arStartTimeVTmp = Explode(":", $startTimeV);
			}
			if (Count($arStartTimeVTmp) != 2 || IntVal($arStartTimeVTmp[0]) > 23 || IntVal($arStartTimeVTmp[0]) < 0
				|| $arStartTimeVTmp[1] != "00" && $arStartTimeVTmp[1] != "30")
				$errorMessage .= Str_Replace("#FORMAT#", GetMessage("INTASK_C29_HM"), GetMessage("INTASK_C29_WRONG_TIME")).". ";
		}

		if (StrLen($timeoutTimeV) <= 0)
		{
			$errorMessage .= GetMessage("INTASK_C29_EMPTY_DURATION").". ";
		}
		else
		{
			$arTimeoutTimeVTmp = Explode(".", $timeoutTimeV);
			if (Count($arTimeoutTimeVTmp) != 2 || IntVal($arTimeoutTimeVTmp[0]) > 23 || IntVal($arTimeoutTimeVTmp[0]) < 0
				|| $arTimeoutTimeVTmp[1] != "0" && $arTimeoutTimeVTmp[1] != "5")
				$errorMessage .= GetMessage("INTASK_C29_WRONG_DURATION").". ";
		}

		if (StrLen($nameV) <= 0)
			$errorMessage .= GetMessage("INTASK_C29_EMPTY_NAME").". ";

		if (StrLen($errorMessage) <= 0)
		{
			$regularityV = StrToUpper($regularityV);
			if (!In_Array($regularityV, array("NONE", "WEEKLY", "DAILY", "MONTHLY", "YEARLY")))
				$regularityV = "NONE";

			if ($regularityV != "NONE")
			{
				$regularityCountV = IntVal($regularityCountV);
				if ($regularityCountV <= 0)
					$regularityCountV = 1;

				if (StrLen($regularityEndV) > 0)
				{
					$regularityEndVTmp = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), MakeTimeStamp($regularityEndV, FORMAT_DATE));
					if ($regularityEndVTmp != $regularityEndV)
						$regularityEndV = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), 2145938400);
				}
				else
				{
					$regularityEndV = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), 2145938400);
				}

				$regularityAdditionalVString = "";
				if ($regularityV == "WEEKLY" && Is_Array($regularityAdditionalV) && Count($regularityAdditionalV) > 0)
				{
					foreach ($regularityAdditionalV as $v)
					{
						$v = IntVal($v);
						if ($v >= 0 && $v <= 6)
						{
							if (StrLen($regularityAdditionalVString) > 0)
								$regularityAdditionalVString .= ",";
							$regularityAdditionalVString .= $v;
						}
					}
				}
			}
			else
			{
				$regularityCountV = "";
				$regularityEndV = "";
				$regularityAdditionalV = "";
				$regularityAdditionalVString = "";
			}
		}

		if (StrLen($errorMessage) <= 0)
		{
			if ($regularityV == "NONE")
			{
				$t = MakeTimeStamp($startDateV, FORMAT_DATE);
				$fromDateTime = MkTime($arStartTimeVTmp[0], $arStartTimeVTmp[1], 0, Date("m", $t), Date("d", $t), Date("Y", $t));
				$toDateTime = MkTime($arStartTimeVTmp[0] + $arTimeoutTimeVTmp[0], $arStartTimeVTmp[1] + ($arTimeoutTimeVTmp[1] == "5" ? 30 : 0), 0, Date("m", $t), Date("d", $t), Date("Y", $t));

				$arFilter = array(
					"ACTIVE" => "Y",
					"IBLOCK_ID" => $arMeeting["IBLOCK_ID"],
					"SECTION_ID" => $arMeeting["ID"],
					"<DATE_ACTIVE_FROM" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $toDateTime),
					">DATE_ACTIVE_TO" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $fromDateTime),
					"PROPERTY_PERIOD_TYPE" => "NONE",
				);

				if ($arItem)
					$arFilter["!ID"] = $arItem["ID"];

				$dbElements = CIBlockElement::GetList(
					array("DATE_ACTIVE_FROM" => "ASC"),
					$arFilter,
					false,
					false,
					array()
				);
				while ($arElements = $dbElements->GetNext())
				{
					$ft1 = MakeTimeStamp($arElements["DATE_ACTIVE_FROM"], FORMAT_DATETIME);
					$ft2 = MakeTimeStamp($arElements["DATE_ACTIVE_TO"], FORMAT_DATETIME);

					$errorMessage .= Str_Replace(
						array("#TIME1#", "#TIME2#", "#RES#"),
						array("<b>".Date("H:i", $ft1)."</b> ".Date("d.m", $ft1), "<b>".Date("H:i", $ft2)."</b> ".Date("d.m", $ft2), $arElements["NAME"]),
						GetMessage("INTASK_C29_CONFLICT").". "
					);
				}

				$arPeriodicElements = __IRM_SearchPeriodic($fromDateTime, $toDateTime, $arMeeting["IBLOCK_ID"], $arMeeting["ID"], $arItem ? $arItem["ID"] : 0);
				foreach ($arPeriodicElements as $pe)
				{
					$errorMessage .= Str_Replace(
						array("#TIME1#", "#TIME2#", "#RES#"),
						array("<b>".Date("H:i", $pe["DATE_ACTIVE_FROM_TIME"])."</b> ".Date("d.m", $pe["DATE_ACTIVE_FROM_TIME"]), "<b>".Date("H:i", $pe["DATE_ACTIVE_TO_TIME"])."</b> ".Date("d.m", $pe["DATE_ACTIVE_TO_TIME"]), $pe["NAME"]),
						GetMessage("INTASK_C29_CONFLICT").". "
					);
				}
			}
			else
			{
				$t = MakeTimeStamp($startDateV, FORMAT_DATE);
				$fromDateTime = MkTime($arStartTimeVTmp[0], $arStartTimeVTmp[1], 0, Date("m", $t), Date("d", $t), Date("Y", $t));
				$toDateTime = MkTime($arStartTimeVTmp[0] + $arTimeoutTimeVTmp[0], $arStartTimeVTmp[1] + ($arTimeoutTimeVTmp[1] == "5" ? 30 : 0), 0, Date("m", $t), Date("d", $t), Date("Y", $t));

				$regularityLength = $toDateTime - $fromDateTime;
				$toDateTime = MakeTimeStamp($regularityEndV, FORMAT_DATE);
			}
		}

		if (StrLen($errorMessage) <= 0)
		{
			$arFields = array(
				"NAME" => $nameV,
				"DATE_ACTIVE_FROM" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $fromDateTime),
				"DATE_ACTIVE_TO" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $toDateTime),
				"CREATED_BY" => $GLOBALS["USER"]->GetID(),
				"DETAIL_TEXT" => $descriptionV,
				"PROPERTY_VALUES" => array(
					$arResult["ALLOWED_ITEM_PROPERTIES"]["UF_PERSONS"]["ID"] => array(
						$personsV
					),
					$arResult["ALLOWED_ITEM_PROPERTIES"]["UF_RES_TYPE"]["ID"] => array(
						$resTypeV
					),
					$arResult["ALLOWED_ITEM_PROPERTIES"]["UF_PREPARE_ROOM"]["ID"] => array(
						$prepareRoomV
					),
					$arResult["ALLOWED_ITEM_PROPERTIES"]["PERIOD_TYPE"]["ID"] => array(
						$regularityV
					),
					$arResult["ALLOWED_ITEM_PROPERTIES"]["PERIOD_COUNT"]["ID"] => array(
						$regularityCountV
					),
					$arResult["ALLOWED_ITEM_PROPERTIES"]["EVENT_LENGTH"]["ID"] => array(
						$regularityLength
					),
					$arResult["ALLOWED_ITEM_PROPERTIES"]["PERIOD_ADDITIONAL"]["ID"] => array(
						$regularityAdditionalVString
					),
				),
				"ACTIVE" => "Y",
			);

			$iblockElementObject = new CIBlockElement;
			if ($arItem)
			{
				$res = $iblockElementObject->Update($arItem["ID"], $arFields);
			}
			else
			{
				$arFields["IBLOCK_ID"] = $arMeeting["IBLOCK_ID"];
				$arFields["IBLOCK_SECTION_ID"] = $arMeeting["ID"];

				$idTmp = $iblockElementObject->Add($arFields);
				$res = ($idTmp > 0);
			}

			if (!$res)
				$errorMessage .= $iblockElementObject->LAST_ERROR." ";
			else
				CEventCalendar::clearEventsCache($arMeeting["IBLOCK_ID"]);
		}

		if (StrLen($errorMessage) <= 0)
		{
			$p = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MEETING"], array("meeting_id" => $arMeeting["ID"]));
			$p = $p.(StrPos($p, "?") === false ? "?" : "&")."week_start=".Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $fromDateTime);
			LocalRedirect($p);
		}
		else
		{
			$arResult["ErrorMessage"] .= $errorMessage;
			$bVarsFromForm = true;

			$arResult["Item"]["StartDate"] = HtmlSpecialCharsbx($_REQUEST["start_date"]);
			$arResult["Item"]["StartTime"] = HtmlSpecialCharsbx($_REQUEST["start_time"]);
			$arResult["Item"]["TimeoutTime"] = HtmlSpecialCharsbx($_REQUEST["timeout_time"]);
			$arResult["Item"]["Name"] = HtmlSpecialCharsbx($_REQUEST["name"]);
			$arResult["Item"]["Persons"] = HtmlSpecialCharsbx($_REQUEST["persons"]);
			$arResult["Item"]["ResType"] = HtmlSpecialCharsbx($_REQUEST["res_type"]);
			$arResult["Item"]["Description"] = HtmlSpecialCharsbx($_REQUEST["description"]);
			$arResult["Item"]["PrepareRoom"] = HtmlSpecialCharsbx($_REQUEST["prepare_room"]);

			$arResult["Item"]["Regularity"] = HtmlSpecialCharsbx($_REQUEST["regularity"]);
			$arResult["Item"]["RegularityCount"] = HtmlSpecialCharsbx($_REQUEST["regularity_count"]);
			$arResult["Item"]["RegularityEnd"] = HtmlSpecialCharsbx($_REQUEST["regularity_end"]);
			$arResult["Item"]["RegularityAdditional"] = HtmlSpecialCharsbx(Implode(",", $_REQUEST["regularity_additional"]));
		}
	}
}

if (StrLen($arResult["FatalError"]) <= 0)
{
	$arResult["MEETING"] = $arMeeting;

	if ($arParams["SET_TITLE"] == "Y")
		$APPLICATION->SetTitle(GetMessage("INTASK_C36_PAGE_TITLE").": ".$arMeeting["NAME"]);

	if ($arParams["SET_NAVCHAIN"] == "Y")
		$APPLICATION->AddChainItem($arMeeting["NAME"]);

	if (!$bVarsFromForm)
	{
		if ($arItem)
		{
			$ft1 = MakeTimeStamp($arItem["DATE_ACTIVE_FROM"], FORMAT_DATETIME);
			$ft2 = MakeTimeStamp($arItem["DATE_ACTIVE_TO"], FORMAT_DATETIME);

			$arResult["Item"]["StartDate"] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $ft1);
			$arResult["Item"]["StartTime"] = Date("H:i", $ft1);

			$arResult["Item"]["Name"] = $arItem["NAME"];
			$arResult["Item"]["Persons"] = $arItem["PROPERTY_UF_PERSONS_VALUE"];
			$arResult["Item"]["ResType"] = $arItem["PROPERTY_UF_RES_TYPE_ENUM_ID"];
			$arResult["Item"]["Description"] = $arItem["DETAIL_TEXT"];
			$arResult["Item"]["PrepareRoom"] = $arItem["PROPERTY_UF_PREPARE_ROOM_VALUE"];

			$arResult["Item"]["Regularity"] = $arItem["PROPERTY_PERIOD_TYPE_VALUE"];
			$arResult["Item"]["RegularityCount"] = $arItem["PROPERTY_PERIOD_COUNT_VALUE"];
			$arResult["Item"]["RegularityAdditional"] = $arItem["PROPERTY_PERIOD_ADDITIONAL_VALUE"];

			if ($arItem["PROPERTY_PERIOD_TYPE_VALUE"] == "NONE")
			{
				$ft = ($ft2 - $ft1) / 3600.0;
				$arResult["Item"]["TimeoutTime"] = IntVal($ft);
				$dt = $ft - IntVal($ft);
				if ($dt < 0.25)
					$arResult["Item"]["TimeoutTime"] .= ".0";
				elseif ($dt >= 0.25 && $dt < 0.75)
					$arResult["Item"]["TimeoutTime"] .= ".5";
				else
					$arResult["Item"]["TimeoutTime"] .= ($arResult["Item"]["TimeoutTime"] + 1).".0";
			}
			else
			{
				$ft = $arItem["PROPERTY_EVENT_LENGTH_VALUE"] / 3600.0;
				$arResult["Item"]["TimeoutTime"] = IntVal($ft);
				$dt = $ft - IntVal($ft);
				if ($dt < 0.25)
					$arResult["Item"]["TimeoutTime"] .= ".0";
				elseif ($dt >= 0.25 && $dt < 0.75)
					$arResult["Item"]["TimeoutTime"] .= ".5";
				else
					$arResult["Item"]["TimeoutTime"] .= ($arResult["Item"]["TimeoutTime"] + 1).".0";

				$arResult["Item"]["RegularityEnd"] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE), $ft2);
			}
		}
		else
		{
			$arResult["Item"]["StartDate"] = HtmlSpecialCharsbx($_REQUEST["start_date"]);
			if (StrLen($arResult["Item"]["StartDate"]) <= 0)
				$arResult["Item"]["StartDate"] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATE));

			$arResult["Item"]["StartTime"] = HtmlSpecialCharsbx($_REQUEST["start_time"]);
			$arResult["Item"]["TimeoutTime"] = HtmlSpecialCharsbx($_REQUEST["timeout_time"]);
			$arResult["Item"]["Name"] = HtmlSpecialCharsbx($_REQUEST["name"]);
			$arResult["Item"]["Persons"] = HtmlSpecialCharsbx($_REQUEST["persons"]);
			$arResult["Item"]["ResType"] = HtmlSpecialCharsbx($_REQUEST["res_type"]);
			$arResult["Item"]["Description"] = HtmlSpecialCharsbx($_REQUEST["description"]);
			$arResult["Item"]["PrepareRoom"] = HtmlSpecialCharsbx($_REQUEST["prepare_room"]);

			$arResult["Item"]["Regularity"] = HtmlSpecialCharsbx($_REQUEST["regularity"]);
			$arResult["Item"]["RegularityCount"] = HtmlSpecialCharsbx($_REQUEST["regularity_count"]);
			$arResult["Item"]["RegularityEnd"] = HtmlSpecialCharsbx($_REQUEST["regularity_end"]);
			$arResult["Item"]["RegularityAdditional"] = HtmlSpecialCharsbx($_REQUEST["regularity_additional"]);
			if (StrLen($arResult["Item"]["RegularityAdditional"]) <= 0)
			{
				$z = Date("w", MakeTimeStamp($arResult["Item"]["StartDate"], FORMAT_DATE));
				$arResult["Item"]["RegularityAdditional"] = ($z == 0 ? 6 : $z - 1);
			}
		}
	}

	if ($arItem)
	{
		$arResult["Item"]["Author"] = "-";
		$dbUser = CUser::GetByID($arItem["CREATED_BY"]);
		if ($arUser = $dbUser->GetNext())
		{
			$arResult["Item"]["Author_ID"] = $arUser["ID"];
			$arResult["Item"]["Author"] = CUser::FormatName($arParams['NAME_TEMPLATE'], $arUser, $bUseLogin);
			$arResult["Item"]["Author_NAME"] = $arUser["NAME"];
			$arResult["Item"]["Author_LAST_NAME"] = $arUser["LAST_NAME"];
			$arResult["Item"]["Author_SECOND_NAME"] = $arUser["SECOND_NAME"];
			$arResult["Item"]["Author_LOGIN"] = $arUser["LOGIN"];
		}
	}
	else
	{
		$arResult["Item"]["Author_ID"] = $GLOBALS["USER"]->GetID();			
		$arResult["Item"]["Author_NAME"] = $GLOBALS["USER"]->GetFirstName();
		$arResult["Item"]["Author_LAST_NAME"] = $GLOBALS["USER"]->GetLastName();
		$arResult["Item"]["Author_SECOND_NAME"] = $GLOBALS["USER"]->GetParam("SECOND_NAME");
		$arResult["Item"]["Author_LOGIN"] = $GLOBALS["USER"]->GetLogin();
		$arTmpUser = array(
			"NAME" => $arResult["Item"]["Author_NAME"],
			"LAST_NAME" => $arResult["Item"]["Author_LAST_NAME"],
			"SECOND_NAME" => $arResult["Item"]["Author_SECOND_NAME"],
			"LOGIN" => $arResult["Item"]["Author_LOGIN"],
		);		
		$arResult["Item"]["Author"] = CUser::FormatName($arParams['NAME_TEMPLATE'], $arTmpUser, $bUseLogin);		
	}
}

//echo "<pre>".print_r($arResult, true)."</pre>";

$this->IncludeComponentTemplate();
?>