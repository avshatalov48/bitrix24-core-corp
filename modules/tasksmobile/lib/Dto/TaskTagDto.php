<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Transformer\ToCamelCase;

class TaskTagDto extends Dto
{
	public int $id;
	public string $name;

	protected function getDecoders(): array
	{
		return [
			new ToCamelCase(),
		];
	}
}
