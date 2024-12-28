<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Dto;

use Bitrix\AI\ShareRole\Events\Enums\ShareType;

class DeleteRoleIdForUserDto
{
	public int $userId;
	public bool $hasInOwnerTable = false;
	public bool $needDeleted = false;
	public int $roleId;
	public string $roleCode;
	public ShareType $shareType;
}
