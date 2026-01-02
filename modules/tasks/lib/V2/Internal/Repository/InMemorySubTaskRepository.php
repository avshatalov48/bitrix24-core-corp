<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

class InMemorySubTaskRepository implements SubTaskRepositoryInterface
{
	private readonly SubTaskRepository $subTaskRepository;

	private array $existenceCache = [];

	public function __construct(SubTaskRepository $subTaskRepository)
	{
		$this->subTaskRepository = $subTaskRepository;
	}

	public function containsSubTasks(int $parentId): bool
	{
		if (!isset($this->existenceCache[$parentId]))
		{
			$this->existenceCache[$parentId] = $this->subTaskRepository->containsSubTasks($parentId);
		}

		return $this->existenceCache[$parentId];
	}
}
