<?php
namespace Bitrix\Intranet\LeftMenu\MenuItem;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\LeftMenu;

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
