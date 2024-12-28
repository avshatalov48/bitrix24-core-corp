<?php

namespace Bitrix\HumanResources\Access\Role;

use Bitrix\Main\Localization\Loc;

class RoleDictionary extends \Bitrix\Main\Access\Role\RoleDictionary
{
	public const ROLE_ADMIN = 'HUMAN_RESOURCES_ROLE_ADMIN';
	public const ROLE_DIRECTOR = 'HUMAN_RESOURCES_ROLE_DIRECTOR';
	public const ROLE_EMPLOYEE = 'HUMAN_RESOURCES_ROLE_EMPLOYEE';

	/**
	 * returns an array of all RoleDictionary constants
	 * @return array<array-key, string>
	 */
	public static function getConstants(): array
	{
		$class = new \ReflectionClass(self::class);
		return array_flip($class->getConstants());
	}
}