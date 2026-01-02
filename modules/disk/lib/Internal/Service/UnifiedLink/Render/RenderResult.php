<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink\Render;

final class RenderResult
{
	public function __construct(
		private readonly string $content,
		private readonly int $status,
	) {
	}

	public function getContent(): string
	{
		return $this->content;
	}

	public function getStatus(): int
	{
		return $this->status;
	}
}
