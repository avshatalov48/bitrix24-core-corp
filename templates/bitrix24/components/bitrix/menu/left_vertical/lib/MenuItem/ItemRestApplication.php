<?php
namespace Bitrix\Intranet\LeftMenu\MenuItem;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\LeftMenu;

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
