<?php
namespace Bitrix\Tasks\Grid\Row;

use Bitrix\Main;
use Bitrix\Tasks\Util\User;
use CComponentEngine;
use CExtranet;
use CTaskPlannerMaintance;

/**
 * Class Actions
 *
 * @package Bitrix\Tasks\Grid\Row
 */
class Action
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return array|array[]
	 * @throws Main\LoaderException
	 */
	public static function prepare(array $row, array $parameters): array
	{
		$userId = User::getId();
		$taskId = (int)$row['ID'];
		$groupId = (int)$parameters['GROUP_ID'];
		$actions = $row['ACTION'];

		$urlPath = ($groupId > 0 ? $parameters['PATH_TO_GROUP_TASKS_TASK'] : $parameters['PATH_TO_USER_TASKS_TASK']);
		$pinAction = ($row['IS_PINNED'] === 'Y' ? 'UNPIN' : 'PIN');
		$muteAction = ($row['IS_MUTED'] === 'Y' ? 'UNMUTE' : 'MUTE');

		$taskRowActions = [
			[
				'text' => GetMessageJS("TASKS_GRID_ROW_ACTION_{$muteAction}"),
				'onclick' => 'BX.Tasks.GridActions.action("'.strtolower($muteAction).'", '.$taskId.');',
			],
			[
				'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_VIEW'),
				'href' => CComponentEngine::MakePathFromTemplate($urlPath, [
					'user_id' => $userId,
					'task_id' => $taskId,
					'group_id' => $groupId,
					'action' => 'view',
				]),
			],
		];
		if ($parameters['CAN_USE_PIN'])
		{
			array_splice($taskRowActions, 0, 0, [[
				'text' => GetMessageJS("TASKS_GRID_ROW_ACTION_{$pinAction}"),
				'onclick' => 'BX.Tasks.GridActions.action("'.strtolower($pinAction).'", '.$taskId.');',
			]]);
		}
		if ($actions['EDIT'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_EDIT'),
				'href' => CComponentEngine::MakePathFromTemplate($urlPath, [
					'user_id' => $userId,
					'task_id' => $taskId,
					'group_id' => $groupId,
					'action' => 'edit',
				]),
			];
		}
		$taskRowActions[] = [
			'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_ADD_SUB_TASK'),
			'href' => CComponentEngine::MakePathFromTemplate($urlPath, [
					'user_id' => $userId,
					'task_id' => 0,
					'group_id' => $groupId,
					'action' => 'edit',
				]).'?PARENT_ID='.$taskId.'&viewType=VIEW_MODE_LIST',
		];
		if ($actions['ADD_FAVORITE'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_ADD_TO_FAVORITES'),
				'onclick' => 'BX.Tasks.GridActions.action("addToFavorite", '.$taskId.');',
			];
		}
		if ($actions['DELETE_FAVORITE'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_REMOVE_FROM_FAVORITES'),
				'onclick' => 'BX.Tasks.GridActions.action("removeFromFavorite", '.$taskId.');',
			];
		}
		if ($actions['COMPLETE'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_COMPLETE'),
				'onclick' => 'BX.Tasks.GridActions.action("complete", '.$taskId.');',
			];
		}
		if ($actions['RENEW'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_RENEW'),
				'onclick' => 'BX.Tasks.GridActions.action("renew", '.$taskId.');',
			];
		}
		if ($actions['ACCEPT'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_ACCEPT'),
				'onclick' => 'BX.Tasks.GridActions.action("accept", '.$taskId.');',
			];
		}
		if ($actions['APPROVE'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_APPROVE'),
				'onclick' => 'BX.Tasks.GridActions.action("approve", '.$taskId.');',
			];
		}
		if ($actions['START'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_START'),
				'onclick' => 'BX.Tasks.GridActions.action("start", '.$taskId.');',
			];
		}
		if ($actions['PAUSE'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_PAUSE'),
				'onclick' => 'BX.Tasks.GridActions.action("pause", '.$taskId.');',
			];
		}
		if ($actions['DEFER'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_DEFER'),
				'onclick' => 'BX.Tasks.GridActions.action("defer", '.$taskId.');',
			];
		}
		$taskRowActions[] = [
			'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_COPY'),
			'href' => CComponentEngine::MakePathFromTemplate($urlPath, [
					'user_id' => $userId,
					'task_id' => 0,
					'action' => 'edit',
					'group_id' => $groupId,
				]).'?COPY='.$taskId.'&viewType=VIEW_MODE_LIST',
		];

		if (static::checkCanUpdatePlan($row) === 'Y')
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_ADD_TO_TIMEMAN'),
				'onclick' => 'BX.Tasks.GridActions.action("add2Timeman", '.$taskId.');',
			];
		}
		if ($actions['REMOVE'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_ROW_ACTION_REMOVE'),
				'onclick' => 'BX.Tasks.GridActions.action("delete", '.$taskId.');',
			];
		}

		foreach (GetModuleEvents('tasks', 'onTasksBuildContextMenu', true) as $event)
		{
			ExecuteModuleEventEx($event, ['TASK_LIST_CONTEXT_MENU', ['ID' => $taskId], &$taskRowActions]);
		}

		return $taskRowActions;
	}

	/**
	 * @param array $row
	 * @return string
	 * @throws Main\LoaderException
	 */
	private static function checkCanUpdatePlan(array $row): string
	{
		static $tasksInPlan = null;

		$can = 'N';

		$userId = User::getId();
		$isResponsible = (int)$row['RESPONSIBLE_ID'] === $userId;
		$isAccomplice = $row['ACCOMPLICES'] && in_array($userId, $row['ACCOMPLICES'], true);
		$isIntranet = Main\Loader::includeModule('intranet');
		$isExtranet = Main\Loader::includeModule('extranet') && CExtranet::IsExtranetSite();

		if (($isResponsible || $isAccomplice) && $isIntranet && !$isExtranet)
		{
			$can = 'Y';

			if ($tasksInPlan === null)
			{
				$tasksInPlan = CTaskPlannerMaintance::getCurrentTasksList();
			}

			if (is_array($tasksInPlan) && in_array($row['ID'], $tasksInPlan, true))
			{
				$can = 'N';
			}
		}

		return $can;
	}
}