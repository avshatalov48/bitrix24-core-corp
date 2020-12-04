<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Tasks\Grid\Row\Content;
use Bitrix\Tasks\Util\User;
use CComponentEngine;
use CTasks;

/**
 * Class Title
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class Title extends Content
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 */
	public static function prepare(array $row, array $parameters): string
	{
		$userId = User::getId();
		$taskId = (int)$row['ID'];
		$taskStatus = (int)$row['REAL_STATUS'];
		$taskPriority = (int)$row['PRIORITY'];
		$groupId = (int)$parameters['GROUP_ID'];
		$canUsePin = $parameters['CAN_USE_PIN'];

		$taskUrlTemplate = (
			$groupId > 0 ? $parameters['PATH_TO_GROUP_TASKS_TASK'] : $parameters['PATH_TO_USER_TASKS_TASK']
		);
		$taskUrl = CComponentEngine::MakePathFromTemplate($taskUrlTemplate, [
			'user_id' => $userId,
			'task_id' => $taskId,
			'group_id' => $groupId,
			'action' => 'view',
		]);

		$pinnedIcon = ($canUsePin && $row['IS_PINNED'] === 'Y' ? "<span class='task-title-pin'></span>" : "");
		$mutedIcon = ($row['IS_MUTED'] === 'Y' ? "<span class='task-title-mute'></span>" : "");
		$prefixIcons = $pinnedIcon.$mutedIcon;

		$priorityLayout = ($taskPriority === CTasks::PRIORITY_HIGH ? '<span class="task-priority-high"></span> ' : '');

		$countFiles = (int) $row['COUNT_FILES'];
		$checkListComplete = (int) $row['CHECK_LIST']['COMPLETE'];
		$checkListWork = (int) $row['CHECK_LIST']['WORK'];
		$checkListAll = ($checkListComplete + $checkListWork);

		$filesIcon = "<div class='task-attachment-counter ui-label ui-label-sm ui-label-light'><span class='ui-label-inner'>{$countFiles}</span></div>";
		$checkListIcon = "<div class='task-checklist-counter ui-label ui-label-sm ui-label-light'><span class='ui-label-inner'>{$checkListComplete}/{$checkListAll}</span></div>";

		$postfixIcons = "<span class='task-title-indicators'>{$priorityLayout}"
			.($countFiles > 0 ? $filesIcon : '')
			.($checkListAll > 0 ? $checkListIcon : '')
			."</span>";
		$timeTracker = "<span class='task-timer' id='task-timer-block-container-{$taskId}'></span>";

		$statuses = [CTasks::STATE_COMPLETED, CTasks::STATE_DEFERRED];
		$cssClass = 'task-status-text-color-'.(
			in_array($taskStatus, $statuses, true) ? tasksStatus2String($taskStatus) : 'in-progress'
			);
		$taskTitle = htmlspecialcharsbx($row['TITLE']);

		$title = "{$prefixIcons}<a href='{$taskUrl}' class='task-title {$cssClass}'>{$taskTitle}{$postfixIcons}</a>";
		$title .= $timeTracker . static::prepareTimeTracking($row, $parameters);

		if (isset($row['NAV_CHAIN']) && !empty($row['NAV_CHAIN']))
		{
			$title .= '<div>';
			foreach ($row['NAV_CHAIN'] as $subTask)
			{
				$subTaskUrl = CComponentEngine::MakePathFromTemplate($taskUrlTemplate, [
					'user_id' => $userId,
					'task_id' => $subTask['ID'],
					'group_id' => $groupId,
					'action' => 'view',
				]);

				$subTaskTitle = htmlspecialcharsbx($subTask['TITLE']);
				$title .= "&nbsp;&nbsp;&larr;&nbsp;&nbsp;<a href='{$subTaskUrl}'>{$subTaskTitle}</a>";
			}
			$title .= '</div>';
		}

		return $title;
	}

	/**
	 * @param array $row
	 * @param array $parameters
	 * @return false|string
	 */
	private static function prepareTimeTracking(array $row, array $parameters)
	{
		$taskId = (int)$row['ID'];
		$timeSpentInLogs = (int)$row['TIME_SPENT_IN_LOGS'];
		$timeEstimate = (int)$row['TIME_ESTIMATE'];
		$allowTimeTracking = $row['ALLOW_TIME_TRACKING'] === 'Y';

		$timer = (is_array($parameters['TIMER']) ? $parameters['TIMER'] : (bool)$parameters['TIMER']);
		$currentTaskTimerRunForUser = (
			$timer !== false
			&& isset($timer['TASK_ID'])
			&& (int)$timer['TASK_ID'] === (int)$row['ID']
		);
		$taskTimersTotalValue = ($currentTaskTimerRunForUser && $timer['RUN_TIME'] ? (int)$timer['RUN_TIME'] : 0);

		$canStartTask = (bool)$row['ACTION']['DAYPLAN.TIMER.TOGGLE'];

		ob_start();
		if ($allowTimeTracking && $canStartTask)
		{
			?>
			<script>
				BX.Tasks.GridActions.redrawTimerNode(
					<?=$taskId?>,
					<?=$timeSpentInLogs?>,
					<?=$timeEstimate?>,
					'<?=$currentTaskTimerRunForUser?>',
					<?=$taskTimersTotalValue?>,
					<?=(int)$canStartTask?>
				);
			</script>
			<?php
		}

		return ob_get_clean();
	}
}