<?php
namespace Bitrix\Tasks\Scrum\Utility;

use Bitrix\Main\UI\PageNavigation;
use Bitrix\Tasks\Scrum\Form\EntityForm;
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

	/**
	 * The method calculates the average, maximum, minimum number of story points.
	 *
	 * @param int $groupId Group id.
	 * @param EntityForm[] List sprints.
	 * @return array
	 */
	public function calculateStoryPointsStats(int $groupId, array $sprints): array
	{
		$cacheKey = hash_init('sha256');
		foreach ($sprints as $sprint)
		{
			hash_update($cacheKey, $sprint->getId());
		}
		$cacheKey = hash_final($cacheKey);

		$cacheService = new CacheService($groupId, CacheService::TEAM_STATS, $cacheKey);

		if ($cacheService->init())
		{
			return $cacheService->getData();
		}

		$kanbanService = new KanbanService();
		$itemService = new ItemService();

		$listSumStoryPoints = [];
		foreach ($sprints as $sprint)
		{
			$completedTaskIds = $kanbanService->getFinishedTaskIdsInSprint($sprint->getId());

			$listStoryPoints = $itemService->getItemsStoryPointsBySourceId($completedTaskIds);

			$listSumStoryPoints[$sprint->getId()] = $this->calculateSumStoryPoints($listStoryPoints);
		}

		$stats = [
			'average' => 0,
			'maximum' => 0,
			'minimum' => 0,
		];
		if ($listSumStoryPoints)
		{
			$stats = [
				'average' => round(
					array_sum(array_values($listSumStoryPoints)) / count($listSumStoryPoints),
					1
				),
				'maximum' => max($listSumStoryPoints),
				'minimum' => min($listSumStoryPoints),
			];
		}

		$cacheService->start();
		$cacheService->end($stats);

		return $stats;
	}
}