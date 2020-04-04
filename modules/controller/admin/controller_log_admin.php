<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */

if (!$USER->CanDoOperation("controller_log_view") || !CModule::IncludeModule("controller"))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

IncludeModuleLangFile(__FILE__);

$sTableID = "t_controll_log";
$oSort = new CAdminSorting($sTableID, "id", "desc");
$lAdmin = new CAdminList($sTableID, $oSort);
/** @global string $by */
/** @global string $order */
$arLogNames = CControllerLog::GetNameArray();
$arTaskNames = CControllerTask::GetTaskArray();

$arFilterRows = array(
	GetMessage("CTRL_LOG_ADMIN_FILTER_STATUS"),
	GetMessage("CTRL_LOG_ADMIN_FILTER_DESC"),
	GetMessage("CTRL_LOG_ADMIN_FILTER_ID"),
	GetMessage("CTRL_LOG_ADMIN_FILTER_CLIENT"),
	GetMessage("CTRL_LOG_ADMIN_FILTER_IDCLIENT"),
	GetMessage("CTRL_LOG_ADMIN_FILTER_TASK"),
	GetMessage("CTRL_LOG_ADMIN_FILTER_TASKID"),
	GetMessage("CTRL_LOG_ADMIN_FILTER_CREATED"),
);

$filter = new CAdminFilter(
	$sTableID."_filter_id",
	$arFilterRows
);

$arFilterFields = array(
	"find_name",
	"find_name2",
	"find_description",
	"find_id",
	"find_status",
	"find_task_id",
	"find_task_name",
	"find_controller_member_id",
	"find_controller_member_name",
	"find_timestamp_x_from",
	"find_timestamp_x_to",
);

$adminFilter = $lAdmin->InitFilter($arFilterFields);

$arFilter = array(
	"ID" => $find_id,
	"CONTROLLER_MEMBER_ID" => $adminFilter['find_controller_member_id'],
	"STATUS" => $adminFilter['find_status'],
	"TASK_ID" => $adminFilter['find_task_id'],
	"%NAME" => (strlen($adminFilter['find_name2']) > 0? $adminFilter['find_name2']: $adminFilter['find_name']),
	"%DESCRIPTION" => $adminFilter['find_description'],
	"%TASK_NAME" => $adminFilter['find_task_name'],
	"%CONTROLLER_MEMBER_NAME" => $adminFilter['find_controller_member_name'],
	">=TIMESTAMP_X" => $adminFilter['find_timestamp_x_from'],
	"<=TIMESTAMP_X" => $adminFilter['find_timestamp_x_to'],
);

if ($USER->CanDoOperation("controller_log_delete") && $arID = $lAdmin->GroupAction())
{
	if ($_REQUEST['action_target'] == 'selected')
	{
		$rsData = CControllerLog::GetList(Array($by => $order), $arFilter);
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
			if (!CControllerLog::Delete($ID))
			{
				$DB->Rollback();
				$lAdmin->AddGroupError(GetMessage("CTRL_LOG_ADMIN_ERR_DELETE"), $ID);
			}
			$DB->Commit();
			break;
		}
	}
}

$rsData = CControllerLog::GetList(
	array(
		$by => $order,
	),
	$arFilter,
	array(
		"nPageSize" => CAdminResult::GetNavSize($sTableID),
	)
);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("CTRL_LOG_ADMIN_PAGETITLE")));

$arHeaders = Array();
$arHeaders[] = Array("id" => "TIMESTAMP_X", "content" => GetMessage("CTRL_LOG_ADMIN_COLUMN_CREATED"), "default" => true, "sort" => "timestamp_x");
$arHeaders[] = Array("id" => "NAME", "content" => GetMessage("CTRL_LOG_ADMIN_COLUMN_NAME"), "default" => true, "sort" => "name");
$arHeaders[] = Array("id" => "CONTROLLER_MEMBER_NAME", "content" => GetMessage("CTRL_LOG_ADMIN_FILTER_CLIENT"), "default" => true, "sort" => "controller_member_name");
$arHeaders[] = Array("id" => "STATUS", "content" => GetMessage("CTRL_LOG_ADMIN_FILTER_STATUS"), "default" => true, "sort" => "status");
$arHeaders[] = Array("id" => "TASK_NAME", "content" => GetMessage("CTRL_LOG_ADMIN_FILTER_TASK"), "default" => true, "sort" => "task_name");
$arHeaders[] = Array("id" => "USER", "content" => GetMessage("CTRL_LOG_ADMIN_COLUMN_USER"), "default" => true);
$arHeaders[] = Array("id" => "DESCRIPTION", "content" => GetMessage("CTRL_LOG_ADMIN_FILTER_DESC"));
$arHeaders[] = Array("id" => "ID", "content" => "ID", "default" => true, "sort" => "id");

$lAdmin->AddHeaders($arHeaders);

while ($arRes = $rsData->Fetch())
{
	$row =& $lAdmin->AddRow($arRes['ID'], $arRes);

	$htmlLink = 'controller_member_edit.php?lang='.LANGUAGE_ID.'&ID='.urlencode($arRes['CONTROLLER_MEMBER_ID']);
	$htmlName = $arRes['CONTROLLER_MEMBER_NAME'].' ['.$arRes['CONTROLLER_MEMBER_ID'].']';
	$row->AddViewField("CONTROLLER_MEMBER_NAME", '<a href="'.htmlspecialcharsbx($htmlLink).'">'.htmlspecialcharsEx($htmlName).'</a>');

	if ($arRes['TASK_ID'] > 0)
	{
		$row->AddViewField("TASK_NAME", htmlspecialcharsEx($arTaskNames[$arRes['TASK_NAME']].' ['.$arRes['TASK_ID'].']'));
	}

	$row->AddViewField("NAME", (isset($arLogNames[$arRes['NAME']])? htmlspecialcharsEx($arLogNames[$arRes['NAME']]): $arRes['NAME']));

	if ($arRes['USER_ID'] > 0)
	{
		$htmlName = '('.$arRes['USER_LOGIN'].') '.$arRes['USER_NAME'].' '.$arRes['USER_LAST_NAME'];
		adminListAddUserLink($row, "USER", $arRes['USER_ID'], $htmlName);

	}

	$row->AddViewField("STATUS", ($arRes['STATUS'] == 'Y'? GetMessage("CTRL_LOG_ADMIN_COLUMN_STATUS_OK"): GetMessage("CTRL_LOG_ADMIN_COLUMN_STATUS_ERR")));

	$arActions = array();

	$arActions[] = array(
		"ICON" => "list",
		"TEXT" => GetMessage("CTRL_LOG_ADMIN_MENU_DETAIL"),
		"ACTION" => "jsUtils.OpenWindow('".CUtil::JSEscape("controller_log_detail.php?lang=".LANGUAGE_ID."&ID=".urlencode($arRes['ID'])."")."', '700', '550');",
		"DEFAULT" => "Y",
	);

	if ($USER->CanDoOperation("controller_log_delete"))
	{
		$arActions[] = array(
			"ICON" => "delete",
			"TEXT" => GetMessage("CTRL_LOG_ADMIN_MENU_DEL"),
			"ACTION" => "if(confirm('".GetMessage("CTRL_LOG_ADMIN_MENU_DEL_CONFIRM")."')) ".$lAdmin->ActionDoGroup($arRes['ID'], "delete"),
		);
	}

	$row->AddActions($arActions);
}

$lAdmin->AddFooter(
	array(
		array("title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value" => $rsData->SelectedRowsCount()),
		array("counter" => true, "title" => GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value" => "0"),
	)
);

if ($USER->CanDoOperation("controller_log_delete"))
{
	$lAdmin->AddGroupActionTable(array(
			"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
		)
	);
}

$lAdmin->AddAdminContextMenu();

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRL_LOG_ADMIN_TITLE"));
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<form name="form1" method="GET" action="<? echo $APPLICATION->GetCurPage() ?>?">
	<? $filter->Begin(); ?>
	<tr>
		<td nowrap><label for="find_name"><?=GetMessage("CTRL_LOG_ADMIN_COLUMN_NAME")?></label>:</td>
		<td nowrap>
			<select name="find_name" id="find_name">
				<option value=""></option>
				<? foreach ($arLogNames as $name_id => $name_value): ?>
					<option value="<?=$name_id?>"><?=htmlspecialcharsEx($name_value)?></option>
				<? endforeach; ?>
			</select>
			<input type="text" name="find_name2" title="" value="<? echo htmlspecialcharsbx($adminFilter['find_name2']) ?>" size="15">
		</td>
	</tr>

	<tr>
		<td nowrap><label for="find_status"><?=GetMessage("CTRL_LOG_ADMIN_FILTER_STATUS")?></label>:</td>
		<td nowrap>
			<select name="find_status" id="find_status">
				<option value=""><? echo GetMessage("CTRL_LOG_ADMIN_FILTER_ANY") ?></option>
				<option value="Y"<? if ($find_status == "Y") echo ' selected' ?>><? echo GetMessage("CTRL_LOG_ADMIN_COLUMN_STATUS_OK") ?></option>
				<option value="N"<? if ($find_status == "N") echo ' selected' ?>><? echo GetMessage("CTRL_LOG_ADMIN_COLUMN_STATUS_ERR") ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td nowrap><label for="find_description"><?=GetMessage("CTRL_LOG_ADMIN_FILTER_DESC")?></label>:</td>
		<td nowrap>
			<input
				type="text"
				name="find_description"
				id="find_description"
				value="<? echo htmlspecialcharsbx($adminFilter['find_description']) ?>"
				size="47"
			>
		</td>
	</tr>

	<tr>
		<td nowrap><label for="find_id"><?=GetMessage("CTRL_LOG_ADMIN_FILTER_ID")?></label>:</td>
		<td nowrap>
			<input
				type="text"
				name="find_id"
				id="find_id"
				value="<? echo htmlspecialcharsbx($adminFilter['find_id']) ?>"
				size="47"
			>
		</td>
	</tr>

	<tr>
		<td nowrap><label for="find_controller_member_name"><?=GetMessage("CTRL_LOG_ADMIN_FILTER_CLIENT")?></label>:</td>
		<td nowrap>
			<input
				type="text"
				name="find_controller_member_name"
				id="find_controller_member_name"
				value="<? echo htmlspecialcharsbx($adminFilter['find_controller_member_name']) ?>"
				size="47"
			>
		</td>
	</tr>

	<tr>
		<td nowrap><label for="find_controller_member_id"><?=GetMessage("CTRL_LOG_ADMIN_FILTER_IDCLIENT")?></label>:</td>
		<td nowrap>
			<input
				type="text"
				name="find_controller_member_id"
				id="find_controller_member_id"
				value="<? echo htmlspecialcharsbx($adminFilter['find_controller_member_id']) ?>"
				size="47"
			>
		</td>
	</tr>

	<tr>
		<td nowrap><label for="find_task_name"><?=GetMessage("CTRL_LOG_ADMIN_FILTER_TASK")?></label>:</td>
		<td nowrap>
			<input
				type="text"
				name="find_task_name"
				id="find_task_name"
				value="<? echo htmlspecialcharsbx($adminFilter['find_task_name']) ?>"
				size="47"
			>
		</td>
	</tr>

	<tr>
		<td nowrap><label for="find_task_id"><?=GetMessage("CTRL_LOG_ADMIN_FILTER_TASKID")?></label>:</td>
		<td nowrap>
			<input
				type="text"
				name="find_task_id"
				id="find_task_id"
				value="<? echo htmlspecialcharsbx($adminFilter['find_task_id']) ?>"
				size="47"
			>
		</td>
	</tr>

	<tr>
		<td nowrap><?=GetMessage("CTRL_LOG_ADMIN_FILTER_CREATED")?>:</td>
		<td nowrap><? echo CalendarPeriod("find_timestamp_x_from", $adminFilter['find_timestamp_x_from'], "find_timestamp_x_to", $adminFilter['find_timestamp_x_to'], "form1", "Y") ?></td>
	</tr>

	<? $filter->Buttons(array("table_id" => $sTableID, "url" => $APPLICATION->GetCurPage(), "form" => "form1"));
	$filter->End(); ?>

</form>

<? $lAdmin->DisplayList(); ?>


<? require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php"); ?>
