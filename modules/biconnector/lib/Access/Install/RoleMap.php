<?php

namespace Bitrix\BIConnector\Access\Install;

use Bitrix\BIConnector\Access\Role\RoleDictionary;

final class RoleMap
{
	/**
	 * @return array<string, string>
	 */
	public static function getDefaultMap(): array
	{
		return [
			RoleDictionary::ROLE_ADMINISTRATOR => Role\Administrator::class,
			RoleDictionary::ROLE_ANALYST => Role\Analyst::class,
			RoleDictionary::ROLE_MANAGER => Role\Manager::class,
		];
	}
}
