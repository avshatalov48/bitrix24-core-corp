<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */

if (!$USER->CanDoOperation("controller_member_history_view") || !CModule::IncludeModule("controller"))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

IncludeModuleLangFile(__FILE__);

$sTableID = "t_controller_member_history";
$lAdmin = new CAdminUiList($sTableID);

$filterFields = array(
	array(
		"id" => "CONTROLLER_MEMBER_ID",
		"name" => GetMessage("CTRL_MEMB_HIST_CONTROLLER_MEMBER_ID"),
		"filterable" => "=",
		"default" => true,
	),
	array(
		"id" => "FIELD",
		"name" => GetMessage("CTRL_MEMB_HIST_FIELD"),
		"type" => "list",
		"items" => array(
			"CONTROLLER_GROUP_ID" => GetMessage("CTRL_MEMB_HIST_CONTROLLER_GROUP_ID"),
			"SITE_ACTIVE" => GetMessage("CTRL_MEMB_HIST_SITE_ACTIVE"),
			"NAME" => GetMessage("CTRL_MEMB_HIST_NAME"),
			"ACTIVE" => GetMessage("CTRL_MEMB_HIST_ACTIVE"),
		),
		"filterable" => "=",
		"default" => true,
	),
);

$arFilter = array();
$lAdmin->AddFilter($filterFields, $arFilter);
foreach ($arFilter as $k => $v)
{
	if ($v == '')
		unset($arFilter[$k]);
}

$arHeaders = array(
	array(
		"id" => "CREATED_DATE",
		"content" => GetMessage("CTRL_MEMB_HIST_CREATED_DATE"),
		"default" => true,
	),
	array(
		"id" => "FIELD",
		"content" => GetMessage("CTRL_MEMB_HIST_FIELD"),
		"default" => true,
	),
	array(
		"id" => "USER_ID",
		"content" => GetMessage("CTRL_MEMB_HIST_USER_ID"),
		"default" => true,
	),
	array(
		"id" => "FROM_VALUE",
		"content" => GetMessage("CTRL_MEMB_HIST_FROM_VALUE"),
		"default" => true,
	),
	array(
		"id" => "TO_VALUE",
		"content" => GetMessage("CTRL_MEMB_HIST_TO_VALUE"),
		"default" => true,
	),
	array(
		"id" => "NOTES",
		"content" => GetMessage("CTRL_MEMB_HIST_NOTES"),
	),
);

$lAdmin->AddHeaders($arHeaders);

$arGroups = Array();
$dbr_groups = CControllerGroup::GetList(Array("SORT" => "ASC"));
while ($ar_groups = $dbr_groups->GetNext())
	$arGroups[$ar_groups["ID"]] = $ar_groups["NAME"];

$rsData = CControllerMember::GetLog($arFilter);
$rsData = new CAdminUiResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->SetNavigationParams($rsData);

while ($arRes = $rsData->Fetch())
{
	$row =& $lAdmin->AddRow($arRes['ID'], $arRes);

	$row->AddViewField("CREATED_DATE", htmlspecialcharsEx($arRes['CREATED_DATE']));
	adminListAddUserLink($row, "USER_ID", $arRes['USER_ID'], $arRes['USER_ID_USER']);

	switch ($arRes['FIELD'])
	{
	case "CONTROLLER_GROUP_ID":
		$row->AddViewField("FIELD", GetMessage("CTRL_MEMB_HIST_CONTROLLER_GROUP_ID"));
		$row->AddViewField("FROM_VALUE", '[<a href="controller_group_edit.php?ID='.intval($arRes['FROM_VALUE']).'&amp;lang='.LANGUAGE_ID.'">'.htmlspecialcharsEx($arRes['FROM_VALUE']).'</a>] '.$arGroups[$arRes['FROM_VALUE']]);
		$row->AddViewField("TO_VALUE", '[<a href="controller_group_edit.php?ID='.intval($arRes['TO_VALUE']).'&amp;lang='.LANGUAGE_ID.'">'.htmlspecialcharsEx($arRes['TO_VALUE']).'</a>] '.$arGroups[$arRes['TO_VALUE']]);
		break;
	case "SITE_ACTIVE":
		$row->AddViewField("FIELD", GetMessage("CTRL_MEMB_HIST_SITE_ACTIVE"));
		$row->AddViewField("FROM_VALUE", $arRes['FROM_VALUE'] == "Y"? GetMessage("MAIN_YES"): GetMessage("MAIN_NO"));
		$row->AddViewField("TO_VALUE", $arRes['TO_VALUE'] == "Y"? GetMessage("MAIN_YES"): GetMessage("MAIN_NO"));
		break;
	case "NAME":
		$row->AddViewField("FIELD", GetMessage("CTRL_MEMB_HIST_NAME"));
		break;
	case "ACTIVE":
		$row->AddViewField("FIELD", GetMessage("CTRL_MEMB_HIST_ACTIVE"));
		$row->AddViewField("FROM_VALUE", $arRes['FROM_VALUE'] == "Y"? GetMessage("MAIN_YES"): GetMessage("MAIN_NO"));
		$row->AddViewField("TO_VALUE", $arRes['TO_VALUE'] == "Y"? GetMessage("MAIN_YES"): GetMessage("MAIN_NO"));
		break;
	}
	$row->AddViewField("NOTES", htmlspecialcharsEx($arRes['NOTES']));
}

$lAdmin->AddFooter(
	array(
		array("title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value" => $rsData->SelectedRowsCount()),
	)
);

$aContext = array(
	array(
		"TEXT" => GetMessage("CTRL_MEMB_HIST_BACK"),
		"LINK" => "controller_member_edit.php?ID=".intval($arFilter['=CONTROLLER_MEMBER_ID'])."&lang=".LANGUAGE_ID,
		"TITLE" => GetMessage("CTRL_MEMB_HIST_BACK_TITLE"),
		"ICON" => "btn_edit",
	),
);

$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRL_MEMB_HIST_TITLE"));

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
