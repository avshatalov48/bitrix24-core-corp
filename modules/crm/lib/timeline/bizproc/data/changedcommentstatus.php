<?php

namespace Bitrix\Crm\Timeline\Bizproc\Data;

use Bitrix\Crm\Timeline\Bizproc\Dto\CommentStatusChangedDto;

final class ChangedCommentStatus
{
	public readonly Workflow $workflow;
	public readonly array $documentId;
	public readonly int $authorId;

	private function __construct(Workflow $workflow, array $documentId, int $authorId)
	{
		$this->workflow = $workflow;
		$this->documentId = $documentId;
		$this->authorId = $authorId;
	}

	public static function createFromRequest(CommentStatusChangedDto $request): ?self
	{
		if (empty($request->workflowId) || empty($request->documentId) || empty($request->authorId))
		{
			return null;
		}

		$workflow = new Workflow($request->workflowId);

		return new self($workflow, $request->documentId, $request->authorId);
	}
}
