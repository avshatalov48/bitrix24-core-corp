<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Text\Emoji;

if (!CModule::IncludeModule("tasks"))
{
	ShowError(GetMessage("TASKS_MODULE_NOT_INSTALLED"));
	return;
}
if (!CModule::IncludeModule('report'))
{
	ShowError(GetMessage('REPORT_MODULE_NOT_FOUND'));
	return;
}
if (!\Bitrix\Tasks\Util\User::isAuthorized())
{
	$APPLICATION->AuthForm("");
	return;
}

CModule::IncludeModule('iblock');

global $APPLICATION;

$arParams["TASK_VAR"] = trim($arParams["TASK_VAR"] ?? '');
if ($arParams["TASK_VAR"] == '')
	$arParams["TASK_VAR"] = "task_id";

$arParams["GROUP_VAR"] = trim($arParams["GROUP_VAR"] ?? '');
if ($arParams["GROUP_VAR"] == '')
	$arParams["GROUP_VAR"] = "group_id";

$arParams["ACTION_VAR"] = trim($arParams["ACTION_VAR"] ?? '');
if ($arParams["ACTION_VAR"] == '')
	$arParams["ACTION_VAR"] = "action";

if ($arParams["PAGE_VAR"] == '')
	$arParams["PAGE_VAR"] = "page";

if ($arParams["NAME_TEMPLATE"] == '')
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();

$arParams["USER_ID"] = intval($arParams["USER_ID"]) > 0 ? intval($arParams["USER_ID"]) : \Bitrix\Tasks\Util\User::getId();

$arParams["GROUP_ID"] = intval($arParams["GROUP_ID"] ?? null);

$taskType = ($arParams["GROUP_ID"] > 0 ? "group" : "user");

//user paths
$arParams["PATH_TO_USER_TASKS"] = trim($arParams["PATH_TO_USER_TASKS"]);
if ($arParams["PATH_TO_USER_TASKS"] == '')
{
	$arParams["PATH_TO_USER_TASKS"] = COption::GetOptionString("tasks", "paths_task_user", null, SITE_ID);
}
$arParams["PATH_TO_USER_TASKS_TASK"] = trim($arParams["PATH_TO_USER_TASKS_TASK"]);
if ($arParams["PATH_TO_USER_TASKS_TASK"] == '')
{
	$arParams["PATH_TO_USER_TASKS_TASK"] = COption::GetOptionString("tasks", "paths_task_user_action", null, SITE_ID);
}
$arParams["PATH_TO_USER_TASKS_REPORT"] = trim($arParams["PATH_TO_USER_TASKS_REPORT"]);
if ($arParams["PATH_TO_USER_TASKS_REPORT"] == '')
{
	$arParams["PATH_TO_USER_TASKS_REPORT"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_report&".$arParams["USER_VAR"]."=#user_id#");
}
$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = trim($arParams["PATH_TO_USER_TASKS_TEMPLATES"]);
if ($arParams["PATH_TO_USER_TASKS_TEMPLATES"] == '')
{
	$arParams["PATH_TO_USER_TASKS_TEMPLATES"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user_tasks_templates&".$arParams["USER_VAR"]."=#user_id#");
}

//group paths
$arParams["PATH_TO_GROUP_TASKS"] = trim($arParams["PATH_TO_GROUP_TASKS"] ?? '');
if ($arParams["PATH_TO_GROUP_TASKS"] == '')
{
	$arParams["PATH_TO_GROUP_TASKS"] = COption::GetOptionString("tasks", "paths_task_group", htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks&".$arParams["GROUP_VAR"]."=#group_id#"), SITE_ID);
}
$arParams["PATH_TO_GROUP_TASKS_TASK"] = trim($arParams["PATH_TO_GROUP_TASKS_TASK"] ?? '');
if ($arParams["PATH_TO_GROUP_TASKS_TASK"] == '')
{
	$arParams["PATH_TO_GROUP_TASKS_TASK"] = COption::GetOptionString("tasks", "paths_task_group_action", htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks_task&".$arParams["GROUP_VAR"]."=#group_id#&".$arParams["TASK_VAR"]."=#task_id#&".$arParams["ACTION_VAR"]."=#action#"), SITE_ID);
}
$arParams["PATH_TO_GROUP_TASKS_REPORT"] = trim($arParams["PATH_TO_GROUP_TASKS_REPORT"] ?? '');
if ($arParams["PATH_TO_GROUP_TASKS_REPORT"] == '')
{
	$arParams["PATH_TO_GROUP_TASKS_REPORT"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=group_tasks_report&".$arParams["GROUP_VAR"]."=#group_id#");
}

if ($taskType == "user")
{
	$arParams["PATH_TO_TASKS"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TASK"]);
	$arParams["PATH_TO_REPORTS"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_REPORT"]);

	$rsUser = CUser::GetByID($arParams["USER_ID"]);
	if ($user = $rsUser->Fetch())
	{
		$arResult["USER"] = $user;
	}
	else
	{
		return;
	}
}
else
{
	$arParams["PATH_TO_TASKS"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS"]);
	$arParams["PATH_TO_TASKS_TASK"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS_TASK"]);
	$arParams["PATH_TO_REPORTS"] = str_replace("#group_id#", $arParams["GROUP_ID"], $arParams["PATH_TO_GROUP_TASKS_REPORT"]);

	$arResult["GROUP"] = CSocNetGroup::GetByID($arParams["GROUP_ID"]);
	if (!$arResult["GROUP"])
	{
		return;
	}
}

$arParams["PATH_TO_TEMPLATES"] = str_replace("#user_id#", $arParams["USER_ID"], $arParams["PATH_TO_USER_TASKS_TEMPLATES"]);

$arFilter = array();

// filter
if (($_GET["F_CANCEL"] ?? null) == "Y")
{
	$_SESSION["FILTER"] = array();
}

$phpDateFormat = $DB->DateFormatToPHP(CSite::GetDateFormat("SHORT"));

if (($_GET["F_FILTER"] ?? null) == "Y")
{
	$_SESSION["FILTER"]["F_FILTER"] = "Y";
	$_SESSION["FILTER"]["F_DATE_TYPE"] = htmlspecialcharsbx($_GET["F_DATE_TYPE"]);
	$_SESSION["FILTER"]["F_DATE_DAYS"] = htmlspecialcharsbx($_GET["F_DATE_DAYS"]);
	$_SESSION["FILTER"]["F_DATE_FROM"] = htmlspecialcharsbx($_GET["F_DATE_FROM"]);
	$_SESSION["FILTER"]["F_DATE_TO"] = htmlspecialcharsbx($_GET["F_DATE_TO"]);
	$_SESSION["FILTER"]["F_DEPARTMENT_ID"] = (int)$_GET["F_DEPARTMENT_ID"];
	$_SESSION["FILTER"]["F_GROUP_ID"] = (int)$_GET["F_GROUP_ID"];
	$_SESSION["FILTER"]["F_RESPONSIBLE_ID"] = (int)$_GET["F_RESPONSIBLE_ID"];
}
else
{
	$arResult["FILTER"]["F_DATE_TYPE"] = "month";

	if ($taskType == "group")
	{
		$arResult["FILTER"]["F_GROUP_ID"] = $arFilter["GROUP_ID"] = $arParams["GROUP_ID"];
	}
	elseif ($arParams["USER_ID"] != \Bitrix\Tasks\Util\User::getId())
	{
		$arResult["FILTER"]["F_RESPONSIBLE_ID"] = $arFilter["RESPONSIBLE_ID"] = $arParams["USER_ID"];
	}
}

if (($_SESSION["FILTER"]["F_FILTER"] ?? null) == "Y")
{
	$arResult["FILTER"] = $_SESSION["FILTER"];
}

switch ($arResult["FILTER"]["F_DATE_TYPE"])
{
	case "month-ago":
		$arFilter["PERIOD"]["START"] = date($phpDateFormat, strtotime(date("Y-m-01", strtotime("-1 month"))));
		$arFilter["PERIOD"]["END"] = date($phpDateFormat, strtotime(date("Y-m-t", strtotime("-1 month"))));
		break;
	case "week":
		$arFilter["PERIOD"]["START"] = date($phpDateFormat, strtotime("-".((date("w") == 0 ? 7 : date("w")) - 1)." day"));
		break;
	case "week-ago":
		$arFilter["PERIOD"]["START"] = date($phpDateFormat, strtotime("-".((date("w") == 0 ? 7 : date("w")) + 6)." day"));
		$arFilter["PERIOD"]["END"] = date($phpDateFormat, strtotime("-".(date("w") == 0 ? 7 : date("w"))." day"));
		break;
	case "days":
		if (intval($arResult["FILTER"]["F_DATE_DAYS"]) > 0)
		{
			$arFilter["PERIOD"]["START"] = date($phpDateFormat, strtotime(date("Y-m-d")." -".intval($arResult["FILTER"]["F_DATE_DAYS"])." day"));
		}
		break;
	case "after":
		if ($arResult["FILTER"]["F_DATE_FROM"])
		{
			$arFilter["PERIOD"]["START"] = $_SESSION["F_DATE_FROM"];
		}
		break;
	case "before":
		if ($arResult["FILTER"]["F_DATE_TO"])
		{
			$arFilter["PERIOD"]["END"] = $_SESSION["F_DATE_TO"];
		}
		break;
	case "interval":
		if ($arResult["FILTER"]["F_DATE_FROM"])
		{
			$arFilter["PERIOD"]["START"] = $_SESSION["F_DATE_FROM"];
		}
		if ($_SESSION["FILTER"]["F_DATE_TO"])
		{
			$arFilter["PERIOD"]["END"] = $_SESSION["F_DATE_TO"];
		}
		break;
	default:
		$arResult["FILTER"]["F_DATE_TYPE"] = "month";
		$arFilter["PERIOD"]["START"] = date($phpDateFormat, strtotime(date("Y-m-01")));
		break;
}
if (intval($arResult["FILTER"]["F_DEPARTMENT_ID"] ?? null) > 0)
{
	$rsSection = CIBlockSection::GetByID(intval($arResult["FILTER"]["F_DEPARTMENT_ID"]));

	$arDeps = array();
	if($arSection = $rsSection->Fetch())
	{
		$arDeps[] = $arSection["ID"];

		$arSubDepsFilter = array(
			'IBLOCK_ID' => $IBlockID,
			'GLOBAL_ACTIVE' => 'Y',
			'>LEFT_MARGIN' => $arSection['LEFT_MARGIN'],
			'<RIGHT_MARGIN' => $arSection['RIGHT_MARGIN'],
		);
		$rsChildSections = CIBlockSection::GetList(array('left_margin' => 'asc'), $arSubDepsFilter, false, array("ID"));
		while ($arChildSection = $rsChildSections->GetNext())
		{
			$arDeps[] = $arChildSection["ID"];
		}
	}
	if (sizeof($arDeps))
	{
		$arFilter["DEPARTMENT_ID"] = $arDeps;
	}
}

if (intval($arResult["FILTER"]["F_GROUP_ID"] ?? null) > 0)
{
	$arFilter["GROUP_ID"] = intval($arResult["FILTER"]["F_GROUP_ID"]);
}

if (intval($arResult["FILTER"]["F_RESPONSIBLE_ID"] ?? null) > 0)
{
	$arFilter["RESPONSIBLE_ID"] = intval($arResult["FILTER"]["F_RESPONSIBLE_ID"]);
}

$arResult["START"] = htmlspecialcharsEx($arFilter["PERIOD"]["START"]);
$arResult["END"] = htmlspecialcharsEx($arFilter["PERIOD"]["END"] ?? '');

// order
if (isset($_GET["SORTF"]) && in_array($_GET["SORTF"], array("RESPONSIBLE", "NEW", "OPEN", "CLOSED", "OVERDUED", "MARKED", "POSITIVE")) && isset($_GET["SORTD"]) && in_array($_GET["SORTD"], array("ASC", "DESC")))
{
	$arResult["ORDER"] = $arOrder = array($_GET["SORTF"] => $_GET["SORTD"]);
}
else
{
	$arResult["ORDER"] = $arOrder = array("RESPONSIBLE" => "ASC");
}

$arParams["ITEMS_COUNT"] = 3;
$rsReports = CTaskReport::GetList(
	$arOrder,
	$arFilter,
	array(
		'NAV_PARAMS' => array(
			'nPageSize' => intval($arParams["ITEMS_COUNT"]) > 0 ? $arParams["ITEMS_COUNT"] : 10,
			'bDescPageNumbering' => false
		)
	)
);

$arResult["NAV_STRING"] = $rsReports->GetPageNavString("", "arrows");
$arResult["NAV_PARAMS"] = $rsReports->GetNavParams();

$arResult["REPORTS"] = array();
$arResult["DEPARTMENTS"] = array();

$IBlockID = COption::GetOptionInt('intranet', 'iblock_structure', 0);

// subordinate departments
$rsSections = CIBlockSection::GetList(array(), array("IBLOCK_ID"=> $IBlockID, "UF_HEAD"=>\Bitrix\Tasks\Util\User::getId(), 'ACTIVE' => 'Y'), false, array('UF_HEAD'));
$arResult["SUBORDINATE_DEPS"] = array();
while($arSection = $rsSections->Fetch())
{
	$arSectionIDs[] = $arSection["ID"];

	$arSubDepsFilter = array(
		'IBLOCK_ID' => $IBlockID,
		'GLOBAL_ACTIVE' => 'Y',
		'>LEFT_MARGIN' => $arSection['LEFT_MARGIN'],
		'<RIGHT_MARGIN' => $arSection['RIGHT_MARGIN'],
		'!ID' => $arSection['ID']
	);
	$rsChildSections = CIBlockSection::GetList(['left_margin' => 'ASC'], $arSubDepsFilter, false, ['ID', 'NAME', 'DEPTH_LEVEL']);
	while ($arChildSection = $rsChildSections->GetNext())
	{
		$arResult["SUBORDINATE_DEPS"][] = $arChildSection;
	}
}

// groups
$rsGroups = CSocNetGroup::GetList(array("NAME" => "ASC"), array("SITE_ID" => SITE_ID));
$arResult["GROUPS"] = array();
$groupIDs = array();
while($group = $rsGroups->GetNext())
{
	if (!empty($group['NAME']))
	{
		$group['NAME'] = Emoji::decode($group['NAME']);
	}
	if (!empty($group['DESCRIPTION']))
	{
		$group['DESCRIPTION'] = Emoji::decode($group['DESCRIPTION']);
	}

	$arResult["GROUPS"][] = $group;
	$groupIDs[] = $group["ID"];
}

if (sizeof($groupIDs) > 0)
{
	$arGroupsPerms = CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $groupIDs, "tasks", "view");
	foreach ($arResult["GROUPS"] as $key=>$group)
	{
		if (!$arGroupsPerms[$group["ID"]])
		{
			unset($arResult["GROUPS"][$key]);
		}
	}
}

while ($report = $rsReports->GetNext())
{
	$tmp = $report;
	$arResult["REPORTS"][] = $tmp;
	if (!is_set($arResult["DEPARTMENTS"][$report["DEPARTMENT_ID"]] ?? null))
	{
		$arParentFilter = array(
			'IBLOCK_ID' => $IBlockID,
			'GLOBAL_ACTIVE' => 'Y',
			'!LEFT_MARGIN' => $report['LEFT_MARGIN'],
			'!RIGHT_MARGIN' => $report['RIGHT_MARGIN'],
			'!ID' => $report["DEPARTMENT_ID"], // little hack because of the iblock module minor bug
		);
		$rsParentSections = CIBlockSection::GetList(['left_margin' => 'ASC'], $arParentFilter);
		$arResult["DEPARTMENTS"][$report["DEPARTMENT_ID"]]["PARENTS"] = array();
		while($section = $rsParentSections->GetNext())
		{
			$arResult["DEPARTMENTS"][$report["DEPARTMENT_ID"]]["PARENTS"][] = $section;
		}
	}
}
$arDepartmentsFilter = $arFilter;
if (!isset($arDepartmentsFilter["DEPARTMENT_ID"]))
{
	$arDepartmentsFilter["DEPARTMENT_ID"] = array_keys($arResult["DEPARTMENTS"]);
}
if (isset($arDepartmentsFilter["RESPONSIBLE_ID"]))
{
	unset($arDepartmentsFilter["RESPONSIBLE_ID"]);
}
$rsDepartmentsReports = CTaskReport::GetDepartementStats($arDepartmentsFilter);
while ($departmentStats = $rsDepartmentsReports->GetNext())
{
	$arResult["DEPARTMENTS"][$departmentStats["DEPARTMENT_ID"]]["STATS"] = $departmentStats;
}

// whole company
$arCompanyFilter = $arFilter;
if (isset($arCompanyFilter["RESPONSIBLE_ID"]))
{
	unset($arCompanyFilter["RESPONSIBLE_ID"]);
}
if (isset($arCompanyFilter["DEPARTMENT_ID"]))
{
	unset($arCompanyFilter["DEPARTMENT_ID"]);
}
if (isset($arCompanyFilter["GROUP_ID"]))
{
	unset($arCompanyFilter["GROUP_ID"]);
}

$rsCompanyStats = CTaskReport::GetCompanyStats($arCompanyFilter);
if ($companyStats = $rsCompanyStats->GetNext())
{
	$arResult["COMPANY_STATS"] = $companyStats;
}

if ($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(GetMessage("TASKS_EFFICIENCY_REPORT"));
}

if (($arParams["SET_NAVCHAIN"] ?? null) != "N")
{
	if ($taskType == "user")
	{
		$APPLICATION->AddChainItem(CUser::FormatName($arParams["NAME_TEMPLATE"],$arResult["USER"]), CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_USER_PROFILE"], array("user_id" => $arParams["USER_ID"])));
		$APPLICATION->AddChainItem(GetMessage("TASKS_EFFICIENCY_REPORT"));
	}
	else
	{
		$APPLICATION->AddChainItem($arResult["GROUP"]["NAME"], CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_GROUP"], array("group_id" => $arParams["GROUP_ID"])));
		$APPLICATION->AddChainItem(GetMessage("TASKS_EFFICIENCY_REPORT"));
	}
}

$this->IncludeComponentTemplate();
?>