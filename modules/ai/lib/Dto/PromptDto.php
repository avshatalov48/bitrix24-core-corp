<?php

declare(strict_types = 1);

namespace Bitrix\AI\Dto;

class PromptDto
{
	public function __construct(
		public readonly string $code,
		public readonly PromptType $promptType,
		public readonly string $title,
		public readonly string $text,
		public readonly bool $isNew,
	)
	{
	}
}