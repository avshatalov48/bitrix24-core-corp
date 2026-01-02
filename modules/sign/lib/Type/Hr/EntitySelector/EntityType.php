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
	case Document = 4;
	case SignersList = 5;

	public static function fromEntityIdAndType(string $entityId, string $entityType): EntityType
	{
		return match ($entityType)
		{
			'user' => self::User,
			'structure-node' => str_ends_with($entityId, ':F') ? self::FlatDepartment : self::Department,
			'sign-document' => self::Document,
			'signers-list' => self::SignersList,
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
			\Bitrix\Sign\Type\Member\EntityType::DOCUMENT => self::Document,
			\Bitrix\Sign\Type\Member\EntityType::SIGNERS_LIST => self::SignersList,
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

	public function isDocument(): bool
	{
		return $this === self::Document;
	}

	public function isSignersList(): bool
	{
		return $this === self::SignersList;
	}

	public function getMemberEntityType(string $initialEntityType): string
	{
		return match ($this)
		{
			self::FlatDepartment => \Bitrix\Sign\Type\Member\EntityType::DEPARTMENT_FLAT,
			self::Department => \Bitrix\Sign\Type\Member\EntityType::DEPARTMENT,
			self::User => \Bitrix\Sign\Type\Member\EntityType::USER,
			self::Document => \Bitrix\Sign\Type\Member\EntityType::DOCUMENT,
			self::SignersList => \Bitrix\Sign\Type\Member\EntityType::SIGNERS_LIST,
			default => $initialEntityType,
		};
	}
	
	public static function getAll(): array
	{
		return [
			self::User->value => 'user',
			self::Department->value => 'structure-node',
			self::FlatDepartment->value => 'structure-node',
			self::Document->value => 'sign-document',
			self::SignersList->value => 'signers-list',
		];
	}
}
