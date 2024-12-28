<?php

namespace Bitrix\Sign\Type\Hr\EntitySelector;

use Bitrix\Sign\Item\Member;

/**
 * Class for processing entityType obtained from entity selector
 */
enum EntityType: int
{
	case Unknown = 0;
	case User = 1;
	case Department = 2;
	case FlatDepartment = 3;

	public static function fromEntityIdAndType(string $entityId, string $entityType): EntityType
	{
		return match ($entityType)
		{
			'user' => self::User,
			'department' => str_ends_with($entityId, ':F') ? self::FlatDepartment : self::Department,
			default => self::Unknown,
		};
	}

	public static function fromMember(Member $member): EntityType
	{
		return match ($member->entityType)
		{
			\Bitrix\Sign\Type\Member\EntityType::USER => self::User,
			\Bitrix\Sign\Type\Member\EntityType::DEPARTMENT => self::Department,
			\Bitrix\Sign\Type\Member\EntityType::DEPARTMENT_FLAT => self::FlatDepartment,
			default => self::Unknown,
		};
	}

	public function isDepartment(): bool
	{
		return $this === self::Department || $this === self::FlatDepartment;
	}

	public function isUser(): bool
	{
		return $this === self::User;
	}
}
