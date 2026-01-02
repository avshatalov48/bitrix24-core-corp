<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider\Relation;

use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\V2\Internal\Access\Service\TaskRightService;
use Bitrix\Tasks\V2\Internal\Access\Task\ActionDictionary;
use Bitrix\Tasks\V2\Internal\Entity\Task\Gantt\LinkType;
use Bitrix\Tasks\V2\Internal\Entity\TaskCollection;
use Bitrix\Tasks\V2\Internal\Repository\GanttLinkRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\GanttRelationTaskMapper;
use Bitrix\Tasks\V2\Internal\Repository\UserRepositoryInterface;
use Bitrix\Tasks\V2\Public\Provider\Params\Relation\RelationTaskParams;

class GanttDependenceProvider extends AbstractRelationTaskProvider
{
	public function __construct(
		protected readonly TaskRightService $taskRightService,
		protected readonly TaskList $taskList,
		protected readonly GanttRelationTaskMapper $ganttRelationTaskMapper,
		protected readonly UserRepositoryInterface $userRepository,
		protected readonly GanttLinkRepositoryInterface $ganttLinkRepository,
	)
	{

	}

	public function getTasks(RelationTaskParams $relationTaskParams): TaskCollection
	{
		if ($relationTaskParams->taskId <= 0)
		{
			return new TaskCollection();
		}

		if (
			$relationTaskParams->checkRootAccess
			&& !$this->taskRightService->canView($relationTaskParams->userId, $relationTaskParams->taskId)
		)
		{
			return new TaskCollection();
		}

		$select = $relationTaskParams->getSelect() ?? $this->getDefaultSelect();

		$tasks = $this->fetchTasks(
			select: $select,
			filter: $this->getFilter($relationTaskParams),
			userId: $relationTaskParams->userId,
			offset: $relationTaskParams->getOffset(),
			limit: $relationTaskParams->getLimit(),
		);

		if (empty($tasks))
		{
			return new TaskCollection();
		}

		$taskIds = array_column($tasks, 'ID');
		Collection::normalizeArrayValuesByInt($taskIds, false);

		$ganttLinks = $this->ganttLinkRepository->getLinkTypes($relationTaskParams->taskId, $taskIds);
		$rights = $this->getRelationRights($taskIds, $relationTaskParams->taskId, $relationTaskParams->userId);

		return $this->ganttRelationTaskMapper->mapToCollection(
			tasks: $tasks,
			rights: $rights,
			ganttLinks: $ganttLinks,
		);
	}

	public function getTasksByIds(array $ids, int $userId): TaskCollection
	{
		Collection::normalizeArrayValuesByInt($ids, false);

		if (empty($ids))
		{
			return new TaskCollection();
		}

		$tasks = $this->fetchTasks(
			select: $this->getDefaultSelect(),
			filter: $this->getIdsFilter($ids),
			userId: $userId,
		);

		if (empty($tasks))
		{
			return new TaskCollection();
		}

		$taskIds = array_column($tasks, 'ID');
		Collection::normalizeArrayValuesByInt($taskIds, false);

		$ganttLinks = array_map(static fn (int $taskId): array => [$taskId => LinkType::FinishStart], $taskIds);
		$rights = $this->getRelationRights($taskIds, 0, $userId);

		return $this->ganttRelationTaskMapper->mapToCollection(
			tasks: $tasks,
			rights: $rights,
			ganttLinks: $ganttLinks,
		);
	}

	public function getDefaultSelect(): array
	{
		return [
			'id',
			'title',
		];
	}

	protected function getFilter(RelationTaskParams $relationTaskParams): array
	{
		return ['=GANTT_ANCESTOR_ID' => $relationTaskParams->taskId];
	}

	protected function translateSelect(array $select): array
	{
		$map = [
			'id' => 'ID',
			'title' => 'TITLE',
		];

		$result = [];
		foreach ($select as $field)
		{
			if (!is_string($field))
			{
				continue;
			}

			if (isset($map[$field]))
			{
				$result[] = $map[$field];
			}
		}

		return $result;
	}

	protected function getRelationRights(array $taskIds, int $taskId, int $userId): array
	{
		if (empty($taskIds))
		{
			return [];
		}

		$params['changeDependence'] = ['dependentId' => $taskId];

		return $this->taskRightService->getTaskRightsBatch(
			userId: $userId,
			taskIds: $taskIds,
			rules: ActionDictionary::GANTT_TASK_ACTIONS,
			params: $params,
		);
	}
}
