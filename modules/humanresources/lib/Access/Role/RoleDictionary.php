<?php

namespace Bitrix\HumanResources\Access\Role;

use Bitrix\Main\Localization\Loc;

class RoleDictionary
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

	public static function getTitle(string $value): string
	{
		$sectionsList = self::getConstants();

		if (!array_key_exists($value, $sectionsList)) {
			return '';
		}
		$title = $sectionsList[$value];

		return Loc::getMessage('HUMAN_RESOURCES_' . $title) ?? '';
	}
}