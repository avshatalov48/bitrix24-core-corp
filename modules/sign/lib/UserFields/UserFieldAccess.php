<?php

namespace Bitrix\Sign\UserFields;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Config\LegalInfo;

class UserFieldAccess extends \Bitrix\Main\UserField\UserFieldAccess
{
	protected function getAvailableEntityIds(): array
	{
		if (LegalInfo::canEdit(CurrentUser::get()->getId()))
		{
			return [
				LegalInfo::USER_FIELD_ENTITY_ID,
			];
		}

		return [];
	}
}
