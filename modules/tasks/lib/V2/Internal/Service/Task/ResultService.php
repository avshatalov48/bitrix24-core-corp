<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Tasks\V2\Internal\Entity\HistoryLog;
use Bitrix\Tasks\V2\Internal\Entity\Result;
use Bitrix\Tasks\V2\Internal\Entity\ResultCollection;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Entity\UserCollection;
use Bitrix\Tasks\V2\Internal\Exception\Task\ResultNotFoundException;
use Bitrix\Tasks\V2\Internal\Repository\TaskLogRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskResultRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\AutomationService;
use Bitrix\Tasks\V2\Internal\Service\PushService;
use InvalidArgumentException;

class ResultService
{
	public const COMMAND_CREATE = 'task_result_create';
	public const COMMAND_DELETE = 'task_result_delete';
	public const COMMAND_UPDATE = 'task_result_update';

	public const RESULT_ADD = 'RESULT';
	public const RESULT_EDIT = 'RESULT_EDIT';
	public const RESULT_REMOVE = 'RESULT_REMOVE';

	private TaskResultRepositoryInterface $taskResultRepository;
	private TaskLogRepositoryInterface $taskLogRepository;
	private PushService $pushService;
	private AutomationService $automationService;

	public function __construct(
		TaskResultRepositoryInterface $taskResultRepository,
		TaskLogRepositoryInterface $taskLogRepository,
		PushService $pushService,
		AutomationService $automationService,
	)
	{
		$this->taskResultRepository = $taskResultRepository;
		$this->taskLogRepository = $taskLogRepository;
		$this->pushService = $pushService;
		$this->automationService = $automationService;
	}

	public function isResultRequired(int $taskId): bool
	{
		return $this->taskResultRepository->isResultRequired($taskId);
	}

	public function create(Result $result, int $userId): Result
	{
		if ($result->taskId === null)
		{
			throw new InvalidArgumentException('Task id must be set');
		}

		$resultToAdd = new Result(
			taskId: $result->taskId,
			text: $result->text,
			author: $result->author,
			createdAtTs: time(),
			updatedAtTs: time(),
			status: Result\Status::Open,
			fileIds: $result->fileIds,
		);

		$resultId = $this->taskResultRepository->save($resultToAdd, $userId);

		$result = Result::mapFromArray([...$resultToAdd->toArray(), 'id' => $resultId]);

		$this->sendPush(self::COMMAND_CREATE, $userId, $result);

		$historyLog = new HistoryLog(
			userId: $userId,
			taskId: $result->taskId,
			field: static::RESULT_ADD,
			toValue: $result->id,
		);

		$this->taskLogRepository->add($historyLog);

		$this->executeAutomationTrigger($result->taskId, $result);

		return $result;
	}

	public function update(Result $result, int $userId): Result
	{
		if ($result->id === null)
		{
			throw new InvalidArgumentException('Result id must be set');
		}

		$resultInDb = $this->taskResultRepository->getById($result->id);

		if (!$resultInDb)
		{
			throw new ResultNotFoundException('Result not found');
		}

		$updatedResult = Result::mapFromArray([
			...$resultInDb->toArray(),
			...array_filter(
				$result->toArray(),
				fn($value) => $value !== null
			)
		]);

		$this->taskResultRepository->save($updatedResult, $userId);

		$this->sendPush(self::COMMAND_UPDATE, $userId, $updatedResult);

		$historyLog = new HistoryLog(
			userId: $userId,
			taskId: $updatedResult->taskId,
			field: static::RESULT_EDIT,
			toValue: $updatedResult->id,
		);

		$this->taskLogRepository->add($historyLog);

		$this->executeAutomationTrigger($updatedResult->taskId, $updatedResult);

		return $updatedResult;
	}

	public function close(int $taskId, int $userId): void
	{
		$resultsCollection = $this->taskResultRepository->getByTask($taskId);

		if ($resultsCollection->isEmpty())
		{
			return;
		}

		foreach ($resultsCollection as $result)
		{
			if ($result->status === Result\Status::Open)
			{
				$updatedResult = Result::mapFromArray([...$result->toArray(), 'status' => Result\Status::Closed->value]);
				$this->taskResultRepository->save($updatedResult, $userId);
			}
		}
	}

	public function deleteByTaskId(int $taskId, int $userId): void
	{
		$resultsCollection = $this->taskResultRepository->getByTask($taskId);

		foreach ($resultsCollection as $result)
		{
			$this->taskResultRepository->delete($result->id, $userId);
			$this->sendPush(self::COMMAND_DELETE, $userId, $result);

			// todo: batch
			$historyLog = new HistoryLog(
				userId: $userId,
				taskId: $result->taskId,
				field: static::RESULT_REMOVE,
				toValue: $result->id,
			);

			$this->taskLogRepository->add($historyLog);
		}
	}

	public function getTaskResults(int $taskId): ResultCollection
	{
		return $this->taskResultRepository->getByTask($taskId);
	}

	public function getLastResult(int $taskId): ?Result
	{
		return $this->taskResultRepository->getByTask($taskId)->findOneById($taskId);
	}

	protected function sendPush(string $command, int $userId, Result $result): void
	{
		$recipients = new UserCollection(...[new User(id: $userId)]);

		$lastResult = $this->getLastResult($result->taskId);

		$this->pushService->addEvent($recipients, [
			'module_id' => $this->pushService->getModuleName(),
			'command' => $command,
			'params' => [
				'result' => $result->toArray(),
				'taskId' => $result->taskId,
				'taskRequireResult' => $this->isResultRequired($result->taskId) ? "Y" : "N",
				'taskHasResult' => $lastResult ? "Y" : "N",
				'taskHasOpenResult' => ($lastResult && $lastResult->status === Result\Status::Open) ? "Y" : "N",
			],
		]);
	}

	protected function executeAutomationTrigger(int $taskId, Result $result): void
	{
		$this->automationService->onTaskFieldChanged(
			taskId: $taskId,
			updatedFields: ['RESULT' => $result->text],
		);
	}
}
