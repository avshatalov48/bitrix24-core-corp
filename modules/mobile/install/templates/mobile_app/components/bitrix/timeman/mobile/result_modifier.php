<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var CBitrixComponent $this
 * @var array $arParams
 * @var array $arResult
 * @var CUser $USER
 * @var CDatabase $DB
 * @var CMain $APPLICATION
 */
if (empty($arParams["DATE_TIME_FORMAT"]) ||  $arParams["DATE_TIME_FORMAT"] == "FULL")
	$arParams["DATE_TIME_FORMAT"]= $DB->DateFormatToPHP(FORMAT_DATETIME);
$arParams["DATE_TIME_FORMAT"] = preg_replace('/[\/.,\s:][s]/', '', $arParams["DATE_TIME_FORMAT"]);

if (!$arParams["TIME_FORMAT"])
	$arParams["TIME_FORMAT"] = preg_replace(array('/[dDjlFmMnYyo]/', '/^[\/.,\s]+/', '/[\/.,\s]+$/'), "", $arParams["DATE_TIME_FORMAT"]);
if (!$arParams["DATE_FORMAT"])
	$arParams["DATE_FORMAT"] = trim(str_replace($arParams["TIME_FORMAT"], "", $arParams["DATE_TIME_FORMAT"]));

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

$statusClass = "";
$stateTimer = 0;
$pauseTimer = intval($arResult["START_INFO"]["INFO"]["TIME_LEAKS"]);
if (array_key_exists("LAST_PAUSE", $arResult["START_INFO"]) && !array_key_exists("DATE_FINISH", $arResult["START_INFO"]["LAST_PAUSE"]))
{
	$pauseTimer += time() - $arResult["START_INFO"]["LAST_PAUSE"]["DATE_START"];
}
if ($arResult["START_INFO"]["STATE"] == "OPENED")
{
	$statusClass = "opened";
	$stateTimer = time() - $arResult["START_INFO"]["INFO"]["DATE_START"] - $arResult["START_INFO"]["INFO"]["TIME_LEAKS"];
}
else
{
	if ($arResult["START_INFO"]["INFO"] &&
		$arResult["START_INFO"]["INFO"]["DATE_START"] &&
		$arResult["START_INFO"]["INFO"]["DATE_FINISH"])
	{
		$stateTimer = ($arResult["START_INFO"]["INFO"]["DATE_FINISH"] - $arResult["START_INFO"]["INFO"]["DATE_START"] - $arResult["START_INFO"]["INFO"]["TIME_LEAKS"]);
	}
	if ($arResult["START_INFO"]["STATE"] == "CLOSED")
	{
		if ($arResult["START_INFO"]["CAN_OPEN"] == "REOPEN" || !$arResult["START_INFO"]["CAN_OPEN"])
		{
			$statusClass = "completed";
		}
		else
		{
			$statusClass = "start";
			$stateTimer = 0;
			$pauseTimer = 0;
		}
	}
	elseif ($arResult["START_INFO"]["STATE"] == "PAUSED")
	{
		$statusClass = "paused";
	}
	elseif ($arResult["START_INFO"]["STATE"] == "EXPIRED")
	{
		$statusClass = "expired";
	}
}

$bInfoRow = $arResult["START_INFO"]['PLANNER']["EVENT_TIME"] != '' || $arResult["START_INFO"]['PLANNER']["TASKS_COUNT"] > 0;
$bTaskTimeRow = isset($arResult["START_INFO"]['PLANNER']['TASKS_TIMER']) && is_array($arResult["START_INFO"]['PLANNER']['TASKS_TIMER']) && $arResult["START_INFO"]['PLANNER']['TASKS_TIMER']['TIMER_STARTED_AT'] > 0;

if($bTaskTimeRow)
{
	$ts = intval($arResult['START_INFO']['PLANNER']['TASK_ON_TIMER']['TIME_SPENT_IN_LOGS']);

	if ($arResult['START_INFO']['PLANNER']['TASKS_TIMER']['TIMER_STARTED_AT'] > 0)
		$ts += (time() - $arResult['START_INFO']['PLANNER']['TASKS_TIMER']['TIMER_STARTED_AT']);

	$taskTime = sprintf("%02d:%02d:%02d", floor($ts/3600), floor($ts/60) % 60, $ts%60);

	if($arResult['START_INFO']['PLANNER']['TASK_ON_TIMER']['TIME_ESTIMATE'] > 0)
	{
		$ts = $arResult['START_INFO']['PLANNER']['TASK_ON_TIMER']['TIME_ESTIMATE'];
		$taskTime .= " / " . sprintf("%02d:%02d", floor($ts/3600), floor($ts/60) % 60);
	}
}
$arReport = array();
$DailyReportIsRequiredError = false;
$arResult["START_INFO"]['REPORT'] = '';
$arResult["START_INFO"]['REPORT_TS'] = '';
if (($arResult["START_INFO"]['REPORT_REQ'] != "A" || $request->getQuery("report") == "Y") && $arResult["START_INFO"]["ID"] > 0)
{
	$arReport = CTimeManUser::instance()->SetReport('', 0, $arResult["START_INFO"]["ID"]);
	if (is_array($arReport))
	{
		$arResult["START_INFO"]['REPORT'] = $arReport['REPORT'];
		$arResult["START_INFO"]['REPORT_TS'] = MakeTimeStamp($arReport['TIMESTAMP_X']);
	}
}

if (CModule::IncludeModule("pull"))
{
	CPullWatch::Add($USER->GetID(), 'TIMEMANWORKINGDAY_'.$USER->GetID(), true);
}
$arResult["statusClass"] = $statusClass;
$arResult["stateTimer"] = $stateTimer;
$arResult["pauseTimer"] = $pauseTimer;