<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CUserTypeManager $USER_FIELD_MANAGER */

if (!$USER->CanDoOperation("controller_group_view") || !CModule::IncludeModule("controller"))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

IncludeModuleLangFile(__FILE__);

$entity_id = "CONTROLLER_GROUP";
$sTableID = "t_controll_group";
$oSort = new CAdminUiSorting($sTableID, "timestamp_x", "desc");
/** @global string $by */
/** @global string $order */
$lAdmin = new CAdminUiList($sTableID, $oSort);

$filterFields = array(
	array(
		"id" => "ID",
		"name" => GetMessage("CTRLR_GR_AD_COL_ID"),
		"filterable" => "=",
		"default" => true,
	),
	array(
		"id" => "TIMESTAMP_X",
		"name" => GetMessage("CTRLR_GR_AD_FLT_MODIF"),
		"type" => "date",
		"default" => true,
	),
	array(
		"id" => "DATE_CREATE",
		"name" => GetMessage("CTRLR_GR_AD_FLT_CREAT"),
		"type" => "date",
		"default" => true,
	),
);

$USER_FIELD_MANAGER->AdminListAddFilterFieldsV2($entity_id, $filterFields);
$arFilter = array();
$lAdmin->AddFilter($filterFields, $arFilter);
$USER_FIELD_MANAGER->AdminListAddFilterV2($entity_id, $arFilter, $sTableID, $filterFields);

$filterOption = new Bitrix\Main\UI\Filter\Options($sTableID);
$filterData = $filterOption->getFilter($filterFields);
if (!empty($filterData["FIND"]))
{
	$arFilter["%NAME"] = $filterData["FIND"];
}

if ($USER->CanDoOperation("controller_group_manage") && $lAdmin->EditAction())
{
	foreach ($_POST['FIELDS'] as $ID => $arFields)
	{
		$ID = intval($ID);

		if (!$lAdmin->IsUpdated($ID))
			continue;

		$DB->StartTransaction();
		$USER_FIELD_MANAGER->AdminListPrepareFields($entity_id, $arFields);

		if (!CControllerGroup::Update($ID, $arFields))
		{
			$e = $APPLICATION->GetException();
			$lAdmin->AddUpdateError(GetMessage("CTRLR_GR_AD_ERR1")." #".$ID.": ".$e->GetString(), $ID);
			$DB->Rollback();
		}
		$DB->Commit();
	}
}

$arID = $lAdmin->GroupAction();
if ($arID && $USER->CanDoOperation("controller_group_manage"))
{
	if ($_REQUEST['action_target'] == 'selected')
	{
		$rsData = CControllerGroup::GetList(Array($by => $order), $arFilter);
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
			if (!CControllerGroup::Delete($ID))
			{
				$e = $APPLICATION->GetException();
				$DB->Rollback();
				$lAdmin->AddGroupError(GetMessage("CTRLR_GR_AD_ERR2").":".$e->GetString(), $ID);
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

$arHeaders = array();
$arHeaders[] = array(
	"id" => "NAME",
	"content" => GetMessage("CTRLR_GR_AD_COL_NAME"),
	"default" => true,
	"sort" => "name",
);
$arHeaders[] = array(
	"id" => "TIMESTAMP_X",
	"content" => GetMessage("CTRLR_GR_AD_COL_MOD"),
	"default" => true,
	"sort" => "timestamp_x",
);
$arHeaders[] = array(
	"id" => "MODIFIED_BY",
	"content" => GetMessage("CTRLR_GR_AD_COL_MODBY"),
	"default" => true,
	"sort" => "modified_by",
);
$arHeaders[] = array(
	"id" => "DATE_CREATE",
	"content" => GetMessage("CTRLR_GR_AD_COL_CRE"),
	"sort" => "DATE_CREATE",
);
$arHeaders[] = array(
	"id" => "CREATED_BY",
	"content" => GetMessage("CTRLR_GR_AD_COL_CREBY"),
	"sort" => "CREATED_BY",
);
$arHeaders[] = array(
	"id" => "DESCRIPTION",
	"content" => GetMessage("CTRLR_GR_AD_COL_DESC"),
);
$arHeaders[] = array(
	"id" => "COUNTER_UPDATE_PERIOD",
	"content" => GetMessage("CTRLE_GR_AD_COUNTER_UPD_PER"),
	"sort" => "COUNTER_UPDATE_PERIOD",
);
$arHeaders[] = array(
	"id" => "CHECK_COUNTER_FREE_SPACE",
	"content" => GetMessage("CTRLE_GR_AD_COUNTER_FREE"),
	"sort" => "CHECK_COUNTER_FREE_SPACE",
);
$arHeaders[] = array(
	"id" => "CHECK_COUNTER_SITES",
	"content" => GetMessage("CTRLE_GR_AD_COUNTER_SITES"),
	"sort" => "CHECK_COUNTER_SITES",
);
$arHeaders[] = array(
	"id" => "CHECK_COUNTER_USERS",
	"content" => GetMessage("CTRLE_GR_AD_COUNTER_USERS"),
	"sort" => "CHECK_COUNTER_USERS",
);
$arHeaders[] = array(
	"id" => "CHECK_COUNTER_LAST_AUTH",
	"content" => GetMessage("CTRLE_GR_AD_COUNTER_LAST_AU"),
	"sort" => "CHECK_COUNTER_LAST_AUTH",
);
$arHeaders[] = array(
	"id" => "ID",
	"content" => "ID",
	"default" => true,
	"sort" => "id",
);
$USER_FIELD_MANAGER->AdminListAddHeaders($entity_id, $arHeaders);

$lAdmin->AddHeaders($arHeaders);

$rsData = CControllerGroup::GetList(Array($by => $order), $arFilter, $lAdmin->GetVisibleHeaderColumns());
$rsData = new CAdminUiResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->SetNavigationParams($rsData);

while ($arRes = $rsData->Fetch())
{
	$row =& $lAdmin->AddRow($arRes["ID"], $arRes, 'controller_group_edit.php?lang='.LANGUAGE_ID.'&ID='.intval($arRes['ID']));

	$USER_FIELD_MANAGER->AddUserFields($entity_id, $arRes, $row);

	$htmlName = '('.$arRes['MODIFIED_BY_LOGIN'].') '.$arRes['MODIFIED_BY_NAME'].' '.$arRes['MODIFIED_BY_LAST_NAME'];
	adminListAddUserLink($row, "MODIFIED_BY", $arRes['MODIFIED_BY'], $htmlName);

	$htmlName = '('.$arRes['CREATED_BY_LOGIN'].') '.$arRes['CREATED_BY_NAME'].' '.$arRes['CREATED_BY_LAST_NAME'];
	adminListAddUserLink($row, "CREATED_BY", $arRes['CREATED_BY'], $htmlName);

	$row->AddInputField("NAME", array("size" => "35"));

	$htmlLink = 'controller_group_edit.php?ID='.urlencode($arRes['ID']).'&lang='.LANGUAGE_ID;
	$row->AddViewField("NAME", '<a href="'.htmlspecialcharsbx($htmlLink).'">'.htmlspecialcharsEx($arRes['NAME']).'</a>');

	$row->AddInputField("COUNTER_UPDATE_PERIOD", array("size" => "5"));

	$row->AddCheckField("CHECK_COUNTER_FREE_SPACE");
	$row->AddCheckField("CHECK_COUNTER_SITES");
	$row->AddCheckField("CHECK_COUNTER_USERS");
	$row->AddCheckField("CHECK_COUNTER_LAST_AUTH");

	if ($USER->CanDoOperation("controller_group_manage"))
	{
		$arActions = array(
			array(
				"ICON" => "edit",
				"DEFAULT" => "Y",
				"TEXT" => GetMessage("CTRLR_GR_AD_MENU_EDIT"),
				"ACTION" => $lAdmin->ActionRedirect("controller_group_edit.php?ID=".urlencode($arRes['ID'])."&lang=".LANGUAGE_ID)
			),
			array(
				"ICON" => "copy",
				"TEXT" => GetMessage("CTRLR_GR_AD_MENU_COPY"),
				"ACTION" => $lAdmin->ActionRedirect("controller_group_edit.php?copy_id=".urlencode($arRes['ID'])."&lang=".LANGUAGE_ID)
			),
			array("SEPARATOR" => true),
			array(
				"ICON" => "delete",
				"TEXT" => GetMessage("CTRLR_GR_AD_MENU_DEL"),
				"ACTION" => "if(confirm('".CUtil::JSEscape(GetMessage("CTRLR_GR_AD_MENU_DEL_CONFIRM"))."')) ".$lAdmin->ActionDoGroup($arRes['ID'], "delete"),
			),
		);
		$row->AddActions($arActions);
	}
}

$lAdmin->AddFooter(
	array(
		array("title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value" => $rsData->SelectedRowsCount()),
		array("counter" => true, "title" => GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value" => "0"),
	)
);

if ($USER->CanDoOperation("controller_group_manage"))
{
	$lAdmin->AddGroupActionTable(array(
			"edit" => true,
			"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
		)
	);
	$aContext = array(
		array(
			"ICON" => "btn_new",
			"TEXT" => GetMessage("MAIN_ADD"),
			"LINK" => "controller_group_edit.php?lang=".LANGUAGE_ID,
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

$APPLICATION->SetTitle(GetMessage("CTRLR_GR_AD_TITLE"));

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
