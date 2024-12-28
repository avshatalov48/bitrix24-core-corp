<?php

namespace Bitrix\Crm\Security\Role\Manage\AttrPreset;

use Bitrix\Crm\Security\Role\UIAdapters\AccessRights\Variants;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Localization\Loc;

class UserRoleAndHierarchy
{
	public const NONE = '0';
	public const  SELF = 'SELF';
	public const  THIS_ROLE = 'THISROLE';
	public const  DEPARTMENT = 'DEPARTMENT';
	public const  SUBDEPARTMENTS = 'SUBDEPARTMENTS';
	public const  OPEN = 'OPEN';
	public const  ALL = 'ALL';
	public const  INHERIT = 'INHERIT';

	public static function getPresetWithUserRole(): Variants
	{
		$variants = new Variants();

		$variants->add(
			self::NONE,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_'),
			[
				'useAsEmptyInSection' => true,
				'useAsNothingSelectedInSubsection' => true,
				'conflictsWith' => [
					self::SELF,
					self::THIS_ROLE,
					self::DEPARTMENT,
					self::SUBDEPARTMENTS,
					self::OPEN,
					self::ALL,
					self::INHERIT,
				],
			]
		);
		$variants->add(
			self::SELF,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_A'),
			[
				'conflictsWith' => [
					self::INHERIT,
				],
			]
		);
		$variants->add(
			self::THIS_ROLE,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_B'),
			[
				'requires' => [
					self::SELF,
				],
				'conflictsWith' => [
					self::INHERIT,
				],
			]
		);
		$variants->add(
			self::DEPARTMENT,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_D'),
			[
				'requires' => [
					self::SELF,
				],
				'conflictsWith' => [
					self::INHERIT,
				],
			]
		);
		$variants->add(
			self::SUBDEPARTMENTS,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_F'),
			[
				'requires' => [
					self::SELF,
					self::DEPARTMENT,
				],
				'conflictsWith' => [
					self::INHERIT,
				],
			]
		);
		$variants->add(
			self::OPEN,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_O'),
			[
				'requires' => [
					self::SELF,
				],
				'conflictsWith' => [
					self::INHERIT,
				],
			]
		);
		$variants->add(
			self::ALL,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_X_MSGVER_1'),
			[
				'requires' => [
					self::SELF,
					self::THIS_ROLE,
					self::DEPARTMENT,
					self::SUBDEPARTMENTS,
					self::OPEN,
				],
				'conflictsWith' => [
					self::INHERIT,
					self::NONE,
				],
			]
		);

		$variants->add(
			self::INHERIT,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_INHERIT'),
			[
				'hideInSection' => true,
				'useAsEmptyInSubsection' => true,
				'secondary' => true,
				'conflictsWith' => [
					self::NONE,
					self::SELF,
					self::THIS_ROLE,
					self::OPEN,
					self::SUBDEPARTMENTS,
					self::DEPARTMENT,
					self::ALL,
				],
			]
		);

		return $variants;
	}

	public static function getPresetWithoutUserRole(): Variants
	{
		$variants = new Variants();

		$variants->add(
			self::NONE,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_'),
			[
				'useAsEmptyInSection' => true,
				'useAsNothingSelectedInSubsection' => true,
				'conflictsWith' => [
					self::SELF,
					self::DEPARTMENT,
					self::SUBDEPARTMENTS,
					self::OPEN,
					self::ALL,
					self::INHERIT,
				],
			]
		);
		$variants->add(
			self::SELF,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_A'),
			[
				'conflictsWith' => [
					self::INHERIT,
				],
			]
		);
		$variants->add(
			self::DEPARTMENT,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_D'),
			[
				'requires' => [
					self::SELF,
				],
				'conflictsWith' => [
					self::INHERIT,
				],
			]
		);
		$variants->add(
			self::SUBDEPARTMENTS,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_F'),
			[
				'requires' => [
					self::SELF,
					self::DEPARTMENT,
				],
				'conflictsWith' => [
					self::INHERIT,
				],
			]
		);
		$variants->add(
			self::OPEN,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_O'),
			[
				'requires' => [
					self::SELF,
					self::SUBDEPARTMENTS,
					self::DEPARTMENT,
				],
				'conflictsWith' => [
					self::INHERIT,
				],
			]
		);
		$variants->add(
			self::ALL,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_X_MSGVER_1'),
			[
				'requires' => [
					self::SELF,
					self::SUBDEPARTMENTS,
					self::DEPARTMENT,
					self::OPEN,
				],
				'conflictsWith' => [
					self::INHERIT,
					self::NONE,
				],
			]
		);

		$variants->add(
			self::INHERIT,
			(string)Loc::getMessage('CRM_SECURITY_ROLE_PERMS_TYPE_MULTI_INHERIT'),
			[
				'hideInSection' => true,
				'useAsEmptyInSubsection' => true,
				'secondary' => true,
				'conflictsWith' => [
					self::NONE,
					self::SELF,
					self::OPEN,
					self::SUBDEPARTMENTS,
					self::DEPARTMENT,
					self::ALL,
				],
			]
		);

		return $variants;
	}

	/**
	 * @param string $singleValue
	 *
	 * @return string[]
	 * @throws ArgumentOutOfRangeException
	 */
	public static function convertSingleToMultiValue(string $singleValue): array
	{
		switch ($singleValue)
		{
			case UserPermissions::PERMISSION_NONE:
				return [
					self::NONE,
				];
			case UserPermissions::PERMISSION_SELF:
				return [
					self::SELF,
				];
			case UserPermissions::PERMISSION_DEPARTMENT:
				return [
					self::SELF,
					self::DEPARTMENT,
				];
			case UserPermissions::PERMISSION_SUBDEPARTMENT:
				return [
					self::SELF,
					self::DEPARTMENT,
					self::SUBDEPARTMENTS,
				];
			case UserPermissions::PERMISSION_OPENED:
				return [
					self::SELF,
					self::DEPARTMENT,
					self::SUBDEPARTMENTS,
					self::OPEN,
				];
			case UserPermissions::PERMISSION_ALL:
				return [
					self::SELF,
					self::DEPARTMENT,
					self::SUBDEPARTMENTS,
					self::OPEN,
					self::ALL,
				];
		}

		throw new ArgumentOutOfRangeException(UserPermissions::PERMISSION_NONE, UserPermissions::PERMISSION_ALL);
	}

	public static function tryConvertMultiToSingleValue(array $multiValue): ?string
	{
		sort($multiValue, SORT_STRING);

		if (in_array(self::ALL, $multiValue, true))
		{
			return UserPermissions::PERMISSION_ALL;
		}
		if ($multiValue === [self::DEPARTMENT, self::OPEN, self::SELF, self::SUBDEPARTMENTS])
		{
			return UserPermissions::PERMISSION_OPENED;
		}
		if ($multiValue === [self::DEPARTMENT, self::SELF, self::SUBDEPARTMENTS])
		{
			return UserPermissions::PERMISSION_SUBDEPARTMENT;
		}
		if ($multiValue === [self::DEPARTMENT, self::SELF])
		{
			return UserPermissions::PERMISSION_DEPARTMENT;
		}
		if ($multiValue === [self::SELF])
		{
			return UserPermissions::PERMISSION_SELF;
		}
		if ($multiValue === [self::NONE])
		{
			return UserPermissions::PERMISSION_NONE;
		}

		return null; // some other combinations
	}
}
