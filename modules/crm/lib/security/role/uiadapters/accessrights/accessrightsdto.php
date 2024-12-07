<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights;

class AccessRightsDTO
{
	public function __construct(
		public readonly array $accessRights,
		public readonly array $userGroups,
	)
	{

	}
}