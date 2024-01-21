<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Transformer\ToLower;

final class ChecklistSummaryDto extends Dto
{
	public int $completed = 0;
	public int $uncompleted = 0;

	protected function getDecoders(): array
	{
		return [
			new ToLower()
		];
	}
}
