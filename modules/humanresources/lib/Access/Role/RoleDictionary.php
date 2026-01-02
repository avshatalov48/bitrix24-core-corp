<?php

namespace Bitrix\HumanResources\Access\Role;

use Bitrix\Main\Localization\Loc;

class RoleDictionary extends \Bitrix\Main\Access\Role\RoleDictionary
{
	public const ROLE_ADMIN = 'HUMAN_RESOURCES_ROLE_ADMIN';
	public const ROLE_DIRECTOR = 'HUMAN_RESOURCES_ROLE_DIRECTOR';
	public const ROLE_EMPLOYEE = 'HUMAN_RESOURCES_ROLE_EMPLOYEE';
	public const ROLE_DEPUTY = 'HUMAN_RESOURCES_ROLE_DEPUTY';
	public const ROLE_HR = 'HUMAN_RESOURCES_ROLE_HR';

	public const ROLE_STRUCTURE_ADMIN = 'HUMAN_RESOURCES_ROLE_STRUCTURE_ADMIN';
	public const ROLE_TEAM_DIRECTOR = 'HUMAN_RESOURCES_ROLE_TEAM_DIRECTOR';
	public const ROLE_TEAM_EMPLOYEE = 'HUMAN_RESOURCES_ROLE_TEAM_EMPLOYEE';
	public const ROLE_TEAM_DEPUTY = 'HUMAN_RESOURCES_ROLE_TEAM_DEPUTY';

	/**
	 * returns an array of all RoleDictionary constants
	 * @return array<array-key, string>
	 */
	public static function getConstants(): array
	{
		$class = new \ReflectionClass(self::class);
		return array_flip($class->getConstants());
	}

	public static function getRoleName(string $code): string
	{
		if ($code === self::ROLE_ADMIN)
		{
			return Loc::getMessage(self::ROLE_STRUCTURE_ADMIN);
		}

		return parent::getRoleName($code);
	}
}