<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Internals\Task\Status;

if (!function_exists('renderMyTasksColumn'))
{
	function renderMyTasksColumn(array $data, array $arResult, bool $isActive): string
	{
		$myTasks = $data['numberOfTasks'];
		$disableClass = $isActive ? '' : '--disable';

		if ($myTasks > 0)
		{
			$text = Loc::getMessagePlural(
				'TASKS_FLOW_LIST_COLUMN_MY_TASKS',
				$myTasks,
				['{number}' => $myTasks]
			);

			$url = $data['url'];

			return <<<HTML
				<div class="tasks-flow__list-cell tasks-flow__list-my-tasks $disableClass">
					<span onclick="BX.SidePanel.Instance.open('$url')">$text</span>
				</div>
			HTML;
		}
		else
		{
			$data = Loc::getMessage('TASKS_FLOW_LIST_NO_YOUR_TASKS');

			return <<<HTML
				<div class="tasks-flow__list-cell $disableClass --right">$data</div>
			HTML;
		}

	}
}

if (!function_exists('prepareMyTasksColumnData'))
{
	function prepareMyTasksColumnData(array $data, array $arResult): array
	{
		$flowId = $data['flowId'];
		$flowName = $data['flowName'];
		$userId = $arResult['currentUserId'];

		$myTasksUri = new Uri(
			CComponentEngine::makePathFromTemplate(
				$arResult['pathToUserTasks'],
			)
		);

		$myTasksUri->addParams(['apply_filter' => 'Y']);
		$myTasksUri->addParams(['FLOW' => $flowId]);
		$myTasksUri->addParams(['CREATED_BY' => $userId]);
		$myTasksUri->addParams(['FLOW_label' => $flowName]);
		$myTasksUri->addParams(['show_counters_toolbar' => 'N']);
		$myTasksUri->addParams(['my_tasks_column' => 'Y']);
		$myTasksUri->addParams(
			[
				'STATUS' => [
					Status::PENDING,
					Status::IN_PROGRESS,
					Status::SUPPOSEDLY_COMPLETED,
					Status::DEFERRED,
				],
			],
		);

		return [
			'url' => $myTasksUri->getUri(),
		];
	}
}

if (!function_exists('prepareMyTasksColumnCounter'))
{
	function prepareMyTasksColumnCounter(array $data, array $arResult, bool $isActive): array
	{
		$disableClass = $isActive ? '' : ' --disable';

		return [
			'events' => [
				'click' => 'BX.SidePanel.Instance.open.bind(BX.SidePanel.Instance, \'' . $data['url'] . '\')',
			],
			'class' => 'tasks-flow__list-my-tasks-counter' . $disableClass,
		];
	}
}
