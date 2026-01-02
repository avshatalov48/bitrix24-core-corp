<?php

use Bitrix\Tasks\Internals\Task\MetaStatus;
use Bitrix\Tasks\Internals\Task\Status;

IncludeModuleLangFile(__FILE__);

function tasksFormatDate($in_date)
{
	$date = $in_date;

	if (!is_int($in_date))
		$date = MakeTimeStamp($in_date);

	if ( ($date === false) || ($date === -1) || ($date === 0) )
		$date = MakeTimeStamp ($in_date);

	// It can be other date on server (relative to client), ...
	$bTzWasDisabled = ! CTimeZone::enabled();

	if ($bTzWasDisabled)
		CTimeZone::enable();

	$ts = time() + CTimeZone::getOffset();		// ... so shift cur timestamp to compensate it.

	if ($bTzWasDisabled)
		CTimeZone::disable();

	$curDateStrAtClient       = date('d.m.Y', $ts);
	$yesterdayDateStrAtClient = date('d.m.Y', strtotime('-1 day', $ts));


	if ($curDateStrAtClient === date('d.m.Y', $date))
	{
		$strDate = FormatDate("today", $date);
	}
	elseif ($yesterdayDateStrAtClient === date('d.m.Y', $date))
	{
		$strDate = FormatDate("yesterday", $date);
	}

	else
	{
		if (defined('FORMAT_DATE'))
		{
			$strDate = \Bitrix\Tasks\UI::formatDateTime($date, FORMAT_DATE);
		}
		else
			$strDate = FormatDate("d.m.Y", $date);
	}

	return $strDate;
}

function tasksFormatName($name, $lastName, $login, $secondName = "", $nameTemplate = "", $bEscapeSpecChars = false)
{
	if ($nameTemplate != "")
	{
		$result = CUser::FormatName($nameTemplate, array(	"NAME" 			=> $name,
															"LAST_NAME" 	=> $lastName,
															"SECOND_NAME" 	=> $secondName,
															"LOGIN"			=> $login),
			true,
			$bEscapeSpecChars);

		return $result;
	}

	if ($name || $lastName)
	{
		$rc = $name.($name && $lastName ? " " : "").$lastName;
	}
	else
	{
		$rc = $login;
	}

	if ($bEscapeSpecChars)
		$rc = htmlspecialcharsbx($rc);

	return ($rc);
}

function tasksFormatNameShort($name, $lastName, $login, $secondName = "", $nameTemplate = "", $bEscapeSpecChars = false)
{
	if ($nameTemplate != "")
	{
		$result = CUser::FormatName($nameTemplate, array(	"NAME" 			=> $name,
															"LAST_NAME" 	=> $lastName,
															"SECOND_NAME" 	=> $secondName,
															"LOGIN"			=> $login),
			true,
			$bEscapeSpecChars);

		return $result;
	}

	if ($name && $lastName)
	{
		if ( ! $bEscapeSpecChars )
			$rc = $lastName." ".mb_substr(htmlspecialcharsBack($name), 0, 1).".";
		else
			$rc = $lastName." ".mb_substr($name, 0, 1).".";
	}
	elseif ($lastName)
	{
		$rc = $lastName;
	}
	elseif ($name)
	{
		$rc = $name;
	}
	else
	{
		$rc = $login;
	}

	if ($bEscapeSpecChars)
		$rc = htmlspecialcharsbx($rc);

	return ($rc);
}

/**@deprecated
 *
 * @param $task
 * @param $arPaths
 * @param string $site_id
 * @param bool $bGantt
 * @param bool $top
 * @param bool $bSkipJsMenu
 * @param array $params
 */
function tasksGetItemMenu($task, $arPaths, $site_id = SITE_ID, $bGantt = false, $top = false, $bSkipJsMenu = false, array $params = array())
{
	$userId = \Bitrix\Tasks\Util\User::getId();

	$arAllowedTaskActions = array();
	if (isset($task['META:ALLOWED_ACTIONS']))
	{
		$arAllowedTaskActions = $task['META:ALLOWED_ACTIONS'];
	}
	elseif ($task['ID'])
	{
		$oTask = CTaskItem::getInstanceFromPool($task['ID'], $userId);
		$arAllowedTaskActions = $oTask->getAllowedTaskActionsAsStrings();
		$task['META:ALLOWED_ACTIONS'] = $arAllowedTaskActions;
	}

	$analyticsSectionCode = $task['GROUP_ID']
		? \Bitrix\Tasks\Helper\Analytics::SECTION['project']
		: \Bitrix\Tasks\Helper\Analytics::SECTION['tasks']
	;

	$editUrl = \Bitrix\Tasks\Slider\Path\TaskPathMaker::getPath([
		"task_id" => $task["ID"],
		'user_id' => $userId,
		'group_id' => $task['GROUP_ID'],
		"action" => "edit"
	]);

	$viewUrl = new \Bitrix\Main\Web\Uri(
		\Bitrix\Tasks\Slider\Path\TaskPathMaker::getPath([
			'task_id' => $task['ID'],
			'user_id' => $userId,
			'group_id' => $task['GROUP_ID'],
			'action' => 'view'
		])
	);

	$addPath = \Bitrix\Tasks\Slider\Path\TaskPathMaker::getPath([
		"task_id" => 0,
		"action" => 'edit',
		'user_id' => $userId,
		'group_id' => $task['GROUP_ID']
	]);

	$viewUrl->addParams([
		'ta_sec' => $analyticsSectionCode,
		'ta_sub' => \Bitrix\Tasks\Helper\Analytics::SUB_SECTION['gantt'],
		'ta_el' => \Bitrix\Tasks\Helper\Analytics::ELEMENT['context_menu'],
	]);

	$subtaskUrl = new \Bitrix\Main\Web\Uri($addPath);
	$subtaskUrl->addParams([
		'PARENT_ID' => $task['ID'],
		'ta_sec' => $analyticsSectionCode,
		'ta_sub' => \Bitrix\Tasks\Helper\Analytics::SUB_SECTION['gantt'],
		'ta_el' => \Bitrix\Tasks\Helper\Analytics::ELEMENT['context_menu'],
	]);

	$copyUrl = new \Bitrix\Main\Web\Uri($addPath);
	$copyUrl->addParams([
		'COPY' => $task['ID'],
		'ta_sec' => $analyticsSectionCode,
		'ta_sub' => \Bitrix\Tasks\Helper\Analytics::SUB_SECTION['gantt'],
		'ta_el' => \Bitrix\Tasks\Helper\Analytics::ELEMENT['context_menu'],
	]);

	$inFavorite = false;
	if(
		isset($params['VIEW_STATE'])
		&& is_array($params['VIEW_STATE'])
		&& $params['VIEW_STATE']['SECTION_SELECTED']['CODENAME'] === 'VIEW_SECTION_ADVANCED_FILTER'
		&& ($params['VIEW_STATE']['SPECIAL_PRESET_SELECTED']['CODENAME'] ?? null) === 'FAVORITE'
	)
	{
		$inFavorite = true;
	}

	?>
		{
			text : "<?=GetMessage("TASKS_VIEW_TASK")?>",
			title : "<?=GetMessage("TASKS_VIEW_TASK_EX")?>",
			className : "menu-popup-item-view",
			href : "<? echo CUtil::JSEscape($viewUrl->getUri())?>"
		},

		<? if ($arAllowedTaskActions['ACTION_EDIT']):?>
		{
			text : "<?=GetMessage("TASKS_EDIT_TASK")?>",
			title : "<?=GetMessage("TASKS_EDIT_TASK_EX")?>",
			className : "menu-popup-item-edit",
			href : "<? echo CUtil::JSEscape($editUrl)?>"
		},
		<? endif?>

		{
			text : "<?=GetMessage("TASKS_ADD_SUBTASK"); ?>",
			title : "<?=GetMessage("TASKS_ADD_SUBTASK"); ?>",
			className : "menu-popup-item-create",
			href : "<? echo CUtil::JSEscape($subtaskUrl->getUri())?>"
		},

		<?

		if ($bGantt && ($arAllowedTaskActions['ACTION_EDIT'] || $arAllowedTaskActions['ACTION_CHANGE_DEADLINE']))
		{
			?>
			{
				text : "<? if(!$task["DEADLINE"]):?><?=GetMessage("TASKS_ADD_DEADLINE")?><? else:?><?=GetMessage("TASKS_REMOVE_DEADLINE")?><? endif?>",
				title : "<? if(!$task["DEADLINE"]):?><?=GetMessage("TASKS_ADD_DEADLINE")?><? else:?><?=GetMessage("TASKS_REMOVE_DEADLINE")?><? endif?>",
				className : "<? if(!$task["DEADLINE"]):?>task-menu-popup-item-add-deadline<? else:?>task-menu-popup-item-remove-deadline<? endif?>",
				onclick : BX.CJSTask.fixWindow(function(window, top, event, item)
				{
					var BX = top.BX;

					if (BX.hasClass(item.layout.item, "task-menu-popup-item-add-deadline"))
					{
						BX.removeClass(item.layout.item, "task-menu-popup-item-add-deadline");
						BX.addClass(item.layout.item, "task-menu-popup-item-remove-deadline");
						item.layout.text.innerHTML = "<?=GetMessage("TASKS_REMOVE_DEADLINE")?>";

						var deadline = BX.GanttChart.convertDateFromUTC(this.params.task.dateEnd);
						deadline.setDate(deadline.getDate() + 3);

						if(typeof top.COMPANY_WORKTIME != 'undefined')
							deadline = BX.CJSTask.addTimeToDate(deadline, top.COMPANY_WORKTIME);

						this.params.task.setDateDeadline(deadline);
						this.params.task.redraw();
						this.popupWindow.close();

						// this should pass through
						var data = {
							mode : "deadline",
							sessid : BX.message("bitrix_sessid"),
							id : this.params.task.id,
							deadline : top.tasksFormatDate(deadline)
						};
						BX.ajax.post(top.ajaxUrl, data);
					}
					else
					{
						BX.removeClass(item.layout.item, "task-menu-popup-item-remove-deadline");
						BX.addClass(item.layout.item, "task-menu-popup-item-add-deadline");
						item.layout.text.innerHTML = "<?=GetMessage("TASKS_ADD_DEADLINE")?>";
						this.params.task.setDateDeadline(null);
						this.params.task.redraw();
						this.popupWindow.close();

						var data = {
							mode : "deadline",
							sessid : BX.message("bitrix_sessid"),
							id : this.params.task.id,
							deadline : ""
						};
						BX.ajax.post(top.ajaxUrl, data);
					}
				})
			},
			<?
		}

		if ($arAllowedTaskActions['ACTION_ADD_FAVORITE'])
		{
			?>{
				text : "<?=GetMessage("ACTION_ADD_FAVORITE")?>",
				title : "<?=GetMessage("ACTION_ADD_FAVORITE")?>",
				className : "task-menu-popup-item-favorite",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.AddToFavorite) || (top && top.AddToFavorite) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},
			<?
		}

		if ($arAllowedTaskActions['ACTION_DELETE_FAVORITE'])
		{
			?>{
				text : "<?=GetMessage("ACTION_DELETE_FAVORITE")?>",
				title : "<?=GetMessage("ACTION_DELETE_FAVORITE")?>",
				className : "task-menu-popup-item-favorite",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.DeleteFavorite) || (top && top.DeleteFavorite) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>, {mode: 'delete-subtree', rowDelete: <?=($inFavorite ? 'true' : 'false')?>});
					this.popupWindow.close();
				})
			},
			<?
		}

		if ($arAllowedTaskActions['ACTION_COMPLETE'])
		{
			?>{
				text : "<?=GetMessage("TASKS_CLOSE_TASK")?>",
				title : "<?=GetMessage("TASKS_CLOSE_TASK")?>",
				className : "menu-popup-item-complete",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.CloseTask) || (top && top.CloseTask) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>, '<?= $analyticsSectionCode ?>');
					this.popupWindow.close();
				})
			},<?
		}

	if ($arAllowedTaskActions['ACTION_START'])
		{
			?>{
				text : "<?=GetMessage("TASKS_START_TASK")?>",
				title : "<?=GetMessage("TASKS_START_TASK")?>",
				className : "menu-popup-item-begin",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.StartTask) || (top && top.StartTask) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}

		if ($arAllowedTaskActions['ACTION_PAUSE'])
		{
			?>{
				text : "<?=GetMessage("TASKS_PAUSE_TASK")?>",
				title : "<?=GetMessage("TASKS_PAUSE_TASK")?>",
				className : "task-menu-popup-item-pause",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.PauseTask) || (top && top.PauseTask) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}

		if ($arAllowedTaskActions['ACTION_RENEW'])
		{
			?>{
				text : "<?=GetMessage("TASKS_RENEW_TASK")?>",
				title : "<?=GetMessage("TASKS_RENEW_TASK")?>",
				className : "menu-popup-item-reopen",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.RenewTask) || (top && top.RenewTask) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}

		if ($arAllowedTaskActions['ACTION_DEFER'])
		{
			?>{
				text : "<?=GetMessage("TASKS_DEFER_TASK")?>",
				title : "<?=GetMessage("TASKS_DEFER_TASK")?>",
				className : "menu-popup-item-hold",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.DeferTask) || (top && top.DeferTask) || BX.DoNothing;
					fn(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}

		if ($arAllowedTaskActions['ACTION_APPROVE'])
		{
			?>{
				text : "<?=GetMessage("TASKS_APPROVE_TASK")?>",
				title : "<?=GetMessage("TASKS_APPROVE_TASK")?>",
				className : "menu-popup-item-accept",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.tasksListNS) || (top && top.tasksListNS) || BX.DoNothing;
					fn.approveTask(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}

		if ($arAllowedTaskActions['ACTION_DISAPPROVE'])
		{
			?>{
				text : "<?=GetMessage("TASKS_REDO_TASK_MSGVER_1")?>",
				title : "<?=GetMessage("TASKS_REDO_TASK_MSGVER_1")?>",
				className : "menu-popup-item-remake",
				onclick : BX.CJSTask.fixWindow(function(window, top, event) {
					var fn = (window && window.tasksListNS) || (top && top.tasksListNS) || BX.DoNothing;
					fn.disapproveTask(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}

		?>

		{
			text : "<?=GetMessage("TASKS_COPY_TASK")?>",
			title : "<?=GetMessage("TASKS_COPY_TASK_EX")?>",
			className : "menu-popup-item-copy",
			href : "<? echo CUtil::JSEscape($copyUrl->getUri())?>"
		},

		<?

		// Only responsible person and accomplices can add task to day plan
		// And we must be not at extranet site
		if (
			(
			$task["RESPONSIBLE_ID"] == $userId
			|| (
				is_array($task['ACCOMPLICES'] ?? null)
				&& in_array($userId, $task['ACCOMPLICES'])
				)
			)
			&& (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite())
		)
		{
			$arTasksInPlan = CTaskPlannerMaintance::getCurrentTasksList();

			// If not in day plan already
			if (!(is_array($arTasksInPlan) && in_array($task["ID"], $arTasksInPlan)))
			{
				?>
				{
					text : "<?=GetMessage("TASKS_ADD_TASK_TO_TIMEMAN")?>",
					title : "<?=GetMessage("TASKS_ADD_TASK_TO_TIMEMAN_EX")?>",
					className : "menu-popup-item-add-to-tm",
					onclick : BX.CJSTask.fixWindow(function(window, top, event, item) {
						var fn = (window && window.Add2Timeman) || (top && top.Add2Timeman) || BX.DoNothing;
						fn(this, <?=intval($task["ID"])?>);
					})
				},<?
			}
		}

		if ($arAllowedTaskActions['ACTION_REMOVE'])
		{
			?>
			{
				text : "<?=GetMessage("TASKS_DELETE_TASK")?>",
				title : "<?=GetMessage("TASKS_DELETE_TASK")?>",
				className : "menu-popup-item-delete",
				onclick : BX.CJSTask.fixWindow(function(window, top, event)
				{
					var fn = (window && window.DeleteTask) || (top && top.DeleteTask) || BX.DoNothing;
					this.menuItems = [];
					this.bindElement.onclick = function() { return (false); };
					fn(<?= (int)$task["ID"] ?>);
					this.popupWindow.close();
				})
			},<?
		}
		?>
		{}
	<?
}

/**@deprecated
 *
 * @param $arTask
 * @param $childrenCount
 * @param $arPaths
 * @param bool $bParent
 * @param bool $bGant
 * @param bool $top
 * @param string $nameTemplate
 * @param array $arAdditionalFields
 * @param bool $bSkipJsMenu
 * @param array $params
 *
 */
function tasksRenderJSON(
	$arTask, $childrenCount, $arPaths, $bParent = false, $bGant = false,
	$top = false, $nameTemplate = "", $arAdditionalFields = array(), $bSkipJsMenu = false, array $params = array()
)
{
	$userId = \Bitrix\Tasks\Util\User::getId();

	if (array_key_exists('USER_ID', $params))
	{
		$profileUserId = (int)$params['USER_ID'];
	}
	else
	{
		$profileUserId = $userId;
	}

	$arAllowedTaskActions = array();
	if (isset($arTask['META:ALLOWED_ACTIONS']))
		$arAllowedTaskActions = $arTask['META:ALLOWED_ACTIONS'];
	elseif ($arTask['ID'])
	{
		$oTask = CTaskItem::getInstanceFromPool($arTask['ID'], $userId);
		$arAllowedTaskActions = $oTask->getAllowedTaskActionsAsStrings();
		$arTask['META:ALLOWED_ACTIONS'] = $arAllowedTaskActions;
	}

	$runningTaskId = $runningTaskTimer = null;
	if (
		isset($arTask['ALLOW_TIME_TRACKING'])
		&& $arTask['ALLOW_TIME_TRACKING'] === 'Y'
	)
	{
		$oTimer           = CTaskTimerManager::getInstance($userId);
		$runningTaskData  = $oTimer->getRunningTask(false);
		if ($runningTaskData && is_array($runningTaskData))
		{
			$runningTaskId    = $runningTaskData['TASK_ID'];
			$runningTaskTimer = time() - $runningTaskData['TIMER_STARTED_AT'];
		}
	}

	$canCreateTasks = false;
	$canEditTasks = false;
	if ($arTask["GROUP_ID"])
	{
		$canCreateTasks = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $arTask["GROUP_ID"], "tasks", "create_tasks");
		$canEditTasks = \CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $arTask["GROUP_ID"], "tasks", "edit_tasks");
	}
	?>
	{
		id : <?=intval($arTask["ID"])?>,
		name : "<?=CUtil::JSEscape($arTask["TITLE"])?>",
		<?if ($arTask["GROUP_ID"]):?>
			projectId : <?=intval($arTask["GROUP_ID"])?>,
			projectName : '<?=CUtil::JSEscape($arTask['GROUP_NAME'] ?? null)?>',
			projectCanCreateTasks: <?=CUtil::PhpToJSObject($canCreateTasks)?>,
			projectCanEditTasks: <?=CUtil::PhpToJSObject($canEditTasks)?>,
		<?else:?>
			projectId : 0,
		<?endif?>
		status : "<?=tasksStatus2String($arTask["STATUS"])?>",
		realStatus : "<?=intval($arTask["REAL_STATUS"])?>",
		url: '<?=CUtil::JSEscape(CComponentEngine::MakePathFromTemplate(
				$arPaths["PATH_TO_TASKS_TASK"],
				array(
					"task_id" => $arTask["ID"],
					"user_id" => $profileUserId,
					"action" => "view",
					"group_id" => $arTask["GROUP_ID"]
				)
			));?>',
		priority : <?=intval($arTask["PRIORITY"])?>,
		mark : <?php echo !$arTask["MARK"] ? "null" : "'".CUtil::JSEscape($arTask["MARK"])."'"?>,
		responsible: '<?=CUtil::JSEscape(tasksFormatNameShort($arTask["RESPONSIBLE_NAME"], $arTask["RESPONSIBLE_LAST_NAME"], $arTask["RESPONSIBLE_LOGIN"], $arTask["RESPONSIBLE_SECOND_NAME"], $nameTemplate))?>',
		director: '<?=CUtil::JSEscape(tasksFormatNameShort($arTask["CREATED_BY_NAME"], $arTask["CREATED_BY_LAST_NAME"], $arTask["CREATED_BY_LOGIN"], $arTask["CREATED_BY_SECOND_NAME"], $nameTemplate))?>',
		responsibleId : <?=intval($arTask["RESPONSIBLE_ID"])?>,
		directorId : <?=intval($arTask["CREATED_BY"])?>,
		responsible_name: '<?=CUtil::JSEscape($arTask["RESPONSIBLE_NAME"]); ?>',
		responsible_second_name: '<?=CUtil::JSEscape($arTask["RESPONSIBLE_SECOND_NAME"]); ?>',
		responsible_last_name: '<?=CUtil::JSEscape($arTask["RESPONSIBLE_LAST_NAME"]); ?>',
		responsible_login: '<?=CUtil::JSEscape($arTask["RESPONSIBLE_LOGIN"]); ?>',
		director_name: '<?=CUtil::JSEscape($arTask["CREATED_BY_NAME"]); ?>',
		director_second_name: '<?=CUtil::JSEscape($arTask["CREATED_BY_SECOND_NAME"]); ?>',
		director_last_name: '<?=CUtil::JSEscape($arTask["CREATED_BY_LAST_NAME"]); ?>',
		director_login: '<?=CUtil::JSEscape($arTask["CREATED_BY_LOGIN"]); ?>',
		dateCreated : <?tasksJSDateObject($arTask["CREATED_DATE"], $top)?>,

		<?php
			$links = $arTask['LINKS'] ?? null;
		?>
		links: <?=CUtil::PhpToJSObject($links, false, false, true)?>,

		<?php if ($arTask["START_DATE_PLAN"]):?>dateStart : <?php tasksJSDateObject($arTask["START_DATE_PLAN"], $top)?>,<?php else:?>dateStart: null,<?php endif?>

		<?php if ($arTask["END_DATE_PLAN"]):?>dateEnd : <?php tasksJSDateObject($arTask["END_DATE_PLAN"], $top)?>,<?php else:?>dateEnd: null,<?php endif?>

		<?php if ($arTask["DATE_START"]):?>dateStarted: <?php tasksJSDateObject($arTask["DATE_START"], $top)?>,<?php endif?>

		dateCompleted : <?php if ($arTask["CLOSED_DATE"]):?><?php tasksJSDateObject($arTask["CLOSED_DATE"], $top)?><?php else:?>null<?php endif?>,

		<?php if ($arTask["DEADLINE"]):?>dateDeadline : <?php tasksJSDateObject($arTask["DEADLINE"], $top)?>,<?php else:?>dateDeadline: null,<?php endif?>

		canEditPlanDates : <?php if ($arAllowedTaskActions['ACTION_CHANGE_DEADLINE']):?>true<?php else:?>false<?php endif?>,

		canEdit: <?=(isset($arTask["META:ALLOWED_ACTIONS"]) && $arTask["META:ALLOWED_ACTIONS"]["ACTION_EDIT"] ? "true" : "false")?>,

		<?if ($arTask["PARENT_ID"] && $bParent):?>
			parentTaskId : <?=intval($arTask["PARENT_ID"])?>,
		<?else:?>
			parentTaskId : 0,
		<?endif?>

		<?php
			if (isset($arTask["FILES"]) && is_array($arTask["FILES"]) && sizeof($arTask["FILES"])):
				$i = 0;
		?>
			files: [
				<?php
					foreach($arTask["FILES"] as $file):
						$i++;
				?>
				{ name : '<?php echo CUtil::JSEscape($file["ORIGINAL_NAME"])?>', url : '/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=<?=intval($file["ID"])?>', size : '<?php echo CUtil::JSEscape(CFile::FormatSize($file["FILE_SIZE"]))?>' }<?php if ($i != sizeof($arTask["FILES"])):?>,<?php endif?>
				<?php endforeach?>
			],
		<?php endif?>

		<?php
		if (($arTask['ACCOMPLICES'] ?? null) && is_array($arTask['ACCOMPLICES']))
		{
			$i = 0;
			echo 'accomplices: [';
			foreach($arTask['ACCOMPLICES'] as $ACCOMPLICE_ID)
			{
				if ($i++)
					echo ',';

				echo '{ id: ' . (int) $ACCOMPLICE_ID . ' }';
			}
			echo '], ';
		}
		?>

		<?php
		if (($arTask['AUDITORS'] ?? null) && is_array($arTask['AUDITORS']))
		{
			$i = 0;
			echo 'auditors: [';
			foreach($arTask['AUDITORS'] as $AUDITOR_ID)
			{
				if ($i++)
					echo ',';

				echo '{ id: ' . (int) $AUDITOR_ID . ' }';
			}
			echo '], ';
		}
		?>

		isSubordinate: <?php echo $arTask["SUBORDINATE"] == "Y" ? "true" : "false"?>,
		isInReport: <?php echo $arTask["ADD_IN_REPORT"] == "Y" ? "true" : "false"?>,
		hasChildren : <?php
			if (((int) $childrenCount) > 0)
				echo 'true';
			else
				echo 'false';
		?>,
		childrenCount : <?php echo (int) $childrenCount; ?>,
		canEditDeadline : <?php
			if ($arAllowedTaskActions['ACTION_CHANGE_DEADLINE'])
				echo 'true';
			else
				echo 'false';
		?>,
		canStartTimeTracking : <?php if ($arAllowedTaskActions['ACTION_START_TIME_TRACKING']):?>true<?php else:?>false<?php endif?>,
		ALLOW_TIME_TRACKING : <?php
			if (isset($arTask['ALLOW_TIME_TRACKING']) && ($arTask['ALLOW_TIME_TRACKING'] === 'Y'))
				echo 'true';
			else
				echo 'false';
		?>,
		matchWorkTime: <?=($arTask['MATCH_WORK_TIME'] == 'Y' ? 'true' : 'false')?>,
		TIMER_RUN_TIME : <?php if ($runningTaskId == $arTask['ID']) echo (int) $runningTaskTimer; else echo 'false'; ?>,
		TIME_SPENT_IN_LOGS : <?php echo (int) $arTask['TIME_SPENT_IN_LOGS']; ?>,
		TIME_ESTIMATE : <?php echo (int) $arTask['TIME_ESTIMATE']; ?>,
		IS_TASK_TRACKING_NOW : <?php if ($runningTaskId == $arTask['ID']) echo 'true'; else echo 'false'; ?>,
		menuItems: [<?php tasksGetItemMenu($arTask, $arPaths, SITE_ID, $bGant, $top, $bSkipJsMenu, $params)?>],

		<?$arTask['SE_PARAMETER'] = is_array($arTask['SE_PARAMETER'] ?? null) ? $arTask['SE_PARAMETER'] : [];?>
		<?$seParameters = array();?>
		<?foreach($arTask['SE_PARAMETER'] as $k => $v):?>
			<?if($v['VALUE'] == 'Y' || $v['VALUE'] == 'N'):?>
				<?
				$code = $v['CODE'];
				if($code == \Bitrix\Tasks\Internals\Task\ParameterTable::PARAM_SUBTASKS_AUTOCOMPLETE)
				{
					$code = 'completeTasksFromSubTasks';
				}
				elseif($code == \Bitrix\Tasks\Internals\Task\ParameterTable::PARAM_SUBTASKS_TIME)
				{
					$code = 'projectPlanFromSubTasks';
				}
				?>
				<?$seParameters[$code] = $v['VALUE'] == 'Y';?>
			<?endif?>
		<?endforeach?>
		parameters: <?=json_encode($seParameters)?>

		<?php
		foreach ($arAdditionalFields as $key => $value)
			echo ', ' . $key . ' : ' . $value . "\n";
		?>
	}
<?php
}


function tasksJSDateObject($date, $top = false)
{
	$ts = MakeTimeStamp($date);
	?>
	new <?php if ($top):?>top.<?php endif?>Date(<?php
		echo date("Y", $ts); ?>, <?php
		echo date("n", $ts) - 1; ?>, <?php
		echo date("j", $ts); ?>, <?php
		echo date("G", $ts); ?>, <?php
		echo (date("i", $ts) + 0); ?>, <?php
		echo (date("s", $ts) + 0); ?>)
	<?php
}


function tasksStatus2String($status)
{
	$arMap = [
		MetaStatus::EXPIRED => 'overdue',
		MetaStatus::UNSEEN => 'new',
		Status::NEW => 'accepted',
		MetaStatus::EXPIRED_SOON => 'overdue-soon',
		Status::PENDING => 'accepted',
		Status::IN_PROGRESS => 'in-progress',
		Status::SUPPOSEDLY_COMPLETED => 'waiting',
		Status::COMPLETED => 'completed',
		Status::DEFERRED => 'delayed',
		Status::DECLINED => 'declined',
	];

	$strStatus = "";
	if (isset($arMap[$status]))
		$strStatus = $arMap[$status];

	return $strStatus;
}

/**
 * @deprecated and will be removed
 *
 * @use \Bitrix\Tasks\V2\Internal\Service\UrlService
 */
function tasksServerName($server_name = false)
{
	return \Bitrix\Tasks\V2\Internal\DI\Container::getInstance()->getUrlService()->getHostUrl((string)$server_name);
}

define("TASKS_FILTER_SESSION_INDEX", "FILTER");


function tasksGetFilter($fieldName)
{
	if (isset($_GET[$fieldName]))
	{
		$_SESSION[TASKS_FILTER_SESSION_INDEX][$fieldName] = $_GET[$fieldName];
	}

	return $_SESSION[TASKS_FILTER_SESSION_INDEX][$fieldName];
}


function tasksPredefinedFilters($userID, $roleFilterSuffix = "")
{
	return array(
		"ROLE" => array(
			array("TITLE" => GetMessage("TASKS_FILTER_MY".$roleFilterSuffix), "FILTER" => array("DOER" => $userID), "CLASS" => "inbox", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_RESPONSIBLE".$roleFilterSuffix), "FILTER" => array("RESPONSIBLE_ID" => $userID), "CLASS" => "my-responsibility", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_ACCOMPLICE".$roleFilterSuffix), "FILTER" => array("ACCOMPLICE" => $userID), "CLASS" => "my-complicity", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_IN_REPORT".$roleFilterSuffix), "FILTER" => array("RESPONSIBLE_ID" => $userID, "ADD_IN_REPORT" => "Y"), "CLASS" => "my-report", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_CREATOR".$roleFilterSuffix), "FILTER" => array("CREATED_BY" => $userID), "CLASS" => "outbox", "COUNT" => "-", "STATUS_FILTER" => 1),
			array("TITLE" => GetMessage("TASKS_FILTER_FOR_REPORT".$roleFilterSuffix), "FILTER" => array("CREATED_BY" => $userID, "ADD_IN_REPORT" => "Y"), "CLASS" => "my-report", "COUNT" => "-", "STATUS_FILTER" => 1),
			array("TITLE" => GetMessage("TASKS_FILTER_AUDITOR".$roleFilterSuffix), "FILTER" => array("AUDITOR" => $userID), "CLASS" => "under-control", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_ALL"), "FILTER" => array("MEMBER" => $userID), "CLASS" => "anybox", "COUNT" => "-", "STATUS_FILTER" => 0)
		),
		"STATUS" => array(
			array(
				array("TITLE" => GetMessage("TASKS_FILTER_ACTIVE"), "FILTER" => array("STATUS" => array(-2, -1, 1, 2, 3)), "CLASS" => "open", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_NEW"), "FILTER" => array("STATUS" => array(-2, 1)), "CLASS" => "new", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_IN_PROGRESS"), "FILTER" => array("STATUS" => 3), "CLASS" => "in-progress", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_ACCEPTED"), "FILTER" => array("STATUS" => 2), "CLASS" => "accepted", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_OVERDUE"), "FILTER" => array("STATUS" => -1), "CLASS" => "overdue", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_DELAYED"), "FILTER" => array("STATUS" => 6), "CLASS" => "delayed", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_CLOSED"), "FILTER" => array("STATUS" => array(4, 5)), "CLASS" => "completed", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_ALL"), "FILTER" => array(), "CLASS" => "any", "COUNT" => "-")
			),
			array(
				array("TITLE" => GetMessage("TASKS_FILTER_ACTIVE"), "FILTER" => array("STATUS" => array(-1, 1, 2, 3, 4, 7)), "CLASS" => "open", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_NOT_ACCEPTED"), "FILTER" => array("STATUS" => 1), "CLASS" => "new", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_IN_CONTROL"), "FILTER" => array("STATUS" => array(4, 7)), "CLASS" => "waiting", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_IN_PROGRESS"), "FILTER" => array("STATUS" => 3), "CLASS" => "in-progress", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_ACCEPTED"), "FILTER" => array("STATUS" => 2), "CLASS" => "accepted", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_OVERDUE"), "FILTER" => array("STATUS" => -1), "CLASS" => "overdue", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_DELAYED"), "FILTER" => array("STATUS" => 6), "CLASS" => "delayed", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_CLOSED"), "FILTER" => array("STATUS" => array(4, 5)), "CLASS" => "completed", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_ALL"), "FILTER" => array(), "CLASS" => "any", "COUNT" => "-")
			)
		)
	);
}

function __checkForum($forumID)
{
	if (!($settingsForumID = COption::GetOptionString("tasks", "task_forum_id")))
	{
		if ( (int) $forumID > 0 )
			COption::SetOptionString("tasks", "task_forum_id", intval($forumID));
	}

	if (IsModuleInstalled('extranet'))
	{
		if (-1 === COption::GetOptionString('tasks', 'task_extranet_forum_id', -1, $siteId = ''))
		{
			try
			{
				$extranetForumID = CTasksTools::GetForumIdForExtranet();
				COption::SetOptionString('tasks', 'task_extranet_forum_id', $extranetForumID, '', $siteId = '');
			}
			catch (TasksException $e)
			{
				COption::SetOptionString('tasks', 'task_extranet_forum_id', (int) $forumID, '', $siteId = '');
			}
		}
	}

	if (CModule::IncludeModule("forum") && $forumID && COption::GetOptionString("tasks", "forum_checked", false))
	{
		$arGroups = array();
		$rs = CGroup::GetList('id', 'asc');
		while($ar = $rs->Fetch())
			$arGroups[$ar['ID']] = 'A';

		CForumNew::Update($forumID, array("GROUP_ID"=>$arGroups, "INDEXATION" => "Y"));
		COption::RemoveOption("tasks", "forum_checked");
	}
}
