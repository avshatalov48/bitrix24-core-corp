<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Main\Web\Uri;
use Bitrix\Socialnetwork\Livefeed\Context\Context;
use Bitrix\Tasks\Grid\Task\Row\Content;
use Bitrix\Tasks\Helper\Analytics;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Slider\Path\TaskPathMaker;
use Bitrix\Tasks\Util\User;

/**
 * Class Title
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content
 */
class Title extends Content
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		$row = $this->getRowData();
		$parameters = $this->getParameters();

		$userId = User::getId();
		$taskId = (int)$row['ID'];
		$taskStatus = (int)$row['REAL_STATUS'];
		$taskPriority = (int)($row['PRIORITY'] ?? 0);
		$groupId = (int)($parameters['GROUP_ID'] ?? 0);

		$taskUrl = new Uri(
			TaskPathMaker::getPath([
				'user_id' => $userId,
				'task_id' => $taskId,
				'group_id' => $groupId,
				'action' => 'view',
			])
		);

		$context = $this->getParameter('CONTEXT');
		$isFlowMyTasksContext = ($parameters['FLOW_MY_TASKS'] ?? null) === 'Y';
		if ($isFlowMyTasksContext)
		{
			$taSec = Analytics::SECTION['flows'];
		}
		elseif ($context === Context::COLLAB)
		{
			$taSec = Analytics::SECTION['collab'];
		}
		else
		{
			$taSec = Analytics::SECTION['tasks'];
		}

		$taEl = (
			$isFlowMyTasksContext
				? Analytics::ELEMENT['my_tasks_column']
				: Analytics::ELEMENT['title_click']
		);

		$taskUrl->addParams([
			'ta_sec' => $taSec,
			'ta_sub' => Analytics::SUB_SECTION['list'],
			'ta_el' => $taEl,
		]);

		$demoSuffix = $arParams['demoSuffix'] ?? null;

		if ($isFlowMyTasksContext && $demoSuffix)
		{
			$taskUrl->addParams([
				'p1' => 'isDemo_' . $demoSuffix,
			]);
		}

		$priorityLayout = ($taskPriority === \Bitrix\Tasks\Internals\Task\Priority::HIGH ? '<span class="task-priority-high"></span> ' : '');

		$countFiles = (int) ($row['COUNT_FILES'] ?? 0);
		$checkListComplete = (int) ($row['CHECK_LIST']['COMPLETE'] ?? 0);
		$checkListWork = (int) ($row['CHECK_LIST']['WORK'] ?? 0);
		$checkListAll = ($checkListComplete + $checkListWork);

		$filesIcon = "<div class='task-attachment-counter ui-label ui-label-sm ui-label-light'><span class='ui-label-inner'>{$countFiles}</span></div>";
		$checkListIcon = "<div class='task-checklist-counter ui-label ui-label-sm ui-label-light'><span class='ui-label-inner'>{$checkListComplete}/{$checkListAll}</span></div>";

		$postfixIcons = "<span class='task-title-indicators'>{$priorityLayout}"
			.($countFiles > 0 ? $filesIcon : '')
			.($checkListAll > 0 ? $checkListIcon : '')
			."</span>";
		$timeTracker = "<span class='task-timer' id='task-timer-block-container-{$taskId}'></span>";

		$statuses = [Status::COMPLETED, Status::DEFERRED];
		$cssClass = 'task-status-text-color-'.(
			in_array($taskStatus, $statuses, true) ? tasksStatus2String($taskStatus) : 'in-progress'
			);
		$taskTitle = htmlspecialcharsbx($row['TITLE'] ?? '');
		$title = "<a href='{$taskUrl->getUri()}' class='task-title {$cssClass}'>{$taskTitle}{$postfixIcons}</a>";
		$title .= $timeTracker . $this->prepareTimeTracking();

		if (isset($row['NAV_CHAIN']) && !empty($row['NAV_CHAIN']))
		{
			$title .= '<div>';
			foreach ($row['NAV_CHAIN'] as $subTask)
			{
				$subTaskUrl = new Uri(
					TaskPathMaker::getPath([
						'user_id' => $userId,
						'task_id' => $subTask['ID'],
						'group_id' => $groupId,
						'action' => 'view',
					])
				);

				$subTaskUrl->addParams([
					'ta_sec' => $taSec,
					'ta_sub' => Analytics::SUB_SECTION['list'],
					'ta_el' => $taEl,
				]);
				if ($isFlowMyTasksContext && $demoSuffix)
				{
					$subTaskUrl->addParams([
						'p1' => 'isDemo_' . $demoSuffix,
					]);
				}

				$subTaskTitle = htmlspecialcharsbx($subTask['TITLE']);
				$title .= "&nbsp;&nbsp;&larr;&nbsp;&nbsp;<a href='{$subTaskUrl->getUri()}'>{$subTaskTitle}</a>";
			}
			$title .= '</div>';
		}

		return $title;
	}

	/**
	 * @return false|string
	 */
	private function prepareTimeTracking()
	{
		$row = $this->getRowData();
		$parameters = $this->getParameters();

		$taskId = (int)$row['ID'];
		$timeSpentInLogs = (int)($row['TIME_SPENT_IN_LOGS'] ?? 0);
		$timeEstimate = (int)($row['TIME_ESTIMATE'] ?? 0);
		$allowTimeTracking = ($row['ALLOW_TIME_TRACKING'] ?? '') === 'Y';

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