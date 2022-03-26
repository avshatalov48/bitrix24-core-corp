<?php
namespace Bitrix\Intranet\UI\LeftMenu\MenuItem;

use Bitrix\Intranet\UI\LeftMenu;

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
