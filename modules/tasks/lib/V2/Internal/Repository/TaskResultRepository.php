<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity\Result;
use Bitrix\Tasks\V2\Internal\Entity\ResultCollection;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\V2\Internal\Model\TaskResultFileTable;
use Bitrix\Main\Type\Collection;

class TaskResultRepository implements TaskResultRepositoryInterface
{
	public function __construct(
		private readonly UserRepositoryInterface  $userRepository,
		private readonly TaskParameterRepositoryInterface $taskParameterRepository,
	)
	{
	}

	public function isResultRequired(int $taskId): bool
	{
		return $this->taskParameterRepository->isResultRequired($taskId);
	}

	public function getById(int $resultId): Result|null
	{
		$result = ResultTable::getById($resultId)->fetchObject();

		if (!$result)
		{
			return null;
		}

		return new Result(
			id: $result->getId(),
			taskId: $result->getTaskId(),
			text: $result->getText(),
			author: $this->userRepository->getByIds([$result->getCreatedBy()])->findOneById($result->getCreatedBy()),
			createdAtTs: $result->getCreatedAt() ? $result->getCreatedAt()->getTimestamp() : null,
			updatedAtTs: $result->getUpdatedAt() ? $result->getUpdatedAt()->getTimestamp() : null,
			status: Result\Status::fromRaw($result->getStatus()),
			fileIds: $this->getFiles($resultId),
		);
	}

	public function getByTask(int $taskId): ResultCollection
	{
		$collection = [];

		$results = ResultTable::getByTaskId($taskId);

		foreach ($results as $result)
		{
			$collection[] = new Result(
				id: $result->getId(),
				taskId: $result->getTaskId(),
				text: $result->getText(),
				author: $this->userRepository->getByIds([$result->getCreatedBy()])->findOneById($result->getCreatedBy()),
				createdAtTs: $result->getCreatedAt() ? $result->getCreatedAt()->getTimestamp() : null,
				updatedAtTs: $result->getUpdatedAt() ? $result->getUpdatedAt()->getTimestamp() : null,
				status: Result\Status::fromRaw($result->getStatus()),
				fileIds: $this->getFiles($result->getId()),
			);
		}

		return new ResultCollection(...$collection);
	}

	public function save(Result $entity, int $userId): int
	{
		$data = [
			'COMMENT_ID' => 0,
			'TASK_ID' => $entity->taskId,
			'TEXT' => $entity->text,
			'CREATED_BY' => $userId,
			'CREATED_AT' => $entity->createdAtTs ? \Bitrix\Main\Type\DateTime::createFromTimestamp($entity->createdAtTs) : null,
			'UPDATED_AT' => $entity->updatedAtTs ? \Bitrix\Main\Type\DateTime::createFromTimestamp($entity->updatedAtTs) : null,
			'STATUS' => $entity->status?->getRaw(),
		];

		if ($entity->id)
		{
			// Update existing record
			ResultTable::update($entity->id, $data);
			// Update files if required
			$this->saveFiles($entity->id, $entity);

			return $entity->id;
		}

		// Add new record
		$resultId = ResultTable::add($data)->getId();

		// Save files if required
		$this->saveFiles($resultId, $entity);

		return $resultId;
	}

	public function delete(int $id, int $userId): void
	{
		ResultTable::delete($id);
	}

	private function getFiles(int $resultId): array
	{
		$resp = TaskResultFileTable::query()
			->setSelect(['FILE_ID'])
			->where('RESULT_ID', $resultId)
			->exec()
			->fetchAll()
		;

		$fileIds = [];

		foreach ($resp as $row)
		{
			$fileIds[] = (int)$row['FILE_ID'];
		}

		Collection::normalizeArrayValuesByInt($fileIds);

		return $fileIds;
	}

	private function saveFiles(int $resultId, Result $result): void
	{
		$filesInDb = $this->getFiles($resultId);
		$incomingFileIds = $result->fileIds ?? [];
		Collection::normalizeArrayValuesByInt($incomingFileIds);

		if ($filesInDb === $incomingFileIds)
		{
			return;
		}

		TaskResultFileTable::deleteByFilter([
			'RESULT_ID' => $resultId,
		]);

		$rowsToAdd = [];

		foreach ($incomingFileIds as $fileId)
		{
			$rowsToAdd[] = ['RESULT_ID' => $resultId, 'FILE_ID' => (int)$fileId];
		}

		if (empty($rowsToAdd))
		{
			return;
		}

		TaskResultFileTable::addMergeMulti($rowsToAdd);
	}
}
