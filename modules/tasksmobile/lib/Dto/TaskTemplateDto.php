<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\TasksMobile\Enum\TaskPriority;

final class TaskTemplateDto
{
	public function __construct(
		public readonly string $name, // for compatibility with TaskDto
		public readonly string $description,
		public readonly TaskPriority $priority,

		/** @var int[] */
		public readonly array $accomplices = [],

		/** @var int[] */
		public readonly array $auditors = [],

		/** @var DiskFileDto[] */
		public readonly array $files = [],

		public readonly array|null $checklist = null,

		/** @var TaskTemplateTagDto[] */
		public readonly array $tags = [],

		/** @var RelatedCrmItemDto[] */
		public readonly array $crm = [],
	) {}
}
