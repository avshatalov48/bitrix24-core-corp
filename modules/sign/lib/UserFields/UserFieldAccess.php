<?php

namespace Bitrix\Sign\UserFields;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Config\LegalInfo;
use Bitrix\Sign\Service\Providers\MemberDynamicFieldInfoProvider;

class UserFieldAccess extends \Bitrix\Main\UserField\UserFieldAccess
{
	protected function getAvailableEntityIds(): array
	{
		$ids = [
			MemberDynamicFieldInfoProvider::USER_FIELD_ENTITY_ID,
		];

		if (LegalInfo::canAdd(CurrentUser::get()->getId()))
		{
			$ids[] = LegalInfo::USER_FIELD_ENTITY_ID;
		}

		return $ids;
	}
}
