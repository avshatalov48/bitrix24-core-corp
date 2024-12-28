<?php

namespace Bitrix\Sign\Type\Member;

final class EntityType
{
	public const CONTACT = 'contact';
	public const COMPANY = 'company';
	public const USER = 'user';
	public const DEPARTMENT = 'department';
	public const DEPARTMENT_FLAT = 'department_flat';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::CONTACT,
			self::COMPANY,
			self::USER,
			self::DEPARTMENT,
			self::DEPARTMENT_FLAT
		];
	}

	public static function getCrmTypes(): array
	{
		return [
			self::CONTACT,
			self::COMPANY
		];
	}

	public static function getEntitySelectorTypes(): array
	{
		return [
			self::DEPARTMENT,
			self::DEPARTMENT_FLAT,
			self::USER,
		];
	}

	public static function isCrmEntity(?string $entity): bool
	{
		return in_array($entity, self::getCrmTypes(), true);
	}

	public static function isDepartment($entityType): bool
	{
		return $entityType === self::DEPARTMENT || $entityType === self::DEPARTMENT_FLAT;
	}
}
