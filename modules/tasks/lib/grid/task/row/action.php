<?php

namespace Bitrix\Tasks\Grid\Task\Row;

use Bitrix\Main;
use Bitrix\Tasks\Helper\Analytics;
use Bitrix\Tasks\Slider\Path\TaskPathMaker;
use Bitrix\Tasks\Util\User;
use CExtranet;
use CTaskPlannerMaintance;

/**
 * Class Action
 *
 * @package Bitrix\Tasks\Grid\Task\Row
 */
class Action
{
	protected $rowData = [];
	protected $parameters = [];

	public function __construct(array $rowData = [], array $parameters = [])
	{
		$this->rowData = $rowData;
		$this->parameters = $parameters;
	}

	/**
	 * @return array|array[]
	 * @throws Main\LoaderException
	 */
	public function prepare(): array
	{
		$userId = User::getId();
		$taskId = (int)$this->rowData['ID'];
		$groupId = (int)$this->parameters['GROUP_ID'];
		$actions = $this->rowData['ACTION'];

		$pinAction = (($this->rowData['IS_PINNED'] ?? '') === 'Y' ? 'UNPIN' : 'PIN');
		$muteAction = (($this->rowData['IS_MUTED'] ?? '') === 'Y' ? 'UNMUTE' : 'MUTE');

		$taskViewPath = new Main\Web\Uri(
			TaskPathMaker::getPath([
				'user_id' => $userId,
				'task_id' => $taskId,
				'group_id' => $groupId,
				'action' => 'view',
			])
		);
		$taskViewPath->addParams([
			'ta_sec' => Analytics::SECTION['tasks'],
			'ta_sub' => Analytics::SUB_SECTION['list'],
			'ta_el' => Analytics::ELEMENT['context_menu'],
		]);

		$taskRowActions = [
			[
				'text' => GetMessageJS("TASKS_GRID_TASK_ROW_ACTION_{$muteAction}"),
				'onclick' => 'BX.Tasks.GridActions.action("'.strtolower($muteAction).'", '.$taskId.');',
			],
			[
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_PING'),
				'onclick' => "BX.Tasks.GridActions.action('ping', {$taskId});",
			],
			[
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_VIEW'),
				'href' => $taskViewPath->getUri(),
			],
		];
		if ($this->parameters['CAN_USE_PIN'])
		{
			array_splice($taskRowActions, 0, 0, [[
				'text' => GetMessageJS("TASKS_GRID_TASK_ROW_ACTION_{$pinAction}"),
				'onclick' => 'BX.Tasks.GridActions.action("'.strtolower($pinAction).'", '.$taskId.');',
			]]);
		}
		if ($actions['EDIT'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_EDIT'),
				'href' => TaskPathMaker::getPath([
					'user_id' => $userId,
					'task_id' => $taskId,
					'group_id' => $groupId,
					'action' => 'edit',
				]),
			];
		}

		if ($actions['CREATE'])
		{
			$subTaskPath = new Main\Web\Uri(
				TaskPathMaker::getPath([
					'user_id' => $userId,
					'task_id' => 0,
					'group_id' => $groupId,
					'action' => 'edit',
				])
			);
			$subTaskPath->addParams([
				'PARENT_ID' => $taskId,
				'viewType' => 'VIEW_MODE_LIST',
				'ta_sec' => Analytics::SECTION['tasks'],
				'ta_sub' => Analytics::SUB_SECTION['list'],
				'ta_el' => Analytics::ELEMENT['context_menu'],
			]);
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_ADD_SUB_TASK'),
				'href' => $subTaskPath->getUri(),
			];
		}

		if ($actions['ADD_FAVORITE'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_ADD_TO_FAVORITES'),
				'onclick' => 'BX.Tasks.GridActions.action("addFavorite", '.$taskId.');',
			];
		}
		if ($actions['DELETE_FAVORITE'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_REMOVE_FROM_FAVORITES'),
				'onclick' => 'BX.Tasks.GridActions.action("deleteFavorite", '.$taskId.');',
			];
		}
		if ($actions['COMPLETE'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_COMPLETE'),
				'onclick' => 'BX.Tasks.GridActions.action("complete", '.$taskId.');',
			];
		}
		if ($actions['RENEW'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_RENEW'),
				'onclick' => 'BX.Tasks.GridActions.action("renew", '.$taskId.');',
			];
		}
		if ($actions['APPROVE'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_APPROVE'),
				'onclick' => 'BX.Tasks.GridActions.action("approve", '.$taskId.');',
			];
		}
		if ($actions['TAKE'])
		{
			$allowTimeTracking = $this->rowData['ALLOW_TIME_TRACKING'] === 'Y';
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_TAKE'),
				'onclick' => 'BX.Tasks.GridActions.action("take", '.$taskId.', { allowTimeTracking: ' . ($allowTimeTracking ? 'true' : 'false') . ' });',
			];
		}
		else if ($actions['START'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_START'),
				'onclick' => 'BX.Tasks.GridActions.action("start", '.$taskId.');',
			];
		}
		if ($actions['PAUSE'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_PAUSE'),
				'onclick' => 'BX.Tasks.GridActions.action("pause", '.$taskId.');',
			];
		}
		if ($actions['DEFER'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_DEFER'),
				'onclick' => 'BX.Tasks.GridActions.action("defer", '.$taskId.');',
			];
		}

		$copyTaskPath = new Main\Web\Uri(
			TaskPathMaker::getPath([
				'user_id' => $userId,
				'task_id' => 0,
				'action' => 'edit',
				'group_id' => $groupId,
			])
		);
		$copyTaskPath->addParams([
			'COPY' => $taskId,
			'viewType' => 'VIEW_MODE_LIST',
			'ta_sec' => Analytics::SECTION['tasks'],
			'ta_sub' => Analytics::SUB_SECTION['list'],
			'ta_el' => Analytics::ELEMENT['context_menu'],
		]);

		if ($actions['CREATE'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_COPY'),
				'href' => $copyTaskPath->getUri(),
			];
		}

		$copyLink = tasksServerName() . TaskPathMaker::getPath([
			'user_id' => $userId,
			'task_id' => $taskId,
			'action' => 'view',
			'group_id' => $groupId,
		]);
		$taskRowActions[] = [
			'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_COPY_LINK'),
			'onclick' => 'BX.Tasks.GridActions.action("copyLink", '.$taskId.', {copyLink: "'.$copyLink.'"});',
		];

		if ($this->checkCanUpdatePlan() === 'Y')
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_ADD_TO_TIMEMAN'),
				'onclick' => 'BX.Tasks.GridActions.action("add2Timeman", '.$taskId.');',
			];
		}
		if ($actions['REMOVE'])
		{
			$taskRowActions[] = [
				'text' => GetMessageJS('TASKS_GRID_TASK_ROW_ACTION_REMOVE'),
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
	 * @return string
	 * @throws Main\LoaderException
	 */
	private function checkCanUpdatePlan(): string
	{
		static $tasksInPlan = null;

		$can = 'N';

		$userId = User::getId();
		$isResponsible = (int)$this->rowData['RESPONSIBLE_ID'] === $userId;
		$isAccomplice = isset($this->rowData['ACCOMPLICES']) && in_array($userId, $this->rowData['ACCOMPLICES'], true);
		$isIntranet = Main\Loader::includeModule('intranet');
		$isExtranet = Main\Loader::includeModule('extranet') && CExtranet::IsExtranetSite();

		if (($isResponsible || $isAccomplice) && $isIntranet && !$isExtranet)
		{
			$can = 'Y';

			if ($tasksInPlan === null)
			{
				$tasksInPlan = array_map('intval', CTaskPlannerMaintance::getCurrentTasksList());
			}

			if (is_array($tasksInPlan) && in_array((int)$this->rowData['ID'], $tasksInPlan, true))
			{
				$can = 'N';
			}
		}

		return $can;
	}
}