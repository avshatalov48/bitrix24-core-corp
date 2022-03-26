<?php
namespace Bitrix\Intranet\UI\LeftMenu\MenuItem;

use \Bitrix\Intranet\UI\LeftMenu;

class ItemAdminCustom extends ItemUser
{
	public function getCode(): string
	{
		return 'custom';
	}

	public function canUserDelete(LeftMenu\User $user): bool
	{
		return $user->isAdmin();
	}
}
