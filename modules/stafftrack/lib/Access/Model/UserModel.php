<?php

namespace Bitrix\StaffTrack\Access\Model;

class UserModel extends \Bitrix\Main\Access\User\UserModel
{

	public function getRoles(): array
	{
		return [];
	}

	public function getPermission(string $permissionId): ?int
	{
		return 0;
	}
}