<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!function_exists('__MSLLogGetIds'))
{
	function __MSLLogGetIds(
		$arOrder, $arFilter, $arNavStartParams, $arSelectFields, $arListParams, $bFirstPage, $arParams,
		&$arResult, &$arCrmActivityId, &$arTmpEventsNew
	)
	{
		$dbEventsID = CSocNetLog::GetList(
			$arOrder,
			$arFilter,
			false,
			$arNavStartParams,
			$arSelectFields,
			$arListParams
		);

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
			else
			{
				$arResult["PAGE_NUMBER"] = $dbEventsID->NavPageNomer;
				$arResult["PAGE_NAVNUM"] = $dbEventsID->NavNum;
				$arResult["PAGE_NAVCOUNT"] = $dbEventsID->NavPageCount;
			}
		}

		$cnt = 0;

		while ($arEvents = $dbEventsID->GetNext())
		{
			if (
				(
					in_array($arEvents["EVENT_ID"], array("timeman_entry", "report"))
					&& !IsModuleInstalled("timeman")
				)
				|| (
					in_array($arEvents["EVENT_ID"], array("tasks"))
					&& !IsModuleInstalled("tasks")
				)
				|| (
					in_array($arEvents["EVENT_ID"], array("lists_new_element"))
					&& !IsModuleInstalled("lists")
				)
			)
			{
				continue;
			}

			if (
				$arEvents["EVENT_ID"] == 'crm_activity_add'
				&& intval($arEvents["ENTITY_ID"]) > 0
			)
			{
				$arCrmActivityId[] = intval($arEvents["ENTITY_ID"]);
			}

			$cnt++;
			$arTmpEventsNew[] = $arEvents;
			$arResult["arLogTmpID"][] = $arEvents["ID"];
		}
	}
}
?>