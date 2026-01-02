<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

class InMemoryRelatedTaskRepository implements RelatedTaskRepositoryInterface
{
	private readonly RelatedTaskRepository $relatedTaskRepository;

	private array $existenceCache = [];
	private array $cache = [];

	public function __construct(RelatedTaskRepository $relatedTaskRepository)
	{
		$this->relatedTaskRepository = $relatedTaskRepository;
	}

	public function getRelatedTaskIds(int $taskId): array
	{
		if (!isset($this->cache[$taskId]))
		{
			$ids = $this->relatedTaskRepository->getRelatedTaskIds($taskId);
			$this->cache[$taskId] = $ids;
			$this->existenceCache[$taskId] = !empty($ids);
		}

		return $this->cache[$taskId];
	}

	public function containsRelatedTasks(int $taskId): bool
	{
		if (!isset($this->existenceCache[$taskId]))
		{
			$this->existenceCache[$taskId] = $this->relatedTaskRepository->containsRelatedTasks($taskId);
		}

		return $this->existenceCache[$taskId];
	}

	public function save(int $taskId, array $relatedTaskIds): void
	{
		$this->relatedTaskRepository->save($taskId, $relatedTaskIds);

		unset($this->existenceCache[$taskId], $this->cache[$taskId]);
	}

	public function deleteByTaskId(int $taskId): void
	{
		$this->relatedTaskRepository->deleteByTaskId($taskId);

		unset($this->existenceCache[$taskId], $this->cache[$taskId]);
	}

	public function deleteByRelatedTaskIds(int $taskId, array $relatedTaskIds): void
	{
		$this->relatedTaskRepository->deleteByRelatedTaskIds($taskId, $relatedTaskIds);

		unset($this->existenceCache[$taskId], $this->cache[$taskId]);
	}
}
