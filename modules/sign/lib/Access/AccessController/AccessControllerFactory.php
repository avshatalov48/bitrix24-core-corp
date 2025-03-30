<?php

namespace Bitrix\Sign\Access\AccessController;

use Bitrix\Main\Access\AccessibleController;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Access\AccessController;

class AccessControllerFactory
{
	public function createForCurrentUser(): AccessController
	{
		$userId = CurrentUser::get()->getId();
		if (is_numeric($userId) && $userId > 0)
		{
			return new AccessController($userId);
		}

		return new AlwaysAllowAccessController();
	}
}