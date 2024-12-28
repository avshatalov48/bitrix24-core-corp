<?php declare(strict_types=1);

namespace Bitrix\AI\ShareRole\Dto;

class RoleUserDto
{
	public int $roleId;
	public int $userId;
	public string $roleCode;
	public string $text = '';
}
