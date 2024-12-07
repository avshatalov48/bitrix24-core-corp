<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\UI;
use Bitrix\Tasks\Internals\Task\Status;

UI\Extension::load("ui.tooltip");

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");

$task = isset($arParams["~TASK"]) ? $arParams["~TASK"] : null;
if ($task === null)
{
	return;
}

$childrenCount = $arParams["CHILDREN_COUNT"];
$arPaths = $arParams["PATHS"];
$depth = isset($arParams["DEPTH"]) ? $arParams["DEPTH"] : 0;
$plain = isset($arParams["PLAIN"]) ? $arParams["PLAIN"] : false;
$defer = isset($arParams["DEFER"]) ? $arParams["DEFER"] : false;
$site_id = isset($arParams["SITE_ID"]) ? $arParams["SITE_ID"] : SITE_ID;
$updatesCount = isset($arParams["UPDATES_COUNT"]) ? $arParams["UPDATES_COUNT"] : 0;
$projectExpanded = isset($arParams["PROJECT_EXPANDED"]) ? $arParams["PROJECT_EXPANDED"] : true;
$taskAdded = isset($arParams["TASK_ADDED"]) ? $arParams["TASK_ADDED"] : false;
$nameFormat = $arParams["NAME_TEMPLATE"] ?  $arParams["NAME_TEMPLATE"] : CSite::GetNameFormat();

$anchor_id = RandString(8);
$viewUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => $task["ID"], "action" => "view"));
$editUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => $task["ID"], "action" => "edit"));
$copyUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"));
$createUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"));
$createUrl = $createUrl.(mb_strpos($createUrl, "?") === false ? "?" : "&")."PARENT_ID=".$task["ID"];

if ($arResult['IFRAME'] === 'Y')
	$viewUrl = $viewUrl.(mb_strpos($viewUrl, "?") === false ? "?" : "&")."IFRAME=Y";

?>
<tr class="task-list-item task-depth-<?php echo $depth?> task-status-<?php echo tasksStatus2String($task["STATUS"])?>"
		id="task-<?php echo $task["ID"]?>"
		oncontextmenu="return ShowMenuPopupContext(<?php echo $task["ID"]?>, event);"
		ondblclick="window.location = '<?php echo CUtil::JSEscape($viewUrl); ?>'; return (false);"
		title="<?php echo GetMessage("TASKS_DOUBLE_CLICK")?>"<?php if (!$projectExpanded):?> style="display: none;"<?php endif?>>
	<td class="task-title-column">
		<div class="task-title-container">
			<?php if ($childrenCount > 0 && !$plain):?>
				<div class="task-title-folding" onclick="ToggleSubtasks(this.parentNode.parentNode.parentNode, <?php echo $depth?>, <?php echo $task["ID"]?>)"><span><?php echo $childrenCount?></span></div>
			<?php endif?>
			<div id="task-title-div-<?php echo $task["ID"]?>" class="task-title-info<?php if ($task["COMMENTS_COUNT"] || $updatesCount || sizeof($task["FILES"])):?> task-indicators<?php if ($updatesCount):?>-updates<?php endif?><?php if(sizeof($task["FILES"])):?>-files<?php endif?><?php if ($task["COMMENTS_COUNT"]):?>-comments<?php endif?><?php endif?>">
				<?php
				$bShowInPopup = false;
				if (COption::GetOptionString('tasks', 'use_task_view_popup_in_list') === 'Y')
				{
					if (CTasksTools::IsIphoneOrIpad())
						$bShowInPopup = false;
					else
						$bShowInPopup = true;
				}

				if ($task["MULTITASK"] == "Y")
				{
					?><span class="task-title-multiple"
						title="<?php echo GetMessage("TASKS_MULTITASK"); ?>"
					></span><?php
				}

				?><a href="<?php echo $viewUrl?>" class="task-title-link"
					target="_top"
					onmouseover="ShowTaskQuickInfo(<?php echo $task["ID"]?>, event);"
					onmouseout="HideTaskQuickInfo(<?php echo $task["ID"]?>, event);"
					><?php echo $task["TITLE"];
				?></a><?php

				if ($updatesCount)
				{
					?><a href="<?php echo $viewUrl?>#updates"
						class="task-item-updates"
						title="<?php
							echo str_replace(
								"#NUM#",
								$updatesCount,
								GetMessage("TASKS_UPDATES_COUNT")
							);
						?>"
					><span class="task-item-updates-inner"><?php echo $updatesCount?></span></a><?php
				}

				if ($task["COMMENTS_COUNT"] || sizeof($task["FILES"]))
				{
					?><span class="task-title-indicators"><?php
						if(sizeof($task["FILES"]))
						{
							?><span class="task-title-files"
								onmouseover="ShowTaskQuickInfo(<?php echo $task["ID"]?>, event);"
								onmouseout="HideTaskQuickInfo(<?php echo $task["ID"]?>, event);"
							></span><?php
						}

						if ($task["COMMENTS_COUNT"])
						{
							?><a href="<?php echo $viewUrl?>#comments"
								class="task-title-comments"
								title="<?php
									echo str_replace(
										"#NUM#",
										$task["COMMENTS_COUNT"],
										GetMessage("TASKS_COMMENTS_COUNT")
									);
							?>"><?php echo $task["COMMENTS_COUNT"]; ?></a><?php
						}
					?></span><?php
				}
				?>
			</div>
		</div>
	</td>
	<td class="task-menu-column"><a href="javascript: void(0)" class="task-menu-button" onclick="return ShowMenuPopup(<?php echo $task["ID"]?>, this);" title="<?php echo GetMessage("TASKS_MENU")?>"><i class="task-menu-button-icon"></i></a></td>
	<td class="task-flag-column"><?php
		if ($arResult['ALLOWED_ACTIONS']['ACTION_APPROVE'])
		{
			?><span class="task-flag-confirm-required" title="<?php echo GetMessage("TASKS_CONFIRM_REQUIRED")?>"></span><?php
		}
		elseif (
			(
				((int)$task["REAL_STATUS"] === Status::NEW)
				&& ($task["CREATED_BY"] == $USER->GetID())
			)
			|| (
				((int)$task["REAL_STATUS"] === Status::SUPPOSEDLY_COMPLETED)
				&& ($task["RESPONSIBLE_ID"] == $USER->GetID())
			)
		)
		{
			?><span class="task-flag-waiting-confirm" title="<?php echo GetMessage("TASKS_WAINTING_CONFIRM")?>"></span><?php
		}
		elseif ($arResult['ALLOWED_ACTIONS']['ACTION_START'])
		{
			?><a href="javascript: void(0)" class="task-flag-begin-perform" onClick="StartTask(<?php echo $task["ID"]?>)" title="<?php echo GetMessage("TASKS_START")?>"></a><?php
		}
		elseif ((int)$task["REAL_STATUS"] === Status::IN_PROGRESS)
		{
			?><span class="task-flag-in-progress" title="<?php echo GetMessage("TASKS_IN_PROGRESS")?>"></span><?php
		}
		else
		{
			?>&nbsp;<?php
		}
	?></td>
	<td class="task-priority-column">
		<?php
		if ($arResult['ALLOWED_ACTIONS']['ACTION_EDIT'])
		{
			?>
			<a href="javascript: void(0)" class="task-priority-box" onclick="return ShowPriorityPopup(<?php echo $task["ID"]?>, this, <?php echo $task["PRIORITY"]?>);" title="<?php echo GetMessage("TASKS_PRIORITY")?>: <?php echo GetMessage("TASKS_PRIORITY_".$task["PRIORITY"])?>"><i class="task-priority-icon task-priority-<?php if ($task["PRIORITY"] == 0):?>low<?php elseif ($task["PRIORITY"] == 2):?>high<?php else:?>medium<?php endif?>"></i></a>
			<?php
		}
		else
		{
			?>
			<i class="task-priority-icon task-priority-<?php if ($task["PRIORITY"] == 0):?>low<?php elseif ($task["PRIORITY"] == 2):?>high<?php else:?>medium<?php endif?>" title="<?php echo GetMessage("TASKS_PRIORITY")?>: <?php echo GetMessage("TASKS_PRIORITY_".$task["PRIORITY"])?>"></i>
			<?php
		}
		?>
	</td>
	<td class="task-deadline-column">
		<?php
		if (
			$arResult['ALLOWED_ACTIONS']['ACTION_EDIT']
			|| $arResult['ALLOWED_ACTIONS']['ACTION_CHANGE_DEADLINE']
		)
		{
			if ($task["DEADLINE"])
			{
				?>
				<span class="task-deadline-datetime">
					<span class="task-deadline-date webform-field-action-link"
						onclick="tasksListNS.onDeadlineChangeClick(<?php echo $task['ID']; ?>, this, '<?php echo $task["DEADLINE"]; ?>');"
					>
					<?php echo formatDateTasks($task["DEADLINE"]); ?>
					</span>
				</span>

				<?php
				if(convertTimeToMilitary($task["DEADLINE"], CSite::GetDateFormat(), "HH:MI") != "00:00")
				{
					?><span class="task-deadline-time webform-field-action-link"
						onclick="tasksListNS.onDeadlineChangeClick(<?php echo $task['ID']; ?>, this, '<?php echo $task["DEADLINE"]; ?>');"
					><?php
					echo convertTimeToMilitary($task["DEADLINE"], CSite::GetDateFormat(), CSite::GetTimeFormat());
					?></span><?php
				}
			}
			else
				echo '&nbsp;';
		}
		else
		{
			if ($task["DEADLINE"])
			{
				?>
				<span class="task-deadline-datetime">
					<span class="task-deadline-date">
					<?php echo formatDateTasks($task["DEADLINE"])?>
					</span>
				</span>

				<?php
				if(convertTimeToMilitary($task["DEADLINE"], CSite::GetDateFormat(), "HH:MI") != "00:00")
				{
					?><span class="task-deadline-time"><?php
					echo convertTimeToMilitary($task["DEADLINE"], CSite::GetDateFormat(), CSite::GetTimeFormat());
					?></span><?php
				}
			}
			else
				echo '&nbsp;';
		}
		?>
	</td>
	<td class="task-responsible-column"><a class="task-responsible-link" target="_top"
		href="<?php echo CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_USER_PROFILE"], array("user_id" => $task["RESPONSIBLE_ID"]))?>"
		id="anchor_responsible_<?php echo $anchor_id?>" bx-tooltip-user-id="<?=$task["RESPONSIBLE_ID"]?>"><?php
		// Force special format for russian version of site.
		if (LANGUAGE_ID === 'ru')
		{
			if(trim($task['RESPONSIBLE_LAST_NAME']).trim($task['RESPONSIBLE_NAME']) <> '')
			{
				echo htmlspecialcharsbx(
					htmlspecialcharsback($task['RESPONSIBLE_LAST_NAME'])
					.' '
					.mb_substr(htmlspecialcharsback($task['RESPONSIBLE_NAME']), 0, 1)
					.'.'
				);
			}
			else
			{
				echo htmlspecialcharsbx($task['RESPONSIBLE_LOGIN']);
			}
		}
		else
		{
			echo CUser::FormatName(
				$nameFormat,
				array(
					"NAME" => $task["RESPONSIBLE_NAME"],
					"LAST_NAME" => $task["RESPONSIBLE_LAST_NAME"],
					"SECOND_NAME" => $task["RESPONSIBLE_SECOND_NAME"],
					"LOGIN" => $task["RESPONSIBLE_LOGIN"]
					),
				true,
				false
			);
		}
	?></a></td>
	<td class="task-director-column"><a class="task-director-link" target="_top" href="<?php
		echo CComponentEngine::MakePathFromTemplate(
			$arPaths["PATH_TO_USER_PROFILE"],
			array("user_id" => $task["CREATED_BY"]))
			?>" id="anchor_created_<?php echo $anchor_id?>" bx-tooltip-user-id="<?=$task["CREATED_BY"]?>"><?php

		// Force special format for russian version of site.
		if (LANGUAGE_ID === 'ru')
		{
			if(trim($task['CREATED_BY_NAME']).trim($task['CREATED_BY_LAST_NAME']) <> '')
			{
				echo htmlspecialcharsbx(
					htmlspecialcharsback($task['CREATED_BY_LAST_NAME'])
					.' '
					.mb_substr(htmlspecialcharsback($task['CREATED_BY_NAME']), 0, 1)
					.'.'
				);
			}
			else
			{
				echo htmlspecialcharsbx($task['CREATED_BY_LOGIN']);
			}
		}
		else
		{
			echo CUser::FormatName(
				$nameFormat,
				array(
					"NAME" => $task["CREATED_BY_NAME"],
					"LAST_NAME" => $task["CREATED_BY_LAST_NAME"],
					"SECOND_NAME" => $task["CREATED_BY_SECOND_NAME"],
					"LOGIN" => $task["CREATED_BY_LOGIN"]
					),
				true,
				false
			);
		}
	?></a></td>
	<td class="task-grade-column"><?php
		if ($arResult['ALLOWED_ACTIONS']['ACTION_EDIT'])
		{
			?><a href="javascript: void(0)"
				class="task-grade-and-report<?php if ($task["MARK"] == "N" || $task["MARK"] == "P"):?> task-grade-<?php echo ($task["MARK"] == "N" ? "minus" : "plus")?><?php endif?><?php if ($task["ADD_IN_REPORT"] == "Y"):?> task-in-report<?php endif?>" onclick="return ShowGradePopup(<?php echo $task["ID"]?>, this, {listValue : '<?php echo ($task["MARK"] == "N" || $task["MARK"] == "P" ? $task["MARK"] : "NULL")?>' });" title="<?php echo GetMessage("TASKS_MARK")?>: <?php echo GetMessage("TASKS_MARK_".($task["MARK"] == "N" || $task["MARK"] == "P" ? $task["MARK"] : "NONE"))?>"><span class="task-grade-and-report-inner"><i class="task-grade-and-report-icon"></i></span></a><?php
		}
		else
		{
			?><span href="javascript: void(0)"
				class="<?php if ($task["MARK"] == "N" || $task["MARK"] == "P"):?> task-grade-<?php echo ($task["MARK"] == "N" ? "minus" : "plus")?><?php endif?><?php if ($task["ADD_IN_REPORT"] == "Y"):?> task-in-report<?php endif?>" title="<?php echo GetMessage("TASKS_MARK")?>: <?php echo GetMessage("TASKS_MARK_".($task["MARK"] == "N" || $task["MARK"] == "P" ? $task["MARK"] : "NONE"))?>"><span class="task-grade-and-report-inner task-grade-and-report-default-cursor"><i class="task-grade-and-report-icon task-grade-and-report-default-cursor"></i></span></span><?php
		}
		?></td>
	<td class="task-complete-column"><?php
		if (
			$arResult['ALLOWED_ACTIONS']['ACTION_COMPLETE']
			|| $arResult['ALLOWED_ACTIONS']['ACTION_APPROVE']
			|| ((int)$task["REAL_STATUS"] === Status::COMPLETED)
		)
		{
			?><a class="task-complete-action" href="javascript: void(0)"
				<?php
				if ((int)$task["REAL_STATUS"] !== Status::COMPLETED)
				{
					if ((int)$task["REAL_STATUS"] === Status::SUPPOSEDLY_COMPLETED)
					{
						?>
						title="<?php echo GetMessage('TASKS_APPROVE_TASK'); ?>"
						onClick="tasksListNS.approveTask(<?php echo $task['ID']; ?>)"
						<?php
					}
					else
					{
						if ($arResult['ALLOWED_ACTIONS']['ACTION_COMPLETE'])
						{
							?>
							title="<?php echo GetMessage("TASKS_FINISH"); ?>"
							onClick="CloseTask(<?php echo $task["ID"]?>)"
							<?php
						}
					}
				}
				else
				{
					?>
					title="<?php echo GetMessage("TASKS_FINISHED"); ?>"
					<?php
				}
				?>
			></a><?php
		}
		else
		{
			?>&nbsp;<?php
		}
		?></td>
</tr>
<script<?php echo $defer ? "  defer=\"defer\"" : ""?>>
	tasksMenuPopup[<?php echo $task["ID"]?>] = [<?php tasksGetItemMenu($task, $arPaths, $site_id)?>];
	quickInfoData[<?php echo $task["ID"]?>] = <?php tasksRenderJSON($task, $childrenCount, $arPaths, false, false, false, $nameFormat)?>;
	<?php if($taskAdded):?>
		BX.onCustomEvent("onTaskListTaskAdd", [quickInfoData[<?php echo $task["ID"]?>]]);
	<?php endif?>
	<?php
	if ($arResult['SHOW_TIMER_NODE'] === 'Y')
	{
		?>
		tasksListNS.redrawTimerNode(
			<?php echo (int) $task['ID']; ?>,
			<?php echo (int) $task['TIME_SPENT_IN_LOGS']; ?>,
			<?php echo (int) $task['TIME_ESTIMATE']; ?>,
			<?php echo (($arResult['CURRENT_TASK_TIMER_RUN_FOR_USER'] === 'Y') ? 'true' : 'false'); ?>,
			<?php
				if ($arResult['TIMER'] && $arResult['TIMER']['RUN_TIME'] && ($arResult['CURRENT_TASK_TIMER_RUN_FOR_USER'] === 'Y'))
					echo (int) $arResult['TIMER']['RUN_TIME'];
				else
					echo 0;
			?>,
			<?php echo (($arResult['ALLOWED_ACTIONS']['ACTION_START_TIME_TRACKING']) ? 'true' : 'false'); ?>
		);
		<?php
	}
	?>
</script>