<?php

namespace Bitrix\Crm\Timeline\Bizproc\Dto;

use Bitrix\Crm\Timeline\Bizproc\Data\CommentStatus;

final class CommentStatusChangedDto
{
	public function __construct(
		public readonly string $workflowId,
		public readonly array $documentId,
		public readonly int $authorId,
		public readonly CommentStatus $status,
	)
	{}
}
