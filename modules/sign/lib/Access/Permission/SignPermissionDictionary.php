<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Sign\Access\Permission;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Config\Feature;
use Bitrix\Sign\Config\Storage;
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
	public const SIGN_B2E_MY_SAFE_FIRED = 17;
	public const SIGN_B2E_SIGNERS_LIST_READ = 18;
	public const SIGN_B2E_SIGNERS_LIST_ADD = 19;
	public const SIGN_B2E_SIGNERS_LIST_EDIT = 20;
	public const SIGN_B2E_SIGNERS_LIST_DELETE = 21;
	public const SIGN_B2E_SIGNERS_LIST_REFUSED = 22;

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
			self::SIGN_B2E_SIGNERS_LIST_ADD,
			self::SIGN_B2E_SIGNERS_LIST_READ,
			self::SIGN_B2E_SIGNERS_LIST_DELETE,
			self::SIGN_B2E_SIGNERS_LIST_EDIT,
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
		if (Feature::instance()->isTemplateFolderGroupingAllowed())
		{
			$signB2eTemplateRead = 'SIGN_B2E_TEMPLATE_AND_FOLDER_READ';
			$signB2eTemplateCreate = 'SIGN_B2E_TEMPLATE_AND_FOLDER_CREATE';
			$signB2eTemplateWrite = 'SIGN_B2E_TEMPLATE_AND_FOLDER_EDIT';
			$signB2eTemplateDelete = 'SIGN_B2E_TEMPLATE_AND_FOLDER_DELETE';
		}
		else
		{
			$signB2eTemplateRead = 'SIGN_B2E_TEMPLATE_READ';
			$signB2eTemplateCreate = 'SIGN_B2E_TEMPLATE_CREATE';
			$signB2eTemplateWrite = 'SIGN_B2E_TEMPLATE_EDIT';
			$signB2eTemplateDelete = 'SIGN_B2E_TEMPLATE_DELETE';
		}

		return match ($permissionId)
		{
			self::SIGN_ACCESS_RIGHTS => 'SIGN_ACCESS_RIGHTS',
			self::SIGN_MY_SAFE_DOCUMENTS => 'SIGN_MY_SAFE_DOCUMENTS',
			self::SIGN_MY_SAFE => 'SIGN_MY_SAFE',
			self::SIGN_TEMPLATES => 'SIGN_TEMPLATES',
			self::SIGN_B2E_MY_SAFE_DOCUMENTS => 'SIGN_B2E_MY_SAFE_DOCUMENTS',
			self::SIGN_B2E_MY_SAFE => 'SIGN_B2E_MY_SAFE',
			self::SIGN_B2E_MY_SAFE_FIRED => 'SIGN_B2E_MY_SAFE_FIRED',
			self::SIGN_B2E_TEMPLATES => 'SIGN_B2E_TEMPLATES_1',
			self::SIGN_B2E_PROFILE_FIELDS_READ => 'SIGN_B2E_PROFILE_FIELDS_READ',
			self::SIGN_B2E_PROFILE_FIELDS_ADD => 'SIGN_B2E_PROFILE_FIELDS_ADD',
			self::SIGN_B2E_PROFILE_FIELDS_EDIT => 'SIGN_B2E_PROFILE_FIELDS_EDIT',
			self::SIGN_B2E_PROFILE_FIELDS_DELETE => 'SIGN_B2E_PROFILE_FIELDS_DELETE_MSG_VER_1',
			self::SIGN_B2E_MEMBER_DYNAMIC_FIELDS_DELETE => 'SIGN_B2E_MEMBER_DYNAMIC_FIELDS_DELETE',
			self::SIGN_B2E_TEMPLATE_READ => $signB2eTemplateRead,
			self::SIGN_B2E_TEMPLATE_CREATE => $signB2eTemplateCreate,
			self::SIGN_B2E_TEMPLATE_WRITE => $signB2eTemplateWrite,
			self::SIGN_B2E_TEMPLATE_DELETE => $signB2eTemplateDelete,
			self::SIGN_B2E_SIGNERS_LIST_READ => 'SIGN_B2E_SIGNERS_LIST_READ',
			self::SIGN_B2E_SIGNERS_LIST_ADD => 'SIGN_B2E_SIGNERS_LIST_ADD',
			self::SIGN_B2E_SIGNERS_LIST_EDIT => 'SIGN_B2E_SIGNERS_LIST_EDIT',
			self::SIGN_B2E_SIGNERS_LIST_DELETE => 'SIGN_B2E_SIGNERS_LIST_DELETE',
			self::SIGN_B2E_SIGNERS_LIST_REFUSED => 'SIGN_B2E_SIGNERS_LIST_REFUSED',
			default => null,
		};
	}
}
