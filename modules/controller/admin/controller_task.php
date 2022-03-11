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

$sTableID = "t_controll_task_v3";
$arTask = CControllerTask::GetTaskArray();
$arStatus = CControllerTask::GetStatusArray();

$iCntExecuted = intval($_REQUEST["executed"]);
$iCntTotal = intval($_REQUEST["cnt"]);

if (
	$_SERVER["REQUEST_METHOD"] == "POST"
	&& $_REQUEST['act'] == 'process'
	&& check_bitrix_sessid()
	&& $USER->CanDoOperation("controller_task_run")
)
{
	$strError = "";
	$onlyRetry = false;
	$endTime = microtime(true) + COption::GetOptionString('controller', 'tasks_run_step_time');

	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
	if ($USER->CanDoOperation("controller_task_run"))
	{
		$sleep = 0;
		//1. Finish partial
		//2. Execute new tasks
		//3. Retry failed tasks
		//4. Run low priority tasks
		foreach (array('P', 'N', 'R', 'L') as $status2exec)
		{
			$dbrTask = CControllerTask::GetList(Array("ID" => "ASC"), Array("=STATUS" => $status2exec));
			while ($arTask = $dbrTask->Fetch())
			{
				if ($status2exec === 'R')
				{
					//check timeout
					if ($arTask["EXECUTED_INTERVAL"] < $arTask["RETRY_TIMEOUT"])
					{
						$onlyRetry = true;
						continue;
					}
				}
				$onlyRetry = false;

				$status = CControllerTask::ProcessTask($arTask["ID"]);

				if ($status === "0" && $e = $APPLICATION->GetException())
				{
					$strError = GetMessage("CTRLR_TASK_ERR_LOCK")."<br>".$e->GetString();
					if (mb_strpos($strError, "PLS-00201") !== false && mb_strpos($strError, "'DBMS_LOCK'") !== false)
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

				if ($status === "F" && $arTask["RETRY_COUNT"] > 0)
				{
					CControllerTask::PostponeTask($arTask["ID"], $arTask["RETRY_COUNT"]-1);
					$iCntExecuted--;
				}

				if (microtime(true) > $endTime)
					break;
			}
		}
	}

	if($strError <> '')
	{
		$message = new CAdminMessage($strError);
		echo $message->Show();
	}
	elseif (!CControllerTask::GetList([],["=STATUS" => ['P', 'N', 'R', 'L']],['ID'],['bOnlyCount' => true]) || $onlyRetry)
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
			CloseWaitWindow();
			<?=$sTableID?>.onReloadGrid();
		</script>
		<?
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

	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin_js.php");
}

$oSort = new CAdminUiSorting($sTableID, "id", "desc");
/** @global string $by */
/** @global string $order */
$lAdmin = new CAdminUiList($sTableID, $oSort);

$filterFields = array(
	array(
		"id" => "STATUS",
		"name" => GetMessage("CTRLR_TASK_FLR_ST"),
		"filterable" => "=",
		"default" => true,
		"type" => "list",
		"items" => $arStatus,
		"params" => array("multiple" => "Y"),
	),
	array(
		"id" => "ID",
		"name" => GetMessage("CTRLR_TASK_FLT_ID"),
		"filterable" => "=",
	),
	array(
		"id" => "CONTROLLER_MEMBER_ID",
		"name" => GetMessage("CTRLR_TASK_FLT_CLIENT"),
		"filterable" => "=",
	),
	array(
		"id" => "TASK_ID",
		"name" => GetMessage("CTRLR_TASK_FLT_OPERATION"),
		"type" => "list",
		"items" => $arTask,
		"params" => array("multiple" => "N"),
		"filterable" => "=",
	),
	array(
		"id" => "DATE_EXECUTE",
		"name" => GetMessage("CTRLR_TASK_FLT_EXECUTED"),
		"type" => "date",
	),
	array(
		"id" => "TIMESTAMP_X",
		"name" => GetMessage("CTRLR_TASK_FLT_MODYFIED"),
		"type" => "date",
	),
	array(
		"id" => "DATE_CREATE",
		"name" => GetMessage("CTRLR_TASK_FLT_CREATED"),
		"type" => "date",
	),
);

$lAdmin->setFilterPresets([
	'in_process' => [
		'name' => GetMessage("CTRLR_TASK_PRESET_IN_PROCESS"),
		'default' => true,
		'current' => true,
		'fields' => ['STATUS' => ['P', 'R', 'N', 'L']],
	],
]);

$arFilter = array();
$lAdmin->AddFilter($filterFields, $arFilter);

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
		$rsData = CControllerTask::GetList(array($by => $order), $arFilter, array("ID"));
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
			{
				if ($e = $APPLICATION->GetException())
					$lAdmin->AddGroupError(GetMessage("CTRLR_TASK_REP_DELETE")." ".$ID.": ".$e->GetString(), $ID);
			}
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
	array("id" => "RETRY_COUNT", "content" => GetMessage("CTRLR_TASK_COLUMN_RETRY_COUNT")),
	array("id" => "RETRY_TIMEOUT", "content" => GetMessage("CTRLR_TASK_COLUMN_RETRY_TIMEOUT")),
);

$lAdmin->AddHeaders($arHeaders);

$nav = $lAdmin->getPageNavigation("pages-controller-log-admin");

if ($lAdmin->isTotalCountRequest())
{
	$count = CControllerTask::GetList(
		array(),
		$arFilter,
		array("ID"),
		array("bOnlyCount" => true)
	);
	$lAdmin->sendTotalCountResponse($count);
}
elseif ($_REQUEST["mode"] == "excel")
{
	$arNavParams = false;
}
else
{
	$arNavParams = array(
		"nTopCount" => $nav->getLimit() + 1,
		"nOffset" => $nav->getOffset(),
	);
}

$arSelect = $lAdmin->GetVisibleHeaderColumns();
$arSelect[] = "ID";

$initExecuteSelected = in_array('INIT_EXECUTE', $arSelect);
$sourceTasks = array();

$rsData = CControllerTask::GetList(
	array(
		$by => $order,
	),
	$arFilter,
	$arSelect,
	$arNavParams
);
$rsData = new CAdminResult($rsData, $sTableID);

$n = 0;
$pageSize = $lAdmin->getNavSize();
while ($arRes = $rsData->Fetch())
{
	$n++;
	if ($n > $pageSize && !($_REQUEST["mode"] == "excel"))
	{
		break;
	}

	if (
		$initExecuteSelected
		&& $arRes['INIT_EXECUTE'] === ''.intval($arRes['INIT_EXECUTE']).''
	)
	{
		$sourceTaskId = $arRes['INIT_EXECUTE'];
		if (!isset($sourceTasks[$sourceTaskId]))
		{
			$sourceTasks[$sourceTaskId] = CControllerTask::GetArrayByID($arRes['INIT_EXECUTE']);
		}

		if ($sourceTasks[$sourceTaskId])
		{
			$arRes["INIT_EXECUTE"] = $sourceTasks[$sourceTaskId]["INIT_EXECUTE"];
		}
	}

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
			"ACTION" => $lAdmin->ActionRedirect("/bitrix/admin/controller_log_admin.php?lang=".urlencode(LANGUAGE_ID)."&apply_filter=Y&TASK_ID=".urlencode($arRes["ID"])),
		);
	}

	if ($arActions)
	{
		$row->AddActions($arActions);
	}
}
$nav->setRecordCount($nav->getOffset() + $n);
$lAdmin->setNavigation($nav, GetMessage("CTRLR_TASK_NAV"), false);

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
	$iTaskNCnt = $USER->CanDoOperation("controller_task_run")? CControllerTask::GetList([],["=STATUS" => ['P', 'N', 'R', 'L']],['ID'],['bOnlyCount' => true]): 0;
	if ($iTaskNCnt > 0)
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
	elseif ($iCntExecuted > 0)
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

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList(["SHOW_COUNT_HTML" => true]);

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
