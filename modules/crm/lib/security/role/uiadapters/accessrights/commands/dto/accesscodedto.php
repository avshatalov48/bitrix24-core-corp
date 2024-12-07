<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DTO;

class AccessCodeDTO
{
	public function __construct(
		public readonly string $id,
		public readonly ?string $type,
	)
	{
	}
}