<?php

declare(strict_types=1);

namespace Bitrix\Mobile\Dto\Ai;

use Bitrix\Mobile\Dto\Dto;

final class CopilotRoleDto extends Dto
{
	public string $code;
	public string $name;
	public string $description;
	public array $avatar;
	public string $industryCode;
	public bool $isNew;
	public bool $isRecommended;
}
