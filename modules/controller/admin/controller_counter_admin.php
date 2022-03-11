<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */

if (!$USER->CanDoOperation("controller_counters_view") || !CModule::IncludeModule("controller"))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

IncludeModuleLangFile(__FILE__);

$sTableID = "t_controller_counter";
$oSort = new CAdminUiSorting($sTableID, "id", "desc");
/** @global string $by */
/** @global string $order */
$lAdmin = new CAdminUiList($sTableID, $oSort);

$arFilterRows = array();

$arGroups = array();
$dbr_groups = CControllerGroup::GetList(array("SORT" => "ASC", "NAME" => "ASC", "ID" => "ASC"));
while ($ar_groups = $dbr_groups->Fetch())
{
	$arGroups[$ar_groups["ID"]] = $ar_groups["NAME"];
}

$filterFields = array(
	array(
		"id" => "CONTROLLER_GROUP_ID",
		"name" => GetMessage("CTRL_CNT_ADMIN_FILTER_GROUP"),
		"type" => "list",
		"items" => $arGroups,
		"params" => array("multiple" => "Y"),
		"filterable" => "=",
		"default" => true,
	),
);

$arFilter = array();
$lAdmin->AddFilter($filterFields, $arFilter);

if ($USER->CanDoOperation("controller_counters_manage") && $lAdmin->EditAction())
{
	foreach ($_POST['FIELDS'] as $ID => $arFields)
	{
		$ID = intval($ID);

		if (!$lAdmin->IsUpdated($ID))
			continue;

		$DB->StartTransaction();
		if (!CControllerCounter::Update($ID, $arFields))
		{
			$e = $APPLICATION->GetException();
			$lAdmin->AddUpdateError(GetMessage("CTRL_CNT_ADMIN_UPDATE_ERROR", array("#ID#" => $ID, "#ERROR#" => $e->GetString())), $ID);
			$DB->Rollback();
		}
		$DB->Commit();
	}
}

$arID = $lAdmin->GroupAction();
if ($arID && $USER->CanDoOperation("controller_counters_manage"))
{
	if ($_REQUEST['action_target'] == 'selected')
	{
		$rsData = CControllerCounter::GetList(array($by => $order), $arFilter);
		while ($arRes = $rsData->Fetch())
			$arID[] = $arRes['ID'];
	}

	foreach ($arID as $ID)
	{
		if ($ID == '')
			continue;
		$ID = intval($ID);

		switch ($_REQUEST['action'])
		{
		case "delete":
			@set_time_limit(0);
			$DB->StartTransaction();
			if (!CControllerCounter::Delete($ID))
			{
				$e = $APPLICATION->GetException();
				$DB->Rollback();
				$lAdmin->AddGroupError(GetMessage("CTRL_CNT_ADMIN_DELETE_ERROR", array("#ID#" => $ID, "#ERROR#" => $e->GetString())), $ID);
			}
			$DB->Commit();
			break;
		}
	}

	if ($lAdmin->hasGroupErrors())
	{
		$adminSidePanelHelper->sendJsonErrorResponse($lAdmin->getGroupErrors());
	}
	else
	{
		$adminSidePanelHelper->sendSuccessResponse();
	}
}

$rsData = CControllerCounter::GetList(Array($by => $order), $arFilter);
$rsData = new CAdminUiResult($rsData, $sTableID);
$rsData->NavStart();
$lAdmin->SetNavigationParams($rsData);

$arHeaders = array(
	array(
		"id" => "ID",
		"content" => GetMessage("CTRL_CNT_ADMIN_ID"),
		"default" => true,
		"sort" => "id",
	),
	array(
		"id" => "NAME",
		"content" => GetMessage("CTRL_CNT_ADMIN_NAME"),
		"default" => true,
		"sort" => "name",
	),
	array(
		"id" => "COUNTER_TYPE",
		"content" => GetMessage("CTRL_CNT_ADMIN_COUNTER_TYPE"),
		"default" => true,
	),
	array(
		"id" => "COUNTER_FORMAT",
		"content" => GetMessage("CTRL_CNT_ADMIN_COUNTER_FORMAT"),
		"default" => true,
	),
	array(
		"id" => "COMMAND",
		"content" => GetMessage("CTRL_CNT_ADMIN_COMMAND"),
		"default" => true,
	),
);

$lAdmin->AddHeaders($arHeaders);

while ($arRes = $rsData->Fetch())
{
	$row = $lAdmin->AddRow($arRes["ID"], $arRes);
	$htmlLink = 'controller_counter_edit.php?ID='.urlencode($arRes['ID']).'&lang='.LANGUAGE_ID;

	$row->AddViewField("ID", '<a href="'.htmlspecialcharsbx($htmlLink).'">'.htmlspecialcharsEx($arRes['ID']).'</a>');
	$row->AddInputField("NAME", array("size" => "35"));
	$row->AddViewField("NAME", '<a href="'.htmlspecialcharsbx($htmlLink).'">'.htmlspecialcharsEx($arRes['NAME']).'</a>');
	$row->AddSelectField("COUNTER_TYPE", CControllerCounter::GetTypeArray());
	$row->AddSelectField("COUNTER_FORMAT", CControllerCounter::GetFormatArray());
	$row->AddViewField("COMMAND", "<pre>".htmlspecialcharsEx($arRes["COMMAND"])."</pre>");
	$row->AddEditField("COMMAND", "<textarea cols=\"80\" rows=\"15\" name=\"".htmlspecialcharsEx("FIELDS[".$arRes["ID"]."][COMMAND]")."\">".htmlspecialcharsbx($arRes["COMMAND"])."</textarea>");

	$arActions = array();

	if ($USER->CanDoOperation("controller_counters_view"))
	{
		$arActions[] = array(
			"TEXT" => GetMessage("CTRL_CNT_ADMIN_MENU_HISTORY"),
			"ACTION" => $lAdmin->ActionRedirect("controller_counter_history.php?COUNTER_ID=".urlencode($arRes["ID"])."&apply_filter=Y&lang=".LANGUAGE_ID),
		);
	}

	if ($USER->CanDoOperation("controller_counters_manage"))
	{
		$arActions[] = array("SEPARATOR" => true);
		$arActions[] = array(
			"ICON" => "edit",
			"DEFAULT" => "Y",
			"TEXT" => GetMessage("CTRL_CNT_ADMIN_MENU_EDIT"),
			"ACTION" => $lAdmin->ActionRedirect("controller_counter_edit.php?ID=".urlencode($arRes["ID"])."&lang=".LANGUAGE_ID),
		);
		$arActions[] = array(
			"ICON" => "delete",
			"TEXT" => GetMessage("CTRL_CNT_ADMIN_MENU_DELETE"),
			"ACTION" => "if(confirm('".GetMessage("CTRL_CNT_ADMIN_MENU_DELETE_ALERT")."')) ".$lAdmin->ActionDoGroup($arRes["ID"], "delete"),
		);
	}
	
	if ($arActions)
	{
		$row->AddActions($arActions);
	}
}

$lAdmin->AddFooter(
	array(
		array(
			"title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"),
			"value" => $rsData->SelectedRowsCount(),
		),
		array(
			"counter" => true,
			"title" => GetMessage("MAIN_ADMIN_LIST_CHECKED"),
			"value" => 0,
		),
	)
);

if ($USER->CanDoOperation("controller_counters_manage"))
{
	$lAdmin->AddGroupActionTable(Array(
			"edit" => true,
			"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
		)
	);

	$aContext = array(
		array(
			"ICON" => "btn_new",
			"TEXT" => GetMessage("MAIN_ADD"),
			"LINK" => "controller_counter_edit.php?lang=".LANGUAGE_ID,
			"TITLE" => GetMessage("MAIN_ADD")
		),
	);
}
else
{
	$lAdmin->bCanBeEdited = false;
	$aContext = array();
}
$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRL_CNT_ADMIN_TITLE"));

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
