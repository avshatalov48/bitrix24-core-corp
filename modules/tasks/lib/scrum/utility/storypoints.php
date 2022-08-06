<?php
namespace Bitrix\Tasks\Scrum\Utility;

use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Scrum\Service\CacheService;
use Bitrix\Tasks\Scrum\Service\ItemService;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\SprintService;

class StoryPoints
{
	/**
	 * The method calculates the sum of story points.
	 *
	 * @param array $listStoryPoints List story points.
	 * @return float
	 */
	public function calculateSumStoryPoints(array $listStoryPoints): float
	{
		$sumStoryPoints = 0;

		foreach ($listStoryPoints as $storyPoints)
		{
			$sumStoryPoints += (float) $storyPoints;
		}

		return $sumStoryPoints;
	}

	/**
	 * The method calculates the average number of completed story points.
	 *
	 * @param int $groupId Group id.
	 * @return float
	 */
	public function calculateAverageNumberCompletedStoryPoints(int $groupId): float
	{
		$cacheService = new CacheService($groupId, CacheService::STATS);

		if ($cacheService->init())
		{
			$stats = $cacheService->getData();

			if (isset($stats['averageNumberCompletedStoryPoints']))
			{
				return $stats['averageNumberCompletedStoryPoints'];
			}
		}

		$averageNumberStoryPoints = 0;

		$nav = new PageNavigation('completed-sprints');
		$nav->setCurrentPage(1);
		$nav->setPageSize(5);

		$sprintService = new SprintService();
		$kanbanService = new KanbanService();
		$itemService = new ItemService();

		$listSumStoryPoints = [];

		$sprints = $sprintService->getCompletedSprints($groupId, $nav);
		foreach ($sprints as $sprint)
		{
			$completedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());

			$listStoryPoints = $itemService->getItemsStoryPointsBySourceId($completedTaskIds);

			$listSumStoryPoints[$sprint->getId()] = $this->calculateSumStoryPoints($listStoryPoints);
		}

		if ($listSumStoryPoints)
		{
			$averageNumberStoryPoints = round(
				array_sum(array_values($listSumStoryPoints)) / count($listSumStoryPoints),
				1
			);
		}

		$cacheService->start();
		$cacheService->end(['averageNumberCompletedStoryPoints' => $averageNumberStoryPoints]);

		return $averageNumberStoryPoints;
	}
}