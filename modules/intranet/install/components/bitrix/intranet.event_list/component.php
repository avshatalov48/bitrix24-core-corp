<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arParams["IBLOCK_ID"] = trim($arParams["IBLOCK_ID"]);
$arParams["IBLOCK_SECTION_ID"] = intVal($arParams["IBLOCK_SECTION_ID"]);
$arParams['B_CUR_USER_LIST'] = $arParams['B_CUR_USER_LIST'] == 'Y';
$arParams["FUTURE_MONTH_COUNT"] = intVal($arParams["FUTURE_MONTH_COUNT"]);

$curUserId = $USER->IsAuthorized() ? $USER->GetID() : '';
$from_ = (strlen($arParams["INIT_DATE"]) == 0 && strpos($arParams["INIT_DATE"], '.') === false) ? date("Ymd") : 0;

$calendar2 = COption::GetOptionString("intranet", "calendar_2", "N") == "Y";
if ($calendar2 && CModule::IncludeModule("calendar"))
{
	if ($arParams['B_CUR_USER_LIST'] == 'Y')
		$type = 'user';
	else
		$type = CCalendar::GetTypeByExternalId('iblock_'.$arParams['IBLOCK_ID']);

	if ($type)
	{
		$arParams["CALENDAR_TYPE"] = $type;
		$arParams["RETURN_ARRAY"] = 'Y';
		$arResult = $APPLICATION->IncludeComponent("bitrix:calendar.events.list", "", $arParams);
		$this->IncludeComponentTemplate();
		return;
	}
}

if($this->StartResultCache(false, array($arParams['B_CUR_USER_LIST']? $curUserId: 0, $from_, $arParams['EVENTS_COUNT'])))
{
	if(!CModule::IncludeModule("intranet") || !class_exists("CEventCalendar"))
	{
		$this->AbortResultCache();
		ShowError(GetMessage("EC_INTRANET_MODULE_NOT_INSTALLED"));
		return;
	}

	if(!CModule::IncludeModule("iblock"))
	{
		$this->AbortResultCache();
		ShowError(GetMessage("EC_IBLOCK_MODULE_NOT_INSTALLED"));
		
		return;
	}

	CModule::IncludeModule("socialnetwork");

	// Limits
	if (strlen($arParams["INIT_DATE"]) > 0 && strpos($arParams["INIT_DATE"], '.') !== false)
		$ts = MakeTimeStamp($arParams["INIT_DATE"], getTSFormat());
	else
		$ts = MakeTimeStamp(date(getDateFormat(false)), getTSFormat());
	$fromLimit = date(getDateFormat(false), $ts);
	$toLimit = date(getDateFormat(false), mktime(0, 0, 0, date("m", $ts) + $arParams["FUTURE_MONTH_COUNT"], date("d", $ts), date("Y", $ts)));

	$arResult['ITEMS'] = array();
	$arEvents = CEventCalendar::GetNearestEventsList(array('bCurUserList' => $arParams['B_CUR_USER_LIST'], 'fromLimit' => $fromLimit, 'toLimit' => $toLimit, 'iblockId' => $arParams["IBLOCK_ID"], 'iblockSectionId' => $arParams["IBLOCK_SECTION_ID"]));

	if ($arEvents == 'access_denied')
	{
		$arResult['ACCESS_DENIED'] = true;
	}
	elseif ($arEvents == 'inactive_feature')
	{
		$arResult['INACTIVE_FEATURE'] = true;
	}
	elseif (is_array($arEvents))
	{
		$limitTromTS = MakeTimeStamp($fromLimit, getTSFormat());
		if (strpos($arParams['DETAIL_URL'], '?') !== FALSE)
			$arParams['DETAIL_URL'] = substr($arParams['DETAIL_URL'], 0, strpos($arParams['DETAIL_URL'], '?'));
		$arParams['DETAIL_URL'] = str_replace('#user_id#', $curUserId, strtolower($arParams['DETAIL_URL']));

		for ($i = 0, $l = count($arEvents); $i < $l; $i++)
		{
			$arEvents[$i]['_FROM_TS'] = MakeTimeStamp($arEvents[$i]['DATE_FROM'], getTSFormat());
			if ($arEvents[$i]['_FROM_TS'] < $limitTromTS)
				continue;

			$arEvents[$i]['_DETAIL_URL'] = $arParams['DETAIL_URL'].'?EVENT_ID='.$arEvents[$i]['ID'].'&EVENT_DATE='.$arEvents[$i]['DATE_FROM'];
			if ($arEvents[$i]['STATUS'] && $arEvents[$i]['STATUS'] == 'Q')
			{
				$arEvents[$i]['_ADD_CLASS'] = ' calendar-not-confirmed';
				$arEvents[$i]['_Q_ICON'] = '<span class="calendar-reminder" title="'.GetMessage('EC_NOT_CONFIRMED').'">[?]</span>';
			}
			else
			{
				$arEvents[$i]['_ADD_CLASS'] = '';
				$arEvents[$i]['_Q_ICON'] = '';
			}
			if ($arEvents[$i]['IMPORTANCE'] == 'high')
				$arEvents[$i]['_ADD_CLASS'] = ' imortant-event';

			if ($arEvents[$i]['_FROM_TS'] >= $limit_from_ts)
				$arResult['ITEMS'][] = $arEvents[$i];
		}

		usort($arResult['ITEMS'], 'eventsSort');
		array_splice($arResult['ITEMS'], intVal($arParams['EVENTS_COUNT']));
	}

	$this->SetResultCacheKeys(array());
	$this->IncludeComponentTemplate();
}
?>