<?php

namespace Bitrix\ImOpenLines\UserField;

use Bitrix\ImOpenlines\Security\Permissions;
use Bitrix\Main\UserField\UserFieldAccess;

class Access extends UserFieldAccess
{
	protected function getAvailableEntityIds(): array
	{
		if (Permissions::createWithCurrentUser()->canModifySettings())
		{
			return [
				\Bitrix\ImOpenLines\Model\SessionTable::getUfId()
			];
		}

		return [];
	}
}