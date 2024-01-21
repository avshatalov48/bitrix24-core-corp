<?php

namespace Bitrix\Tasks\Member\Role;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Member\AbstractMemberManager;

class Auditors extends AbstractMemberManager
{
	public function getRole(): string
	{
		return RoleDictionary::ROLE_AUDITOR;
	}
}