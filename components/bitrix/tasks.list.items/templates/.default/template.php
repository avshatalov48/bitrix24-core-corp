<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\UI;

UI\Extension::load("ui.tooltip");

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");

$bShowInPopup = false;
if (COption::GetOptionString('tasks', 'use_task_view_popup_in_list') === 'Y')
{
	if (CTasksTools::IsIphoneOrIpad())
		$bShowInPopup = false;
	elseif($arParams['OPEN_TASK_IN_POPUP'] !== false)
		$bShowInPopup = true;
}

$ufCrmUserTypeId = null;
$ufCrmUserType = null;

foreach ($arResult['ITEMS'] as &$arItem)
{
	$task            = &$arItem["TASK"];
	$childrenCount   = $arItem["CHILDREN_COUNT"];
	$depth           = $arItem['DEPTH'];
	$updatesCount    = $arItem['UPDATES_COUNT'];
	$projectExpanded = $arItem['PROJECT_EXPANDED'];

	$anchor_id = RandString(8);
	$viewUrl = CComponentEngine::MakePathFromTemplate($arParams["PATHS"]["PATH_TO_TASKS_TASK"], array("task_id" => $task["ID"], "action" => "view"));
	$editUrl = CComponentEngine::MakePathFromTemplate($arParams["PATHS"]["PATH_TO_TASKS_TASK"], array("task_id" => $task["ID"], "action" => "edit"));
	$createUrl = CComponentEngine::MakePathFromTemplate($arParams["PATHS"]["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"));
	$createUrl = $createUrl.(mb_strpos($createUrl, "?") === false ? "?" : "&")."PARENT_ID=".$task["ID"];

	if ($arResult['IFRAME'] === 'Y')
		$viewUrl = $viewUrl.(mb_strpos($viewUrl, "?") === false ? "?" : "&")."IFRAME=Y";

	?>
	<tr class="task-list-item task-depth-<?php echo $depth?> task-status-<?php echo tasksStatus2String($task["STATUS"])?>"
		id="task-<?php echo $task["ID"]?>"
		data-project-id="<?=intval($task["GROUP_ID"])?>"
		oncontextmenu="return ShowMenuPopupContext(<?php echo $task["ID"]?>, event);"
		ondblclick="window.location = '<?php echo CUtil::JSEscape($viewUrl); ?>'; return (false);"
		title="<?php echo GetMessage("TASKS_DOUBLE_CLICK")?>"<?php if (!$projectExpanded):?> style="display: none;"<?php endif?>
	>

		<?if(in_array(CTaskColumnList::SYS_COLUMN_CHECKBOX, $arResult['SYSTEM_COLUMN_IDS'])):?>
			<td ondblclick="if (window.event) BX.PreventDefault(window.event); return (false);">
				<input id="task_list_group_action_ID_<?php echo $task['ID']; ?>" name="task_list_group_action_ID" value="<?php echo $task['ID']; ?>" class="task-list-inp" type="checkbox" />
			</td>
		<?endif?>
	<?php

	foreach ($arResult['COLUMNS_IDS'] as $columnId)
	{
		?><td><?php
		switch($columnId)
		{
			case CTaskColumnList::COLUMN_ID:
				echo $task['ID'];
			break;

			case CTaskColumnList::COLUMN_GROUP_ID:
				if (($task['GROUP_ID'] > 0) && isset($task['GROUP_NAME']))
				{
					if (isset($arParams['PATH_TO_GROUP']))
					{
						echo '<a href="'
							. CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_GROUP'],
								array('group_id' => $task['GROUP_ID'])
							)
							. '">' . $task['GROUP_NAME'] . '</a>';
					}
					else
						echo $task['GROUP_NAME'];
				}
			break;

			case CTaskColumnList::COLUMN_TITLE:
				?>
				<div class="task-title-container">
					<?php if ($childrenCount > 0 && !$arParams["PLAIN"]):?>
						<div class="task-title-folding" onclick="ToggleSubtasks(this.parentNode.parentNode.parentNode, <?php echo $depth?>, <?php echo $task["ID"]?>)"><span id="task-children-count-<?php echo (int) $task['ID']; ?>"><?php echo $childrenCount; ?></span></div>
					<?php endif?>
					<div id="task-title-div-<?php echo $task["ID"]?>" class="task-title-info<?php if (isset($task["COMMENTS_COUNT"]) || isset($updatesCount) || isset($task["FILES"]) && sizeof($task["FILES"])):?> task-indicators<?php if ($updatesCount):?>-updates<?php endif?><?php if(sizeof($task["FILES"])):?>-files<?php endif?><?php if (isset($task["COMMENTS_COUNT"])):?>-comments<?php endif?><?php endif?>">
						<?php
						if ($task["MULTITASK"] == "Y")
						{
							?><span class="task-title-multiple"
									title="<?php echo GetMessage("TASKS_MULTITASK"); ?>"
							></span><?php
						}

						?><a href="<?php echo $viewUrl; ?>"
							class="task-title-link"

							<?if(($arParams['SHOW_QUICK_INFORMERS'] ?? null) !== false):?>
								onmouseover="ShowTaskQuickInfo(<?php echo $task["ID"]?>, event);"
								onmouseout="HideTaskQuickInfo(<?php echo $task["ID"]?>, event);"
							<?endif?>

							<?php
							if ($bShowInPopup)
							{
								?>onclick="ShowPopupTask(<?php echo $task["ID"]?>, event);"<?php
							}
							?>
						><?php echo $task["TITLE"]; ?></a><?php

						if ($updatesCount)
						{
							?><a href="<?php echo $viewUrl?>#updates"
								class="task-item-updates"
							<?php
						if ($bShowInPopup)
						{
							?>onclick="ShowPopupTask(<?php echo $task["ID"]?>, event);"<?php
						}
							?>
								title="<?php
								echo str_replace(
									"#NUM#",
									$updatesCount,
									GetMessage("TASKS_UPDATES_COUNT")
								);
								?>"
							><span class="task-item-updates-inner"><?php echo $updatesCount?></span></a><?php
						}

						if (isset($task["COMMENTS_COUNT"]) || isset($task["FILES"]) && sizeof($task["FILES"]))
						{
							?><span class="task-title-indicators"><?php
							if(sizeof($task["FILES"]) && $arParams['SHOW_QUICK_INFORMERS'] !== false)
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
								<?php
							if ($bShowInPopup)
							{
								?>onclick="ShowPopupTask(<?php echo $task["ID"]?>, event);"<?php
							}
								?>
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
				</div><?/*do not put CRLF here*/?><div class="task-title-right-block">
					<span id="task-title-btn-menu-<?php echo (int) $task['ID']; ?>" class="task-title-btn-wrap">
						<a href="javascript: void(0)" class="task-menu-button" onclick="return ShowMenuPopup(<?php echo $task["ID"]?>, this);" title="<?php echo GetMessage("TASKS_MENU")?>"><i class="task-menu-button-icon"></i></a>
					</span><span id="task-title-btn-start-<?php echo (int) $task['ID']; ?>" class="task-title-btn-wrap">
						<?php
						if ($arItem['ALLOWED_ACTIONS']['ACTION_APPROVE'])
						{
							?><span class="task-flag-confirm-required" title="<?php echo GetMessage("TASKS_CONFIRM_REQUIRED")?>"></span><?php
						}
						elseif (
							(
								($task["REAL_STATUS"] == CTasks::STATE_NEW)
								&& ($task["CREATED_BY"] == $USER->GetID())
							)
							|| (
								($task["REAL_STATUS"] == CTasks::STATE_SUPPOSEDLY_COMPLETED)
								&& ($task["RESPONSIBLE_ID"] == $USER->GetID())
							)
						)
						{
							?><span class="task-flag-waiting-confirm" title="<?php echo GetMessage("TASKS_WAINTING_CONFIRM")?>"></span><?php
						}
						elseif ($arItem['ALLOWED_ACTIONS']['ACTION_START'])
						{
							?><a href="javascript: void(0)" class="task-flag-begin-perform" onClick="StartTask(<?php echo $task["ID"]?>)" title="<?php echo GetMessage("TASKS_START")?>"></a><?php
						}
						elseif ($task["REAL_STATUS"] == CTasks::STATE_IN_PROGRESS)
						{
							?><span class="task-flag-in-progress" title="<?php echo GetMessage("TASKS_IN_PROGRESS")?>"></span><?php
						}
						else
						{
							?>&nbsp;<?php
						}
						?>
					</span><span id="task-title-btn-priority-<?php echo (int) $task['ID']; ?>" class="task-title-btn-wrap">
						<?php
						if ($arItem['ALLOWED_ACTIONS']['ACTION_EDIT'])
						{
							?>
							<a href="javascript: void(0)" class="task-priority-box" onclick="return ShowPriorityPopup(<?php echo $task["ID"]?>, this, <?php echo $task["PRIORITY"]?>);" title="<?php echo GetMessage("TASKS_PRIORITY_V2")?>: <?=GetMessage($task["PRIORITY"] == CTasks::PRIORITY_HIGH ? 'TASKS_COMMON_YES' : 'TASKS_COMMON_NO')?>"><i class="task-priority-icon task-priority-<?=($task["PRIORITY"] == CTasks::PRIORITY_HIGH ? 'high' : 'low')?>"></i></a>
							<?php
						}
						else
						{
							?>
							<i class="task-priority-icon task-priority-<?=($task["PRIORITY"] == CTasks::PRIORITY_HIGH ? 'high' : 'low')?>" title="<?php echo GetMessage("TASKS_PRIORITY_V2")?>: <?=GetMessage($task["PRIORITY"] == CTasks::PRIORITY_HIGH ? 'TASKS_COMMON_YES' : 'TASKS_COMMON_NO')?>"></i>
							<?php
						}
						?>
					</span>
				</div>
				<?php
			break;

			case CTaskColumnList::COLUMN_DEADLINE:
				if (
					$arItem['ALLOWED_ACTIONS']['ACTION_EDIT']
					|| $arItem['ALLOWED_ACTIONS']['ACTION_CHANGE_DEADLINE']
				)
				{
					if ($task["DEADLINE"])
					{
						?>
						<span class="task-deadline-datetime">
							<span class="task-deadline-date webform-field-action-link"
								onclick="tasksListNS.onDeadlineChangeClick(<?php echo $task['ID']; ?>, this, '<?php echo $task["DEADLINE"]; ?>');"
								>
							<?php echo tasksFormatDate($task["DEADLINE"]); ?>
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
							<?php echo tasksFormatDate($task["DEADLINE"])?>
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
			break;

			case CTaskColumnList::COLUMN_ORIGINATOR:
				?><a class="task-director-link" target="_top" href="<?php
					echo CComponentEngine::MakePathFromTemplate(
						$arParams["PATHS"]["PATH_TO_USER_PROFILE"],
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
							$arParams["NAME_TEMPLATE"],
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
				?></a><?php
			break;

			case CTaskColumnList::COLUMN_RESPONSIBLE:
				?>
				<?if(intval($task["RESPONSIBLE_ID"])):?>

					<a class="task-responsible-link" target="_top"
						href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATHS"]["PATH_TO_USER_PROFILE"], array("user_id" => $task["RESPONSIBLE_ID"]))?>"
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
								$arParams["NAME_TEMPLATE"],
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
					?></a>

				<?else:?>
					[<?=GetMessage('TASKS_LIST_NOT_SET')?>]
				<?endif?>

				<?
			break;

			case CTaskColumnList::COLUMN_GRADE:
				if ($arItem['ALLOWED_ACTIONS']['ACTION_EDIT'])
				{
					?><a href="javascript: void(0)"
						class="task-grade-and-report<?php if ($task["MARK"] == "N" || $task["MARK"] == "P"):?> task-grade-<?php echo ($task["MARK"] == "N" ? "minus" : "plus")?><?php endif?><?php if ($task["ADD_IN_REPORT"] == "Y"):?> task-in-report<?php endif?>" onclick="return ShowGradePopup(<?php echo $task["ID"]?>, this, {listValue : '<?php echo ($task["MARK"] == "N" || $task["MARK"] == "P" ? $task["MARK"] : "NULL")?>' });" title="<?php echo GetMessage("TASKS_MARK")?>: <?php echo GetMessage("TASKS_MARK_".($task["MARK"] == "N" || $task["MARK"] == "P" ? $task["MARK"] : "NONE"))?>"><span class="task-grade-and-report-inner"><i class="task-grade-and-report-icon"></i></span></a><?php
				}
				else
				{
					?><span href="javascript: void(0)"
							class="<?php if ($task["MARK"] == "N" || $task["MARK"] == "P"):?> task-grade-<?php echo ($task["MARK"] == "N" ? "minus" : "plus")?><?php endif?><?php if ($task["ADD_IN_REPORT"] == "Y"):?> task-in-report<?php endif?>" title="<?php echo GetMessage("TASKS_MARK")?>: <?php echo GetMessage("TASKS_MARK_".($task["MARK"] == "N" || $task["MARK"] == "P" ? $task["MARK"] : "NONE"))?>"><span class="task-grade-and-report-inner task-grade-and-report-default-cursor"><i class="task-grade-and-report-icon task-grade-and-report-default-cursor"></i></span></span><?php
				}
			break;

			case CTaskColumnList::COLUMN_CREATED_DATE:
				if ($task['CREATED_DATE'])
				{
					?>
					<span class="task-deadline-datetime">
						<span class="task-deadline-date">
						<?php echo tasksFormatDate($task['CREATED_DATE'])?>
						</span>
					</span>

					<?php
					if(convertTimeToMilitary($task['CREATED_DATE'], CSite::GetDateFormat(), 'HH:MI') != '00:00')
					{
						?><span class="task-deadline-time"><?php
						echo convertTimeToMilitary($task['CREATED_DATE'], CSite::GetDateFormat(), CSite::GetTimeFormat());
						?></span><?php
					}
				}
				else
					echo '&nbsp;';
			break;

			case CTaskColumnList::COLUMN_CHANGED_DATE:
				if ($task['CHANGED_DATE'])
				{
					?>
					<span class="task-deadline-datetime">
						<span class="task-deadline-date">
						<?php echo tasksFormatDate($task['CHANGED_DATE'])?>
						</span>
					</span>

					<?php
					if(convertTimeToMilitary($task['CHANGED_DATE'], CSite::GetDateFormat(), 'HH:MI') != '00:00')
					{
						?><span class="task-deadline-time"><?php
						echo convertTimeToMilitary($task['CHANGED_DATE'], CSite::GetDateFormat(), CSite::GetTimeFormat());
						?></span><?php
					}
				}
				else
					echo '&nbsp;';
			break;

			case CTaskColumnList::COLUMN_CLOSED_DATE:
				if ($task['CLOSED_DATE'])
				{
					?>
					<span class="task-deadline-datetime">
						<span class="task-deadline-date">
						<?php echo tasksFormatDate($task['CLOSED_DATE'])?>
						</span>
					</span>

					<?php
					if(convertTimeToMilitary($task['CLOSED_DATE'], CSite::GetDateFormat(), 'HH:MI') != '00:00')
					{
						?><span class="task-deadline-time"><?php
						echo convertTimeToMilitary($task['CLOSED_DATE'], CSite::GetDateFormat(), CSite::GetTimeFormat());
						?></span><?php
					}
				}
				else
					echo '&nbsp;';
			break;

			case CTaskColumnList::COLUMN_ALLOW_TIME_TRACKING:
				if ($task['ALLOW_TIME_TRACKING'] === 'Y')
					echo GetMessage('TASKS_LIST_ITEMS_YES');
				else
					echo GetMessage('TASKS_LIST_ITEMS_NO');
			break;

			case CTaskColumnList::COLUMN_ALLOW_CHANGE_DEADLINE:
				if ($task['ALLOW_CHANGE_DEADLINE'] === 'Y')
					echo GetMessage('TASKS_LIST_ITEMS_YES');
				else
					echo GetMessage('TASKS_LIST_ITEMS_NO');
			break;

			case CTaskColumnList::COLUMN_TIME_ESTIMATE:
				echo \Bitrix\Tasks\UI::formatTimeAmount($task['TIME_ESTIMATE']);
			break;

			case CTaskColumnList::COLUMN_TIME_SPENT_IN_LOGS:
				echo \Bitrix\Tasks\UI::formatTimeAmount($task['TIME_SPENT_IN_LOGS']);
			break;

			case CTaskColumnList::COLUMN_STATUS:
				echo GetMessage('TASKS_LIST_ITEMS_STATUS_' . $task['REAL_STATUS']);
			break;

			case CTaskColumnList::COLUMN_PRIORITY:
				echo GetMessage('TASKS_LIST_ITEMS_PRIORITY_' . $task['PRIORITY']);
			break;

			case CTaskColumnList::COLUMN_UF_CRM:

				$crmFieldName = \Bitrix\Tasks\Integration\CRM\UserField::getMainSysUFCode();

				if (empty($task[$crmFieldName]))
					break;

				$collection = array();
				sort($task['UF_CRM_TASK']);
				foreach ($task[$crmFieldName] as $value)
				{
					$crmElement = explode('_', $value);
					$type       = $crmElement[0];
					$typeId     = CCrmOwnerType::ResolveID(CCrmOwnerTypeAbbr::ResolveName($type));
					$title      = CCrmOwnerType::GetCaption($typeId, $crmElement[1]);
					$url        = CCrmOwnerType::GetShowUrl($typeId, $crmElement[1]);

					if ( ! isset($collection[$type]) )
						$collection[$type] = array();

					$collection[$type][] = '<a href="' . $url . '">' . htmlspecialcharsbx($title) . '</a>';
				}

				if ($collection)
				{
					echo '<div class="tasks-list-crm-div">';
					$prevType = null;
					foreach ($collection as $type => $items)
					{
						if ($type !== $prevType)
						{
							if ($prevType !== null)
								echo ' &nbsp;&nbsp; ';

							echo '<span class="tasks-list-crm-div-type">' . GetMessage('TASKS_LIST_CRM_TYPE_' . $type) . ':</span>';
						}

						$prevType = $type;

						echo implode(', ', $items);
					}
					echo '</div>';
				}
			break;

			case CTaskColumnList::SYS_COLUMN_EMPTY:
				echo '&nbsp;';
			break;

			default:
				echo 'unknown column';
			break;
		}
		?></td><?php
	}

	?>

		<?if(in_array(CTaskColumnList::SYS_COLUMN_COMPLETE, $arResult['SYSTEM_COLUMN_IDS'])):?>
			<td>
			<?php
			if (
				$arItem['ALLOWED_ACTIONS']['ACTION_COMPLETE']
				|| $arItem['ALLOWED_ACTIONS']['ACTION_APPROVE']
				|| ($task["REAL_STATUS"] == CTasks::STATE_COMPLETED)
			)
			{
				?><a class="task-complete-action" href="javascript: void(0)"
					<?php
					if ($task["REAL_STATUS"] != CTasks::STATE_COMPLETED)
					{
						if ($task["REAL_STATUS"] == CTasks::STATE_SUPPOSEDLY_COMPLETED)
						{
							?>
							title="<?php echo GetMessage('TASKS_APPROVE_TASK'); ?>"
							onClick="tasksListNS.approveTask(<?php echo $task['ID']; ?>)"
							<?php
						}
						else
						{
							if ($arItem['ALLOWED_ACTIONS']['ACTION_COMPLETE'])
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
			?>
			</td>
		<?endif?>
	</tr>

	<script type="text/javascript"<?php echo $arParams["DEFER"] ? "  defer=\"defer\"" : ""?>>
		tasksMenuPopup[<?php echo $task["ID"]?>] = [<?
			if ((string)($arParams['CUSTOM_ACTIONS_CALLBACK'] ?? null) != '' && is_callable($arParams['CUSTOM_ACTIONS_CALLBACK']))
			{
				call_user_func_array($arParams['CUSTOM_ACTIONS_CALLBACK'], array($task, $arParams["PATHS"]));
			}
			else
			{
				tasksGetItemMenu($task, $arParams["PATHS"], $arParams["SITE_ID"], false, false, false, array('VIEW_STATE' => $arParams['VIEW_STATE']));
			}
		?>];

		<?if (($arParams['SHOW_QUICK_INFORMERS'] ?? null) !== false):?>
			quickInfoData[<?php echo $task["ID"]?>] = <?=tasksRenderJSON($task, $childrenCount, $arParams["PATHS"], false, false, false, $arParams["NAME_TEMPLATE"], array(), false, array('VIEW_STATE' => $arParams['VIEW_STATE']))?>;
		<?endif?>
		<?php if($arParams["TASK_ADDED"]):?>
			BX.onCustomEvent("onTaskListTaskAdd", [quickInfoData[<?php echo $task["ID"]?>]]);
		<?php endif?>

		BX.bind(
			BX('task_list_group_action_ID_<?php echo $task['ID']; ?>'),
			'click',
			function(e){
				var ev;

				if (!e)
					ev = window.event;
				else
					ev = e;

				tasksListNS.onCheckboxClick(ev, this);
			}
		);

		<?php
		if (($arItem['SHOW_TIMER_NODE'] ?? null) === 'Y')
		{
			?>
			tasksListNS.redrawTimerNode(
				<?php echo (int) $task['ID']; ?>,
				<?php echo (int) $task['TIME_SPENT_IN_LOGS']; ?>,
				<?php echo (int) $task['TIME_ESTIMATE']; ?>,
				<?php echo (($arItem['CURRENT_TASK_TIMER_RUN_FOR_USER'] === 'Y') ? 'true' : 'false'); ?>,
				<?php
					if ($arResult['TIMER'] && $arResult['TIMER']['RUN_TIME'] && ($arItem['CURRENT_TASK_TIMER_RUN_FOR_USER'] === 'Y'))
						echo (int) $arResult['TIMER']['RUN_TIME'];
					else
						echo 0;
				?>,
				<?php echo (($arItem['ALLOWED_ACTIONS']['ACTION_START_TIME_TRACKING']) ? 'true' : 'false'); ?>
			);
			<?php
		}
		?>
	</script>
	<?php

	unset($task);
}
unset($arItem);