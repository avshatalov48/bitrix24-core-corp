<?php
namespace Bitrix\Tasks\Scrum\Checklist;

use Bitrix\Main\Access\User\UserModel as BaseUserModel;

class UserModel extends BaseUserModel
{
	public function getRoles(): array
	{
		return [];
	}

	public function getPermission(string $permissionId): ?int
	{
		return null;
	}
}