<?php

namespace Bitrix\Crm\Timeline\Bizproc\Data;

use Bitrix\Crm\Timeline\Bizproc\Dto\TaskStatusChangedDto;

final class ChangedTaskStatus
{
	public readonly Task $task;
	public readonly Workflow $workflow;

	public readonly array $documentId;
	public readonly int $entityTypeId;
	public readonly int $entityId;

	public readonly array $users;
	public readonly array $usersRemoved;
	public readonly array $usersAdded;
	public readonly string $statusName;
	public readonly int $status;
	public readonly bool $isFullyCompleted;
	public readonly bool $isPartiallyCompleted;
	public readonly bool $isPartiallyUnCompleted;

	private function __construct(
		Workflow $workflow,
		Task $task,
		string $documentId,
		array $data,
	)
	{
		$this->workflow = $workflow;
		$this->task = $task;

		[$entityTypeId, $entityId] = \CCrmBizProcHelper::resolveEntityIdByDocumentId($documentId);
		$this->entityTypeId = $entityTypeId;
		$this->entityId = $entityId;
		$this->documentId = \CCrmBizProcHelper::ResolveDocumentId($entityTypeId, $entityId) ?? [];

		$this->initData($data);
	}

	public static function createFromRequest(TaskStatusChangedDto $request): ?self
	{
		$task = Task::initFromArray($request->task);
		if (empty($request->workflowId) || empty($request->documentId) || !$task)
		{
			return null;
		}

		$workflow = new Workflow($request->workflowId);

		return new self($workflow, $task, $request->documentId, $request->task);
	}

	private function initData(array $data): void
	{
		$this->users = (array)($data['USERS'] ?? []);
		$this->usersRemoved = (array)($data['USERS_REMOVED'] ?? []);
		$this->usersAdded = (array)($data['USERS_ADDED'] ?? []);
		$this->statusName = (string)($data['STATUS_NAME'] ?? '');
		$this->status = (int)($data['STATUS'] ?? -1);

		$this->isFullyCompleted = $this->status !== -1 && $this->status !== 0;
		$this->isPartiallyCompleted = $this->statusName === 'COMPLETED';
		$this->isPartiallyUnCompleted = $this->statusName === 'UNCOMPLETED';
	}
}
