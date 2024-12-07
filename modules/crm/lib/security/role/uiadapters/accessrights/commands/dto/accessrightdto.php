<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Commands\DTO;

class AccessRightDTO
{
	public function __construct(
		public readonly string $id,
		public readonly string| array| null $value,
	)
	{
	}
}