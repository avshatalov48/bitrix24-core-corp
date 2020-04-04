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
$oSort = new CAdminSorting($sTableID, "id", "desc");
/** @global string $by */
/** @global string $order */
$lAdmin = new CAdminList($sTableID, $oSort);

$arFilterRows = array();

$arGroups = array();
$dbr_groups = CControllerGroup::GetList(array("SORT" => "ASC", "NAME" => "ASC", "ID" => "ASC"));
while ($ar_groups = $dbr_groups->Fetch())
	$arGroups[$ar_groups["ID"]] = $ar_groups["NAME"];

$filter = new CAdminFilter(
	$sTableID."_filter_id",
	$arFilterRows
);

$arFilterFields = array(
	"find_controller_group_id",
);

$adminFilter = $lAdmin->InitFilter($arFilterFields);

if ($adminFilter["find_controller_group_id"])
	$arFilter = array("=CONTROLLER_GROUP_ID" => $adminFilter["find_controller_group_id"]);
else
	$arFilter = array();

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


if ($USER->CanDoOperation("controller_counters_manage") && $arID = $lAdmin->GroupAction())
{
	if ($_REQUEST['action_target'] == 'selected')
	{
		$rsData = CControllerCounter::GetList(array($by => $order), $arFilter);
		while ($arRes = $rsData->Fetch())
			$arID[] = $arRes['ID'];
	}

	foreach ($arID as $ID)
	{
		if (strlen($ID) <= 0)
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
}

$rsData = CControllerCounter::GetList(Array($by => $order), $arFilter);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("CTRL_CNT_ADMIN_NAV")));

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

	$row->AddInputField("NAME", array("size" => "35"));
	$htmlLink = 'controller_counter_edit.php?ID='.urlencode($arRes['ID']).'&lang='.LANGUAGE_ID;
	$row->AddViewField("NAME", '<a href="'.htmlspecialcharsbx($htmlLink).'">'.htmlspecialcharsEx($arRes['NAME']).'</a>');

	$row->AddSelectField("COUNTER_TYPE", CControllerCounter::GetTypeArray());
	$row->AddSelectField("COUNTER_FORMAT", CControllerCounter::GetFormatArray());
	$row->AddViewField("COMMAND", "<pre>".htmlspecialcharsEx($arRes["COMMAND"])."</pre>");
	$row->AddEditField("COMMAND", "<textarea cols=\"80\" rows=\"15\" name=\"".htmlspecialcharsEx("FIELDS[".$arRes["ID"]."][COMMAND]")."\">".htmlspecialcharsbx($arRes["COMMAND"])."</textarea>");

	if ($USER->CanDoOperation("controller_counters_manage"))
	{
		$arActions = array(
			array(
				"ICON" => "edit",
				"DEFAULT" => "Y",
				"TEXT" => GetMessage("CTRL_CNT_ADMIN_MENU_EDIT"),
				"ACTION" => $lAdmin->ActionRedirect("controller_counter_edit.php?ID=".urlencode($arRes["ID"])."&lang=".LANGUAGE_ID),
			),
			array("SEPARATOR" => true),
			array(
				"ICON" => "delete",
				"TEXT" => GetMessage("CTRL_CNT_ADMIN_MENU_DELETE"),
				"ACTION" => "if(confirm('".GetMessage("CTRL_CNT_ADMIN_MENU_DELETE_ALERT")."')) ".$lAdmin->ActionDoGroup($arRes["ID"], "delete"),
			),
		);

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
?>
<form name="form1" method="GET" action="<? echo $APPLICATION->GetCurPage() ?>?">
	<? $filter->Begin(); ?>
	<tr>
		<td nowrap><label for="find_controller_group_id"><?=GetMessage("CTRL_CNT_ADMIN_FILTER_GROUP")?></label></td>
		<td>
			<select name="find_controller_group_id" id="find_controller_group_id">
				<option value=""><? echo GetMessage("CTRL_CNT_ADMIN_FILTER_ANY") ?></option>
				<? foreach ($arGroups as $group_id => $group_name): ?>
					<option value="<?=htmlspecialcharsbx($group_id)?>" <? if ($group_id == $adminFilter['find_controller_group_id']) echo "selected" ?>><?=htmlspecialcharsEx($group_name)?></option>
				<? endforeach; ?>
			</select>
		</td>
	</tr>

	<?
	$filter->Buttons(array("table_id" => $sTableID, "url" => $APPLICATION->GetCurPage(), "form" => "form1"));
	$filter->End();
	?>

</form>
<?
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
