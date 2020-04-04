<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */

if (!$USER->CanDoOperation("controller_task_view") || !CModule::IncludeModule("controller"))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

IncludeModuleLangFile(__FILE__);

$arTask = CControllerTask::GetTaskArray();

$arStatus = CControllerTask::GetStatusArray();

$dbrTaskN = CControllerTask::GetList(Array(), Array("=STATUS" => Array('N', 'P')), true);
$arTaskN = $dbrTaskN->Fetch();
$iTaskNCnt = intval($arTaskN['C']);

if (
	$_SERVER["REQUEST_METHOD"] == "POST"
	&& $_REQUEST['act'] == 'process'
	&& check_bitrix_sessid()
	&& $USER->CanDoOperation("controller_task_run")
)
{
	$strError = "";
	$iCntExecuted = intval($_REQUEST["executed"]);
	$iCntTotal = intval($_REQUEST["cnt"]);
	$endTime = microtime(true) + COption::GetOptionString('controller', 'tasks_run_step_time');

	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
	if ($iTaskNCnt > 0)
	{
		$dbrTask = CControllerTask::GetList(Array("ID" => "ASC"), Array("=STATUS" => Array('N', 'P')));
		$tTasksTime = microtime(true);
		while ($arTask = $dbrTask->Fetch())
		{
			$status = CControllerTask::ProcessTask($arTask["ID"]);

			if ($status === "0" && $e = $APPLICATION->GetException())
			{
				$strError = GetMessage("CTRLR_TASK_ERR_LOCK")."<br>".$e->GetString();
				if (strpos($strError, "PLS-00201") !== false && strpos($strError, "'DBMS_LOCK'") !== false)
					$strError .= "<br>".GetMessage("CTRLR_TASK_ERR_LOCK_ADVICE");
				$APPLICATION->ResetException();
				break;
			}

			$iCntExecuted++;

			while ($status === "P")
			{
				$status = CControllerTask::ProcessTask($arTask["ID"]);
				if (microtime(true) > $endTime)
					break;
			}

			if (microtime(true) > $endTime)
				break;
		}

		if (strlen($strError))
		{
			$message = new CAdminMessage($strError);
			echo $message->Show();
		}
		else
		{
			$message = new CAdminMessage(array(
				"TYPE" => "PROGRESS",
				"MESSAGE" => GetMessage("CTRLR_TASK_PROGRESS"),
				"DETAILS" => GetMessage("CTRLR_TASK_PROGRESS_BAR")." $iCntExecuted ".GetMessage("CTRLR_TASK_PROGRESS_BAR_FROM")." $iCntTotal #PROGRESS_BAR#",
				"HTML" => true,
				"PROGRESS_TOTAL" => $iCntTotal,
				"PROGRESS_VALUE" => $iCntExecuted,
			));
			echo $message->Show();
			?>
			<script>
				Start(<?echo $iCntTotal?>, <?echo $iCntExecuted?>);
			</script>
			<?
		}
	}
	else
	{
		$message = new CAdminMessage(array(
			"TYPE" => "PROGRESS",
			"MESSAGE" => GetMessage("CTRLR_TASK_PROGRESS"),
			"DETAILS" => GetMessage("CTRLR_TASK_PROGRESS_BAR")." $iCntExecuted ".GetMessage("CTRLR_TASK_PROGRESS_BAR_FROM")." $iCntTotal #PROGRESS_BAR#",
			"HTML" => true,
			"PROGRESS_TOTAL" => $iCntTotal,
			"PROGRESS_VALUE" => $iCntExecuted,
		));
		$message->Show();
		?>
		<script>
			CloseWaitWindow();
		</script>
		<?
	}
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_js.php");
}

$sTableID = "t_controll_task";
$oSort = new CAdminSorting($sTableID, "id", "desc");
/** @global string $by */
/** @global string $order */
$lAdmin = new CAdminList($sTableID, $oSort);

$arFilterRows = array(
	GetMessage("CTRLR_TASK_FLT_ID"),
	GetMessage("CTRLR_TASK_FLT_CLIENT"),
	GetMessage("CTRLR_TASK_FLT_OPERATION"),
	GetMessage("CTRLR_TASK_FLT_EXECUTED"),
	GetMessage("CTRLR_TASK_FLT_MODYFIED"),
	GetMessage("CTRLR_TASK_FLT_CREATED"),
);

$filter = new CAdminFilter(
	$sTableID."_filter_id",
	$arFilterRows
);
$arFilterFields = Array(
	"find_status",
	"find_task_id",
	"find_id",
	"find_controller_member_id",
	"find_executed_from",
	"find_executed_to",
	"find_timestamp_x_from",
	"find_timestamp_x_to",
	"find_created_from",
	"find_created_to",
);

$adminFilter = $lAdmin->InitFilter($arFilterFields);

if (!isset($find_status) || !is_array($find_status))
	$find_status = array('N', 'P');

$arFilter = array(
	"%TASK_ID" => $_REQUEST["find_task_id"],
	"ID" => $_REQUEST["find_id"],
	"=STATUS" => $_REQUEST["find_status"],
	"CONTROLLER_MEMBER_ID" => $_REQUEST["find_controller_member_id"],
	">=TIMESTAMP_X" => $_REQUEST["find_timestamp_x_from"],
	"<=TIMESTAMP_X" => $_REQUEST["find_timestamp_x_to"],
	">=DATE_CREATE" => $_REQUEST["find_created_from"],
	"<=DATE_CREATE" => $_REQUEST["find_created_to"],
	">=DATE_EXECUTE" => $_REQUEST["find_executed_from"],
	"<=DATE_EXECUTE" => $_REQUEST["find_executed_to"],
);

$arID = $lAdmin->GroupAction();
if (
	!empty($arID)
	&& (
		($_REQUEST['action'] === 'repeat' && $USER->CanDoOperation("controller_task_run"))
		|| ($_REQUEST['action'] === 'delete' && $USER->CanDoOperation("controller_task_delete"))
	)
)
{
	if ($_REQUEST['action_target'] == 'selected')
	{
		$rsData = CControllerTask::GetList(array($by => $order), $arFilter);
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
			$DB->StartTransaction();
			if (!CControllerTask::Delete($ID))
			{
				$DB->Rollback();
				$lAdmin->AddGroupError(GetMessage("CTRLR_TASK_ERR_DELETE"), $ID);
			}
			$DB->Commit();
			break;

		case "repeat":
			if (!CControllerTask::Update($ID, Array("STATUS" => "N", "DATE_EXECUTE" => false)))
				if ($e = $APPLICATION->GetException())
					$lAdmin->AddGroupError(GetMessage("CTRLR_TASK_REP_DELETE")." ".$ID.": ".$e->GetString(), $ID);
			break;
		}
	}
}

$rsData = CControllerTask::GetList(
	array(
		$by => $order,
	),
	$arFilter,
	false,
	array(
		"nPageSize" => CAdminResult::GetNavSize($sTableID),
	)
);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("CTRLR_TASK_NAV")));

$arHeaders = array(
	array("id" => "CONTROLLER_MEMBER_NAME", "content" => GetMessage("CTRLR_TASK_FLT_CLIENT"), "default" => true, "sort" => "CONTROLLER_MEMBER_NAME"),
	array("id" => "TASK_ID", "content" => GetMessage("CTRLR_TASK_COLUMN_TASK"), "default" => true, "sort" => "TASK_ID"),
	array("id" => "STATUS", "content" => GetMessage("CTRLR_TASK_COLUMN_STATUS"), "default" => true, "sort" => "STATUS"),
	array("id" => "DATE_EXECUTE", "content" => GetMessage("CTRLR_TASK_COLUMN_EXEC"), "default" => true, "sort" => "DATE_EXECUTE"),
	array("id" => "INIT_EXECUTE", "content" => GetMessage("CTRLR_TASK_COLUMN_ARGS")),
	array("id" => "RESULT_EXECUTE", "content" => GetMessage("CTRLR_TASK_COLUMN_RESULT"), "default" => true),
	array("id" => "CONTROLLER_MEMBER_URL", "content" => GetMessage("CTRLR_TASK_COLUMN_URL"), "sort" => "CONTROLLER_MEMBER_URL"),
	array("id" => "TIMESTAMP_X", "content" => GetMessage("CTRLR_TASK_COLUMN_DATE_MOD"), "sort" => "timestamp_x"),
	array("id" => "DATE_CREATE", "content" => GetMessage("CTRLR_TASK_COLUMN_DATE_CRE"), "default" => true, "sort" => "DATE_CREATE"),
	array("id" => "ID", "content" => "ID", "default" => true, "sort" => "id"),
);

$lAdmin->AddHeaders($arHeaders);

while ($arRes = $rsData->Fetch())
{
	$row =& $lAdmin->AddRow($arRes["ID"], $arRes);

	if ($arRes["STATUS"] == 'N')
	{
		$row->AddViewField("RESULT_EXECUTE", '');
		$row->AddViewField("DATE_EXECUTE", '');
	}

	$row->AddViewField("STATUS", (isset($arStatus[$arRes["STATUS"]])? $arStatus[$arRes["STATUS"]]: htmlspecialcharsEx($arRes["STATUS"])));
	$row->AddViewField("TASK_ID", (isset($arTask[$arRes["TASK_ID"]])? $arTask[$arRes["TASK_ID"]]: htmlspecialcharsEx($arRes["TASK_ID"])));
	$row->AddViewField("CONTROLLER_MEMBER_NAME", '<a href="controller_member_edit.php?lang='.LANGUAGE_ID.'&ID='.urlencode($arRes["CONTROLLER_MEMBER_ID"]).'">'.htmlspecialcharsEx($arRes["CONTROLLER_MEMBER_NAME"]).'</a>');
	$row->AddViewField("CONTROLLER_MEMBER_URL", '<a href="'.htmlspecialcharsbx($arRes["CONTROLLER_MEMBER_URL"]).'">'.htmlspecialcharsEx($arRes["CONTROLLER_MEMBER_URL"]).'</a>');

	$arActions = array();
	if ($USER->CanDoOperation("controller_task_run"))
	{
		$arActions[] = array(
			"ICON" => "other",
			"TEXT" => GetMessage("CTRLR_TASK_MENU_REPEAT"),
			"ACTION" => "if(confirm('".GetMessage("CTRLR_TASK_MENU_REPEAT_CONFIRM")."')) ".$lAdmin->ActionDoGroup($arRes["ID"], "repeat"),
		);
	}
	if ($USER->CanDoOperation("controller_task_delete"))
	{
		$arActions[] = array(
			"ICON" => "delete",
			"TEXT" => GetMessage("CTRLR_TASK_MENU_CANCEL"),
			"ACTION" => "if(confirm('".GetMessage("CTRLR_TASK_MENU_CANCEL_CONFIRM")."')) ".$lAdmin->ActionDoGroup($arRes["ID"], "delete"),
		);
	}
	if ($USER->CanDoOperation("controller_log_view"))
	{
		$arActions[] = array(
			"ICON" => "other",
			"TEXT" => GetMessage("CTRLR_TASK_MENU_LOG"),
			"ACTION" => $lAdmin->ActionRedirect("/bitrix/admin/controller_log_admin.php?lang=".urlencode(LANGUAGE_ID)."&set_filter=Y&find_task_id=".urlencode($arRes["ID"])),
		);
	}

	if ($arActions)
	{
		$row->AddActions($arActions);
	}
}

$lAdmin->AddFooter(
	array(
		array("title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value" => $rsData->SelectedRowsCount()),
		array("counter" => true, "title" => GetMessage("MAIN_ADMIN_LIST_CHECKED"), "value" => "0"),
	)
);

if ($USER->CanDoOperation("controller_task_delete"))
{
	$lAdmin->AddGroupActionTable(Array(
			"delete" => GetMessage("MAIN_ADMIN_LIST_DELETE"),
			"repeat" => GetMessage("CTRLR_TASK_REPEAT"),
		)
	);
}

$lAdmin->AddAdminContextMenu(array());

$lAdmin->BeginPrologContent();
?>
<div id="progress">
	<?
	if ($iTaskNCnt > 0 && $USER->CanDoOperation("controller_task_run"))
	{
		$message = new CAdminMessage(array(
			"TYPE" => "PROGRESS",
			"MESSAGE" => GetMessage("CTRLR_TASK_PROGRESS"),
			"DETAILS" => GetMessage("CTRLR_TASK_PROGRESS_BAR")." 0 ".GetMessage("CTRLR_TASK_PROGRESS_BAR_FROM")." $iTaskNCnt #PROGRESS_BAR#",
			"HTML" => true,
			"PROGRESS_TOTAL" => $iTaskNCnt,
			"PROGRESS_VALUE" => 0,
			"BUTTONS" => array(
				array(
					"ID" => "btn_start",
					"VALUE" => GetMessage("CTRLR_TASK_BUTTON_START"),
					"ONCLICK" => "Start($iTaskNCnt, 0);",
				),
			),
		));
		echo $message->Show();
	}
	?>
</div>
<script>
	function Start(cnt, executed)
	{
		ShowWaitWindow();
		BX.ajax.post(
			'controller_task.php?lang=<?echo LANGUAGE_ID?>&<?echo bitrix_sessid_get()?>&act=process&cnt=' + cnt + '&executed=' + executed,
			null,
			function (result)
			{
				BX('progress').innerHTML = result;
			}
		);
	}
</script>
<?
$lAdmin->EndPrologContent();


$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRLR_TASK_TITLE"));
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<form name="form1" method="GET" action="<? echo $APPLICATION->GetCurPage() ?>?">
	<? $filter->Begin(); ?>

	<tr>
		<td><?=GetMessage("CTRLR_TASK_FLR_ST")?>:</td>
		<td>
			<select name="find_status[]" multiple="multiple">
				<? foreach ($arStatus as $status_id => $status_name): ?>
					<option value="<?=htmlspecialcharsbx($status_id)?>"<? if (in_array($status_id, $find_status)) echo ' selected' ?>><?=htmlspecialcharsEx($status_name)?></option>
				<? endforeach ?>
			</select>
		</td>
	</tr>
	<tr>
		<td>ID:</td>
		<td>
			<input type="text" name="find_id" value="<? echo htmlspecialcharsbx($_REQUEST["find_id"]) ?>" size="47">
		</td>
	</tr>
	<tr>
		<td><?=GetMessage("CTRLR_TASK_FLT_CLIENT")?>:</td>
		<td>
			<input type="text" name="find_controller_member_id" value="<? echo htmlspecialcharsbx($_REQUEST["find_controller_member_id"]) ?>" size="47">
		</td>
	</tr>
	<tr>
		<td><?=GetMessage("CTRLR_TASK_FLT_OPERATION")?>:</td>
		<td>
			<select name="find_task_id">
				<option value=""><? echo GetMessage("CTRLR_TASK_FLR_ANY") ?></option>
				<? foreach ($arTask as $task_id => $task_name): ?>
					<option value="<?=htmlspecialcharsbx($task_id)?>" <? if ($_REQUEST["find_task_id"] == $task_id) echo ' selected="selected"'; ?>><?=htmlspecialcharsEx($task_name)?></option>
				<? endforeach ?>
			</select>
	</tr>
	<tr>
		<td><?=GetMessage("CTRLR_TASK_FLT_EXECUTED")?>:</td>
		<td><? echo CalendarPeriod("find_executed_from", $adminFilter["find_executed_from"], "find_executed_to", $adminFilter["find_executed_to"], "form1", "Y") ?></td>
	</tr>
	<tr>
		<td><?=GetMessage("CTRLR_TASK_FLT_MODYFIED")?>:</td>
		<td><? echo CalendarPeriod("find_timestamp_x_from", $adminFilter["find_timestamp_x_from"], "find_timestamp_x_to", $adminFilter["find_timestamp_x_to"], "form1", "Y") ?></td>
	</tr>
	<tr>
		<td><?=GetMessage("CTRLR_TASK_FLT_CREATED")?>:</td>
		<td><? echo CalendarPeriod("find_created_from", $adminFilter["find_created_from"], "find_created_to", $adminFilter["find_created_to"], "form1", "Y") ?></td>
	</tr>

	<? $filter->Buttons(array("table_id" => $sTableID, "url" => $APPLICATION->GetCurPage(), "form" => "form1"));
	$filter->End(); ?>

</form>

<?
$lAdmin->DisplayList();

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php"); ?>
