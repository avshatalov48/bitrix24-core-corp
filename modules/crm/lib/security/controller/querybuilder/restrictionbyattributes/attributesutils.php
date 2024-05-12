<?php

namespace Bitrix\Crm\Security\Controller\QueryBuilder\RestrictionByAttributes;

final class AttributesUtils
{
	protected static string $userRegex = '/^U(\d+)$/i';

	protected static string $departmentRegex = '/^D(\d+)$/i';

	public static function tryParseUser($attribute, &$value): bool
	{
		return self::tryParseAttributeValue($attribute, self::$userRegex, $value);
	}

	public static function tryParseDepartment($attribute, &$value): bool
	{
		return self::tryParseAttributeValue($attribute, self::$departmentRegex, $value);
	}

	private static function tryParseAttributeValue($attribute, $regex, &$value): bool
	{
		if (preg_match($regex, $attribute, $m) !== 1)
		{
			return false;
		}

		$value = $m[1] ?? '';

		return true;
	}
}