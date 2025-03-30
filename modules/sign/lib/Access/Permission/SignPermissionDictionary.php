<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Sign\Access\Permission;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Helper\IterationHelper;

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
	public const SIGN_B2E_MEMBER_DYNAMIC_FIELDS_DELETE = 12;
	public const SIGN_B2E_TEMPLATE_READ = 13;
	public const SIGN_B2E_TEMPLATE_CREATE = 14;
	public const SIGN_B2E_TEMPLATE_WRITE = 15;
	public const SIGN_B2E_TEMPLATE_DELETE = 16;

	public static function isValid(string|int $permission): bool
	{
		return IterationHelper::any(self::getList(), fn($value, $id) => $permission === $id);
	}

	public static function isVariable($permissionId): bool
	{
		return in_array($permissionId, [
			self::SIGN_MY_SAFE_DOCUMENTS,
			self::SIGN_B2E_MY_SAFE_DOCUMENTS,
			self::SIGN_B2E_TEMPLATE_READ,
			self::SIGN_B2E_TEMPLATE_CREATE,
			self::SIGN_B2E_TEMPLATE_WRITE,
			self::SIGN_B2E_TEMPLATE_DELETE,
			self::SIGN_TEMPLATES,
			self::SIGN_B2E_TEMPLATES,
		]);
	}

	/**
	 * @param self::TYPE_VARIABLES|static::TYPE_TOGGLER $permissionId
	 *
	 * @return string
	 */
	public static function getType($permissionId): string
	{
		return self::isVariable($permissionId)
			? static::TYPE_VARIABLES
			: static::TYPE_TOGGLER;
	}

	public static function getPermission($permissionId): array
	{
		$permission = parent::getPermission($permissionId);
		if (array_key_exists('title', $permission))
		{
			$permission['title'] = static::getTitle($permissionId);
		}

		return $permission;
	}

	public static function getTitle($permissionId): string
	{
		static::loadLoc();
		$title = static::getPermissionTitleLocCode($permissionId);
		if ($title)
		{
			return Loc::getMessage($title) ?? '';
		}

		return parent::getTitle($permissionId) ?? '';
	}

	private static function getPermissionTitleLocCode($permissionId): ?string
	{
		return match ($permissionId)
		{
			self::SIGN_ACCESS_RIGHTS => 'SIGN_ACCESS_RIGHTS',
			self::SIGN_MY_SAFE_DOCUMENTS => 'SIGN_MY_SAFE_DOCUMENTS',
			self::SIGN_MY_SAFE => 'SIGN_MY_SAFE',
			self::SIGN_TEMPLATES => 'SIGN_TEMPLATES',
			self::SIGN_B2E_MY_SAFE_DOCUMENTS => 'SIGN_B2E_MY_SAFE_DOCUMENTS',
			self::SIGN_B2E_MY_SAFE => 'SIGN_B2E_MY_SAFE',
			self::SIGN_B2E_TEMPLATES => 'SIGN_B2E_TEMPLATES_1',
			self::SIGN_B2E_PROFILE_FIELDS_READ => 'SIGN_B2E_PROFILE_FIELDS_READ',
			self::SIGN_B2E_PROFILE_FIELDS_ADD => 'SIGN_B2E_PROFILE_FIELDS_ADD',
			self::SIGN_B2E_PROFILE_FIELDS_EDIT => 'SIGN_B2E_PROFILE_FIELDS_EDIT',
			self::SIGN_B2E_PROFILE_FIELDS_DELETE => 'SIGN_B2E_PROFILE_FIELDS_DELETE_MSG_VER_1',
			self::SIGN_B2E_MEMBER_DYNAMIC_FIELDS_DELETE => 'SIGN_B2E_MEMBER_DYNAMIC_FIELDS_DELETE',
			self::SIGN_B2E_TEMPLATE_READ => 'SIGN_B2E_TEMPLATE_READ',
			self::SIGN_B2E_TEMPLATE_CREATE => 'SIGN_B2E_TEMPLATE_CREATE',
			self::SIGN_B2E_TEMPLATE_WRITE => 'SIGN_B2E_TEMPLATE_EDIT',
			self::SIGN_B2E_TEMPLATE_DELETE => 'SIGN_B2E_TEMPLATE_DELETE',
			default => null,
		};
	}
}
