<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Sign\Access\Permission;

class SignPermissionDictionary extends \Bitrix\Main\Access\Permission\PermissionDictionary
{
	use PermissionName;

	public const SIGN_ACCESS_RIGHTS = 1;
	public const SIGN_MY_SAFE_DOCUMENTS = 2;
	public const SIGN_MY_SAFE = 3;
	public const SIGN_TEMPLATES = 4;
	public const SIGN_B2E_PROFILE_FIELDS_READ = 5;
	public const SIGN_B2E_PROFILE_FIELDS_ADD = 6;
	public const SIGN_B2E_PROFILE_FIELDS_EDIT = 7;
	public const SIGN_B2E_PROFILE_FIELDS_DELETE = 8;
	public const SIGN_B2E_MY_SAFE_DOCUMENTS = 9;
	public const SIGN_B2E_MY_SAFE = 10;
	public const SIGN_B2E_TEMPLATES = 11;

	private static function isVariable($permissionId): bool
	{
		return in_array($permissionId, [
			self::SIGN_MY_SAFE_DOCUMENTS,
			self::SIGN_B2E_MY_SAFE_DOCUMENTS,
			self::SIGN_TEMPLATES,
			self::SIGN_B2E_TEMPLATES,
		]);
	}

	public static function getType($permissionId): string
	{
		return self::isVariable($permissionId)
			? static::TYPE_VARIABLES
			: static::TYPE_TOGGLER;
	}
}