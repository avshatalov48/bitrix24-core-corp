<?php
// DEPRECATED! use tasks.interface.filter
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_FOUND"));
	return;
}

//$arParams['USER_ID'] = intval($arResult['USER_ID']);
$arParams['GROUP_ID'] = intval($arParams['GROUP_ID']);
$arParams["ROLE_FILTER_SUFFIX"] = htmlspecialcharsbx($arParams["ROLE_FILTER_SUFFIX"]);

if($arParams['CHECK_TASK_IN'] != 'R' && $arParams['CHECK_TASK_IN'] != 'S')
	$arParams['CHECK_TASK_IN'] = 'A';

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arResult["ADVANCED_STATUSES"] = array(
	array("TITLE" => GetMessage("TASKS_FILTER_ALL"), "FILTER" => array()),
	array(
		"TITLE" => GetMessage("TASKS_FILTER_ACTIVE"),
		"FILTER" => array("STATUS" => array(
			CTasks::METASTATE_VIRGIN_NEW,
			CTasks::METASTATE_EXPIRED,
			CTasks::STATE_NEW,
			CTasks::STATE_PENDING,
			CTasks::STATE_IN_PROGRESS
	))),
	array(
		"TITLE" => GetMessage("TASKS_FILTER_NEW"),
		"FILTER" => array("STATUS" => array(
			CTasks::METASTATE_VIRGIN_NEW,
			CTasks::STATE_NEW
	))),
	array(
		"TITLE" => GetMessage("TASKS_FILTER_IN_CONTROL"),
		"FILTER" => array("STATUS" => array(
			CTasks::STATE_SUPPOSEDLY_COMPLETED,
			CTasks::STATE_DECLINED
	))),
	array("TITLE" => GetMessage("TASKS_FILTER_IN_PROGRESS"), "FILTER" => array("STATUS" => CTasks::STATE_IN_PROGRESS)),
	array("TITLE" => GetMessage("TASKS_FILTER_ACCEPTED"), "FILTER" => array("STATUS" => CTasks::STATE_PENDING)),
	array("TITLE" => GetMessage("TASKS_FILTER_OVERDUE"), "FILTER" => array("STATUS" => CTasks::METASTATE_EXPIRED)),
	array("TITLE" => GetMessage("TASKS_FILTER_DELAYED"), "FILTER" => array("STATUS" => CTasks::STATE_DEFERRED)),
	array("TITLE" => GetMessage("TASKS_FILTER_DECLINED"), "FILTER" => array("STATUS" => CTasks::STATE_DECLINED)),
	array(
		"TITLE" => GetMessage("TASKS_FILTER_CLOSED"),
		"FILTER" => array("STATUS" => array(
			CTasks::STATE_SUPPOSEDLY_COMPLETED,
			CTasks::STATE_COMPLETED
	)))
);

$arPreDefindFilters = tasksPredefinedFilters($arParams["USER_ID"], $arParams["ROLE_FILTER_SUFFIX"]);
$preDefinedFilterRole = &$arPreDefindFilters["ROLE"];
$preDefinedFilterStatus = &$arPreDefindFilters["STATUS"][0];

$arResult["TASK_TYPE"] = $taskType = ($arParams["GROUP_ID"] > 0 ? "group" : "user");

if ($taskType == "group")
{
	$roleFilter = 7;
	$preDefinedFilterRole[7]["FILTER"] = array();
}
else
{
	if (isset($_GET["FILTERR"]) && array_key_exists($_GET["FILTERR"], $preDefinedFilterRole))
	{
		$roleFilter = intval($_GET["FILTERR"]);
	}
	elseif (isset($_SESSION["FILTER"]["FILTERR"]) && array_key_exists($_SESSION["FILTER"]["FILTERR"], $preDefinedFilterRole))
	{
		$roleFilter = intval($_SESSION["FILTER"]["FILTERR"]);
	}
	else
	{
		$roleFilter = 0;
	}
}
$_SESSION["FILTER"]["FILTERR"] = $roleFilter;

$preDefinedFilterRole[$roleFilter]["SELECTED"] = ($arParams["HIGHLIGHT_CURRENT"] == "Y");

if ($roleFilter == 4 || $roleFilter == 5)
{
	$preDefinedFilterStatus = &$arPreDefindFilters["STATUS"][1];
}

if (isset($_GET["FILTERS"]) && array_key_exists($_GET["FILTERS"], $preDefinedFilterStatus) && $arParams['CHECK_TASK_IN'] != 'R')
{
	$statusFilter = intval($_GET["FILTERS"]);
}
elseif (isset($_SESSION["FILTER"]["FILTERS"]) && array_key_exists($_SESSION["FILTER"]["FILTERS"], $preDefinedFilterStatus) && $arParams['CHECK_TASK_IN'] != 'R')
{
	$statusFilter = intval($_SESSION["FILTER"]["FILTERS"]);
}
else
{
	$statusFilter = 0;
}
$_SESSION["FILTER"]["FILTERS"] = $statusFilter;

$preDefinedFilterStatus[$statusFilter]["SELECTED"] = ($arParams["HIGHLIGHT_CURRENT"] == "Y");

// calculate items count for each filter
$obCache = new CPHPCache();
$lifeTime = CTasksTools::CACHE_TTL_UNLIM;
$cacheDir = "/tasks/oldfilter";
$arFiltersCount = array();
$changed = false;
$arCacheTags = array();

$cacheID = md5(serialize($arParams["COMMON_FILTER"]).\Bitrix\Tasks\Util\User::getId()."user".$arParams["USER_ID"].$arParams['CHECK_TASK_IN']);
$arCacheTags[] = "tasks_user_".$arParams["USER_ID"];

if ($taskType == "group")
{
	$cacheID .= '|' . (int) $arParams["GROUP_ID"];
	$arCacheTags[] = "tasks_group_" . (int) $arParams["GROUP_ID"];
}

if(defined('BX_COMP_MANAGED_CACHE') && $obCache->InitCache($lifeTime, $cacheID, $cacheDir))
{
	$arFiltersCount = $obCache->GetVars();
}

if($arParams['CHECK_TASK_IN'] == 'R' || $arParams['CHECK_TASK_IN'] == 'A')
{
	foreach($preDefinedFilterRole as $key=>$f)
	{
		$sf =  $arPreDefindFilters["STATUS"][$f["STATUS_FILTER"]];
		if (!array_key_exists($statusFilter, (array)$sf))
		{
			$statusFilter = 0;
		}

		$arFiltersCount = (array)$arFiltersCount;

		if (is_array($arFiltersCount)
			&& !array_key_exists($key, $arFiltersCount)
			&& !array_key_exists($statusFilter, $arFiltersCount[$key])
		)
		{
			$arFiltersCount[$key][$statusFilter] = CTasks::GetCountInt(array_merge($f["FILTER"], $arParams["COMMON_FILTER"], $sf[$statusFilter]["FILTER"]));
			$changed = true;
		}
		$preDefinedFilterRole[$key]["COUNT"] = $arFiltersCount[$key][$statusFilter];
	}
}

if($arParams['CHECK_TASK_IN'] == 'S' || $arParams['CHECK_TASK_IN'] == 'A')
{
	foreach($preDefinedFilterStatus as $key=>$f)
	{
		if (!array_key_exists($roleFilter, $preDefinedFilterRole))
		{
			$roleFilter = 0;
		}
		if (!isset($arFiltersCount[$roleFilter][$key]))
		{
			$arFiltersCount[$roleFilter][$key] = CTasks::GetCountInt(array_merge($f["FILTER"], $arParams["COMMON_FILTER"], $preDefinedFilterRole[$roleFilter]["FILTER"]));
			$changed = true;
		}
		$preDefinedFilterStatus[$key]["COUNT"] = $arFiltersCount[$roleFilter][$key];
	}

	if (!isset($arFiltersCount[4][1]))
	{
		$arFiltersCount[4][1] = CTasks::GetCountInt(array_merge($arPreDefindFilters["STATUS"][1][1]["FILTER"], $arParams["COMMON_FILTER"], $preDefinedFilterRole[4]["FILTER"]));
		$changed = true;
	}
	$arResult["EXTRA_COUNT"][0] = $arFiltersCount[4][1];
	if (!isset($arFiltersCount[4][2]))
	{
		$arFiltersCount[4][2] = CTasks::GetCountInt(array_merge($arPreDefindFilters["STATUS"][1][2]["FILTER"], $arParams["COMMON_FILTER"], $preDefinedFilterRole[4]["FILTER"]));
		$changed = true;
	}
	$arResult["EXTRA_COUNT"][1] = $arFiltersCount[4][2];
}

if (defined('BX_COMP_MANAGED_CACHE') && $changed)
{
	$obCache->Clean($cacheID, $cacheDir);
	if ($obCache->StartDataCache())
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->StartTagCache($cacheDir);

		foreach ($arCacheTags as $cacheTag)
			$CACHE_MANAGER->RegisterTag($cacheTag);

		$CACHE_MANAGER->EndTagCache();
		$obCache->EndDataCache($arFiltersCount);
	}
}

$arResult["PREDEFINED_FILTERS"] = array(
	"ROLE" => $preDefinedFilterRole,
	"STATUS" => $preDefinedFilterStatus
);

$this->IncludeComponentTemplate();
?>