<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

final class ChecklistAttachmentDto
{
	public function __construct(
		public readonly int $id,
		public readonly int $fileId,
		public readonly string $serverFileId,
		public readonly string $name,
		public readonly string $url,
		public readonly string $type,
		public readonly bool $isUploading = false,
	)
	{
	}
}
