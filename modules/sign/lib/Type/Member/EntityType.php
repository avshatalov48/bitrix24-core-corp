<?php

namespace Bitrix\Sign\Type\Member;

final class EntityType
{
	public const CONTACT = 'contact';
	public const COMPANY = 'company';
	public const USER = 'user';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::CONTACT,
			self::COMPANY,
			self::USER
		];
	}

	public static function getCrmTypes(): array
	{
		return [
			self::CONTACT,
			self::COMPANY
		];
	}

	public static function isCrmEntity(?string $entity): bool
	{
		return in_array($entity, self::getCrmTypes(), true);
	}
}