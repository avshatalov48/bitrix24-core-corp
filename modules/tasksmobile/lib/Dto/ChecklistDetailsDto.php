<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Transformer\ToLower;

final class ChecklistDetailsDto extends Dto
{
	public string $title;
	public ?int $completed = null;
	public ?int $uncompleted = null;

	protected function getDecoders(): array
	{
		return [
			new ToLower()
		];
	}
}
