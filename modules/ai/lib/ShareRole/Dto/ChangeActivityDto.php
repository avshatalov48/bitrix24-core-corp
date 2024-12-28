<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Dto;

use Bitrix\AI\ShareRole\Events\Enums\ShareType;

class ChangeActivityDto
{
	public int $userId;
	public int $roleId;
	public string $roleCode;
	public bool $needActivate = false;
	public ShareType $shareType;
}
