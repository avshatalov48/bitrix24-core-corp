<?php

declare(strict_types=1);

namespace Bitrix\Booking\Access\Model;

use Bitrix\Main\Access\User\UserModel;

class User extends UserModel
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
