<?php

namespace Bitrix\Crm\Timeline\Bizproc\Data;

use Bitrix\Crm\Timeline\Bizproc\Dto\WorkflowStatusChangedDto;

final class ChangedWorkflowStatus
{
	public readonly Workflow $workflow;
	public readonly array $documentId;
	public readonly int $documentEventType;

	private function __construct(Workflow $workflow, string $documentId, int $documentEventType)
	{
		$this->workflow = $workflow;
		[$entityTypeId, $entityId] = \CCrmBizProcHelper::resolveEntityIdByDocumentId($documentId);
		$this->documentId = \CCrmBizProcHelper::ResolveDocumentId($entityTypeId, $entityId) ?? [];
		$this->documentEventType = $documentEventType;
	}

	public static function createFromRequest(WorkflowStatusChangedDto $request): ?self
	{
		if (empty($request->workflowId) || empty($request->documentId))
		{
			return null;
		}

		$workflow = new Workflow($request->workflowId);

		return new self($workflow, $request->documentId, $request->documentEventType);
	}
}
