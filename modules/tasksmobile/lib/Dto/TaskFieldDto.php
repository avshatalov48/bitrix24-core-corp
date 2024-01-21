<?php

declare(strict_types=1);

namespace Bitrix\TasksMobile\Dto;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Transformer\ToCamelCase;

final class TaskFieldDto extends Dto
{
	public string $code;
	public string $title;
	public bool $visible;
}
