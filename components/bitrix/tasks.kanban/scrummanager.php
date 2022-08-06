<?php

namespace Bitrix\Tasks\Component\Kanban;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\KanbanService;

class ScrumManager
{
	private $groupId;

	private static $listScrumItems = [];

	public function __construct(int $groupId)
	{
		$this->groupId = (int) $groupId;
	}

	public function isScrumProject(): bool
	{
		$group = Workgroup::getById($this->groupId);

		return ($group && $group->isScrumProject());
	}

	public function getScrumTaskResponsible(int $defaultUserId): int
	{
		$responsibleId = $defaultUserId;

		$group = Workgroup::getById($this->groupId);
		if ($group)
		{
			$scrumTaskResponsible = $group->getScrumTaskResponsible();
			$responsibleId = ($scrumTaskResponsible == 'A' ? $defaultUserId : $group->getScrumMaster());
		}

		return $responsibleId;
	}

	/**
	 * The method returns a map that can be used to determine if the base task has subtasks in the current sprint.
	 *
	 * @param int $sprintId Sprint id.
	 * @param array $taskIds List task ids.
	 * @return array
	 * @throws \TasksException
	 */
	public function buildMapOfExistenceOfSubtasks(int $sprintId, array $taskIds): array
	{
		if (empty($taskIds))
		{
			return [];
		}

		$kanbanService = new KanbanService();

		$mapOfExistenceOfSubtasks = [];

		$queryObject = \CTasks::getList(
			['ID' => 'ASC'],
			[
				'GROUP_ID' => $this->groupId,
				'PARENT_ID' => $taskIds,
			],
			['ID', 'PARENT_ID']
		);
		while ($data = $queryObject->fetch())
		{
			if ($kanbanService->isTaskInKanban($sprintId, $data['ID']))
			{
				$mapOfExistenceOfSubtasks[$data['PARENT_ID']] = true;
			}
		}

		foreach ($taskIds as $taskId)
		{
			$mapOfExistenceOfSubtasks[$taskId] = array_key_exists($taskId, $mapOfExistenceOfSubtasks);
		}

		return $mapOfExistenceOfSubtasks;
	}

	public function groupBySubTasks(TaskRegistry $taskRegistry, array $items, array $columns): array
	{
		$parentTasks = [];

		[$updatedItems, $subTasksItems, $updatedColumns] = $this->extractSubTasksItems($items, $columns);
		[$updatedItems, $updatedColumns] = $this->extractParentTasksItems(
			$updatedItems,
			$subTasksItems,
			$updatedColumns
		);

		foreach ($columns as &$column)
		{
			$column['total'] = 0;
		}

		$itemService = new ItemService();

		foreach ($subTasksItems as $item)
		{
			if (!array_key_exists($item['parentId'], $parentTasks))
			{
				$parentTaskData = $taskRegistry->get($item['parentId']);
				if (!$parentTaskData)
				{
					continue;
				}
				$parentGroupId = (int) $parentTaskData['GROUP_ID'];
				$subTaskGroupId = (int) $item['data']['groupId'];
				if ($parentGroupId !== $subTaskGroupId)
				{
					$updatedItems[] = $item;

					continue;
				}

				if (!isset(self::$listScrumItems[$parentTaskData['ID']]))
				{
					self::$listScrumItems[$parentTaskData['ID']] = $itemService
						->getItemBySourceId($parentTaskData['ID'])
					;
				}

				$scrumItem = self::$listScrumItems[$parentTaskData['ID']];

				$parentTasks[$item['parentId']] = [
					'id' => $parentTaskData['ID'],
					'name' => $parentTaskData['TITLE'],
					'completed' => ($parentTaskData['STATUS'] == \CTasks::STATE_COMPLETED ? 'Y' : 'N'),
					'storyPoints' => $scrumItem->getStoryPoints(),
					'isVisibilitySubtasks' => $scrumItem->getInfo()->isVisibilitySubtasks() ? 'Y' : 'N',
				];
				$parentTasks[$item['parentId']]['columns'] = $columns;
				$parentTasks[$item['parentId']]['items'] = [];
			}

			$parentTasks[$item['parentId']]['columns'] = $this->addToColumnTotal(
				$parentTasks[$item['parentId']]['columns'],
				$item
			);
			$parentTasks[$item['parentId']]['items'][] = $item;
		}

		return [$updatedItems, $updatedColumns, $parentTasks];
	}

	private function extractSubTasksItems(array $inputItems, array $columns): array
	{
		$items = [];
		$subTasksItems = [];

		foreach ($inputItems as $key => $item)
		{
			if ((int)$item['parentId'])
			{
				$subTasksItems[] = $item;
				$columns = $this->subtractFromColumnTotal($columns, $item);
			}
			else
			{
				$items[] = $item;
			}
		}

		return [$items, $subTasksItems, $columns];
	}

	private function extractParentTasksItems(array $inputItems, array $subTasksItems, array $columns): array
	{
		$items = [];

		foreach ($inputItems as $key => $item)
		{
			if (array_search($item['id'], array_column($subTasksItems, 'parentId')) === false)
			{
				$items[] = $item;
			}
			else
			{
				$columns = $this->subtractFromColumnTotal($columns, $item);
			}
		}

		return [$items, $columns];
	}

	private function addToColumnTotal(array $columns, array $item): array
	{
		foreach ($columns as &$column)
		{
			if ($column['id'] === $item['columnId'])
			{
				$column['total'] = ((int)$column['total'] + 1);
			}
		}

		return $columns;
	}

	private function subtractFromColumnTotal(array $columns, array $item): array
	{
		foreach ($columns as &$column)
		{
			if ($column['id'] === $item['columnId'])
			{
				$column['total'] = ((int)$column['total'] - 1);
			}
		}

		return $columns;
	}
}