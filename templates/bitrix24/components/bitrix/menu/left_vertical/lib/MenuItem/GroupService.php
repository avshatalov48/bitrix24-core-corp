<?php
namespace Bitrix\Intranet\LeftMenu\MenuItem;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\LeftMenu;

class GroupService extends Group
{
	public function getCode(): string
	{
		return 'service_group_show_or_hidden_in_general';
	}

	public function canUserDelete(LeftMenu\User $user): bool
	{
		return false;
	}
}
