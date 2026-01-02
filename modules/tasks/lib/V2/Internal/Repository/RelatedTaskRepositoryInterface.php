<?php

namespace Bitrix\Tasks\V2\Internal\Repository;

interface RelatedTaskRepositoryInterface
{
	public function getRelatedTaskIds(int $taskId): array;

	public function containsRelatedTasks(int $taskId): bool;

	public function save(int $taskId, array $relatedTaskIds): void;

	public function deleteByTaskId(int $taskId): void;

	public function deleteByRelatedTaskIds(int $taskId, array $relatedTaskIds): void;
}
