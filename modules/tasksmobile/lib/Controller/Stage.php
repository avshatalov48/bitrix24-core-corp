<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\Result;
use Bitrix\TasksMobile\Dto\TaskRequestFilter;
use Bitrix\TasksMobile\Provider\StageProvider;

class Stage extends Base
{
	protected function getQueryActionNames(): array
	{
		return [
			'getPlannerStages',
			'getDeadlineStages',
			'getKanbanStages',
		];
	}

	private function processResult(Result $result)
	{
		if (!$result->isSuccess())
		{
			$this->errorCollection->add($result->getErrors());
		}

		return $this->convertKeysToCamelCase($result->getData());
	}

	/**
	 * @param int|null $projectId
	 * @param TaskRequestFilter $searchParams
	 * @param array $extra
	 * @return array
	 */
	public function getPlannerStagesAction(
		TaskRequestFilter $searchParams,
		?int $projectId = null,
		array $extra = []
	): array
	{
		$stageProvider = new StageProvider($this->getCurrentUser()->getId(), $searchParams, $extra);
		$result = $stageProvider->getPlannerStages($projectId);

		return $this->processResult($result);
	}

	/**
	 * @param int|null $projectId
	 * @param TaskRequestFilter $searchParams
	 * @param array $extra
	 * @return array
	 */
	public function getDeadlineStagesAction(
		TaskRequestFilter $searchParams,
		?int $projectId = null,
		array $extra = []
	): array
	{
		$stageProvider = new StageProvider($this->getCurrentUser()->getId(), $searchParams, $extra);
		$result = $stageProvider->getDeadlineStages($projectId);

		return $this->processResult($result);
	}

	/**
	 * @param int $projectId
	 * @param TaskRequestFilter $searchParams
	 * @param array $extra
	 * @return array
	 */
	public function getKanbanStagesAction(
		TaskRequestFilter $searchParams,
		int $projectId,
		array $extra = [],
		?int $taskId = null,
	): array
	{
		$stageProvider = new StageProvider($this->getCurrentUser()->getId(), $searchParams, $extra);
		$result = $stageProvider->getProjectStages($projectId, $taskId);

		return $this->processResult($result);
	}

	public function updatePlannerStagesOrderAction(array $stagesOrder = []): array
	{
		$stageProvider = new StageProvider($this->getCurrentUser()->getId());
		$result = $stageProvider->updateStagesSortOrder(null, $stagesOrder);

		return $this->processResult($result);
	}

	public function updateKanbanStagesOrderAction(int $projectId, array $stagesOrder = []): array
	{
		$stageProvider = new StageProvider($this->getCurrentUser()->getId());
		$result = $stageProvider->updateStagesSortOrder($projectId, $stagesOrder);

		return $this->processResult($result);
	}

	public function addPlannerStageAction(
		string $name,
		string $color,
		?int $afterId = null
	): array
	{
		$stageProvider = new StageProvider($this->getCurrentUser()->getId());
		$result = $stageProvider->addStage(null, $name, $color, $afterId);

		return $this->processResult($result);
	}

	public function addKanbanStageAction(
		string $name,
		string $color,
		int $projectId,
		?int $afterId = null
	): array
	{
		$stageProvider = new StageProvider($this->getCurrentUser()->getId());
		$result = $stageProvider->addStage($projectId, $name, $color, $afterId);

		return $this->processResult($result);
	}

	public function deletePlannerStageAction(int $stageId): array
	{
		$stageProvider = new StageProvider($this->getCurrentUser()->getId());
		$result = $stageProvider->deleteStage($stageId);

		return $this->processResult($result);
	}

	public function deleteKanbanStageAction(int $stageId, int $projectId): array
	{
		$stageProvider = new StageProvider($this->getCurrentUser()->getId());
		$result = $stageProvider->deleteStage($stageId, $projectId);

		return $this->processResult($result);
	}

	public function updatePlannerStageAction(int $stageId, string $name, string $color)
	{
		$stageProvider = new StageProvider($this->getCurrentUser()->getId());
		$result = $stageProvider->updateStage($stageId, null, $name, $color);

		return $this->processResult($result);
	}

	public function updateKanbanStageAction(int $projectId, int $stageId, string $name, string $color)
	{
		$stageProvider = new StageProvider($this->getCurrentUser()->getId());
		$result = $stageProvider->updateStage($stageId, $projectId, $name, $color);

		return $this->processResult($result);
	}
}
