<?php

namespace Bitrix\Crm\Timeline\Bizproc\Dto;

final class WorkflowStatusChangedDto
{
	public function __construct(
		public readonly string $workflowId,
		public readonly string $documentId,
		public readonly int $documentEventType,
		public readonly int $status,
	)
	{}
}
