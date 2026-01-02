<?php

namespace Bitrix\TasksMobile\Provider;

use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Integration\Socialnetwork;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\SprintService;
use Bitrix\Tasks\Scrum\Form\ItemForm;
use Bitrix\Tasks\Scrum\Form\EntityForm;

final class ScrumProvider
{
	public function shouldShowKanbanStages(int $taskId, int $groupId): bool
	{
		$filteredTasks = $this->filterTasksWithKanbanStages([['ID' => $taskId, 'GROUP_ID' => $groupId]]);

		return !empty($filteredTasks);
	}

	/**
	 * @param array $tasks array of ['ID' => int, 'GROUP_ID' => int]
	 * @return array|int[]
	 */
	public function filterTasksWithKanbanStages(array $tasks): array
	{
		if (empty($tasks) || !Socialnetwork::includeModule())
		{
			return [];
		}

		$groupTasks = $this->extractTasksByGroup($tasks);
		if (empty($groupTasks))
		{
			return [];
		}

		$groupIds = array_unique(array_values($groupTasks));

		$projectsData = $this->getProjects($groupIds);
		if (empty($projectsData))
		{
			return [];
		}

		$scrumProjectIds = [];
		$projectIds = [];
		foreach ($projectsData as $project)
		{
			if (!empty($project['SCRUM_MASTER_ID']))
			{
				$scrumProjectIds[] = (int)$project['ID'];
			}
			else
			{
				$projectIds[] = (int)$project['ID'];
			}
		}

		$scrumTasksIds = $this->getTaskIdsInActiveSprint($groupTasks, $scrumProjectIds);

		$projectTaskIds = [];
		if (!empty($projectIds))
		{
			$projectTaskIds = $this->filterTaskIdsByProjectIds($groupTasks, $projectIds);
		}


		return array_merge($scrumTasksIds, $projectTaskIds);
	}

	/**
	 * @param int[] $groupTasks
	 * @param int[] $scrumProjectIds
	 * @return array|int[]
	 */
	private function getTaskIdsInActiveSprint(array $groupTasks, array $scrumProjectIds): array
	{
		if (empty($groupTasks) || empty($scrumProjectIds))
		{
			return [];
		}

		$taskIdsInScrumProjects = $this->filterTaskIdsByProjectIds($groupTasks, $scrumProjectIds);

		$scrumItems = (new ItemService())->getItemsBySourceIds($taskIdsInScrumProjects);

		return $this->filterTaskIdsInActiveSprints($scrumItems);
	}

	/**
	 * @param array $tasks array of ['ID' => int, 'GROUP_ID' => int]
	 * @return array
	 */
	private function extractTasksByGroup(array $tasks): array
	{
		$groupTasks = [];
		foreach ($tasks as $task)
		{
			if ($task['GROUP_ID'] > 0)
			{
				$groupTasks[$task['ID']] = $task['GROUP_ID'];
			}
		}
		return $groupTasks;
	}

	/**
	 * @param int[] $groupIds
	 * @return array
	 */
	private function getProjects(array $groupIds): array
	{
		$scrumProjects = \Bitrix\Socialnetwork\WorkgroupTable::getList([
			'filter' => array(
				'@ID' => $groupIds,
			),
			'select' => ['ID', 'SCRUM_MASTER_ID'],
		]);

		$scrumProjectIds = [];

		while ($scrumProject = $scrumProjects->fetch())
		{
			$scrumProjectIds[] = [
				'ID' => (int)$scrumProject['ID'],
				'SCRUM_MASTER_ID' => (int)$scrumProject['SCRUM_MASTER_ID'],
			];
		}

		return $scrumProjectIds;
	}

	/**
	 * @param int[] $groupTasks
	 * @param int[] $scrumProjectIds
	 * @return array|int[]
	 */
	private function filterTaskIdsByProjectIds(array $groupTasks, array $scrumProjectIds): array
	{
		$taskIdsInScrumProjects = [];
		foreach ($groupTasks as $taskId => $groupId)
		{
			if (in_array($groupId, $scrumProjectIds, true))
			{
				$taskIdsInScrumProjects[] = $taskId;
			}
		}

		return $taskIdsInScrumProjects;
	}

	/**
	 * @param ItemForm[] $scrumItems
	 * @return int[]
	 */
	private function filterTaskIdsInActiveSprints(array $scrumItems): array
	{
		$scrumEntityIds = $this->extractEntityIdsFromScrumItems($scrumItems);

		if (empty($scrumEntityIds))
		{
			return [];
		}

		$sprintService = new SprintService();
		$sprints = $sprintService->getSprintByIds($scrumEntityIds);

		if (empty($sprints) || $sprintService->getErrors())
		{
			return [];
		}

		$activeSprints = $this->getActiveSprintIds($sprints);

		if (empty($activeSprints))
		{
			return [];
		}

		$taskIdsInActiveSprints = [];
		foreach ($scrumItems as $scrumItem)
		{
			if ($scrumItem->isEmpty())
			{
				continue;
			}

			if (in_array($scrumItem->getEntityId(), $activeSprints, true))
			{
				$taskIdsInActiveSprints[] = $scrumItem->getSourceId();
			}
		}

		return $taskIdsInActiveSprints;
	}

	/**
	 * @param ItemForm[] $scrumItems
	 * @return array|int[]
	 */
	private function extractEntityIdsFromScrumItems(array $scrumItems): array
	{
		$scrumEntityIds = [];
		foreach ($scrumItems as $scrumItem)
		{
			if ($scrumItem->isEmpty())
			{
				continue;
			}

			$scrumEntityIds[] = $scrumItem->getEntityId();
		}

		return $scrumEntityIds;
	}

	/**
	 * @param EntityForm[] $sprints
	 * @return int[]
	 */
	private function getActiveSprintIds(array $sprints): array
	{
		$activeSprintTaskIds = [];
		foreach ($sprints as $sprint)
		{
			if ($sprint->isActiveSprint() && !$sprint->isEmpty())
			{
				$activeSprintTaskIds[] = $sprint->getId();
			}
		}

		return $activeSprintTaskIds;
	}
}
