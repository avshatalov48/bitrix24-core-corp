<?php
namespace Bitrix\Intranet\UI\LeftMenu\MenuItem;

use \Bitrix\Intranet\UI\LeftMenu;

class ItemRestApplication extends ItemUser
{
	public function getCode(): string
	{
		return 'admin';
	}

	public function canUserDelete(LeftMenu\User $user): bool
	{
		return $user->isAdmin();
	}
}
