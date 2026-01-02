<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider\Relation;

use Bitrix\Main\DB\Order;
use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\Provider\Query\TaskQuery;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\V2\Internal\Access\Service\TaskRightService;
use Bitrix\Tasks\V2\Internal\Access\Task\ActionDictionary;
use Bitrix\Tasks\V2\Internal\Entity\TaskCollection;
use Bitrix\Tasks\V2\Internal\Entity\UserCollection;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\RelationTaskMapper;
use Bitrix\Tasks\V2\Internal\Repository\UserRepositoryInterface;
use Bitrix\Tasks\V2\Public\Provider\Params\Relation\RelationTaskParams;

abstract class AbstractRelationTaskProvider
{
	public function __construct(
		protected readonly TaskRightService $taskRightService,
		protected readonly TaskList $taskList,
		protected readonly UserRepositoryInterface $userRepository,
		protected readonly RelationTaskMapper $relationTaskMapper,
	)
	{

	}

	abstract protected function getFilter(RelationTaskParams $relationTaskParams): array;

	abstract protected function getRelationRights(array $taskIds, int $taskId, int $userId): array;

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

		$users = $this->getUsers($tasks);
		$rights = $this->getRelationRights($taskIds, $relationTaskParams->taskId, $relationTaskParams->userId);

		return $this->relationTaskMapper->mapToCollection(
			tasks: $tasks,
			users: $users,
			rights: $rights,
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

		$users = $this->getUsers($tasks);
		$rights = $this->getRelationRights($taskIds, 0, $userId);

		return $this->relationTaskMapper->mapToCollection(
			tasks: $tasks,
			users: $users,
			rights: $rights,
		);
	}

	public function getTaskIds(RelationTaskParams $relationTaskParams): array
	{
		if ($relationTaskParams->taskId <= 0)
		{
			return [];
		}

		if (
			$relationTaskParams->checkRootAccess
			&& !$this->taskRightService->canView($relationTaskParams->userId, $relationTaskParams->taskId)
		)
		{
			return [];
		}

		$filter = $this->getFilter($relationTaskParams);

		$query =
			(new TaskQuery($relationTaskParams->userId))
				->setSelect(['ID'])
				->setWhere($filter)
		;

		$tasks = $this->taskList->getList($query);

		$taskIds = array_column($tasks, 'ID');
		Collection::normalizeArrayValuesByInt($taskIds, false);

		return $taskIds;
	}

	protected function getDefaultSelect(): array
	{
		return [
			'id',
			'title',
			'responsible',
			'deadline',
		];
	}

	protected function translateSelect(array $select): array
	{
		$map = [
			'id' => 'ID',
			'title' => 'TITLE',
			'responsible' => 'RESPONSIBLE_ID',
			'deadline' => 'DEADLINE',
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

	protected function fetchTasks(
		array $select,
		array $filter,
		int $userId,
		?int $offset = null,
		?int $limit = null,
	): array
	{
		$select = $this->translateSelect($select);

		if (empty($select))
		{
			return [];
		}

		$query =
			(new TaskQuery($userId))
				->setSelect($select)
				->setWhere($filter)
				->setOrder(['ID' => Order::Desc->value])
		;

		if ($offset !== null)
		{
			$query->setOffset($offset);
		}

		if ($limit !== null)
		{
			$query->setLimit($limit);
		}

		return $this->taskList->getList($query);
	}

	protected function getUsers(array $tasks): UserCollection
	{
		$userIds = array_column($tasks, 'RESPONSIBLE_ID');

		Collection::normalizeArrayValuesByInt($userIds, false);

		if (empty($userIds))
		{
			return new UserCollection();
		}

		return $this->userRepository->getByIds($userIds);
	}

	protected function getIdsFilter(array $ids): array
	{
		return ['@ID' => $ids];
	}
}
