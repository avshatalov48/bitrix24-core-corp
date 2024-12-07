<?php

declare(strict_types=1);

namespace Bitrix\Mobile\Dto\Ai;

use Bitrix\Mobile\Dto\Dto;
use Bitrix\Mobile\Dto\Attributes\Collection;

final class CopilotIndustryDto extends Dto
{
	public string $code;
	public string $name;
	#[Collection(CopilotRoleDto::class)]
	public array $roles = [];
}
