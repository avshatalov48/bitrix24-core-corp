<?php

namespace Bitrix\Crm\Timeline\Bizproc\Dto;

final class TaskStatusChangedDto
{
	public function __construct(
		public readonly string $workflowId,
		public readonly int $status,
		public readonly string $documentId,
		public readonly array $task,
	)
	{}
}
