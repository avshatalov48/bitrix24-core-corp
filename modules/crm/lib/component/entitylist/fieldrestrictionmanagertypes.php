<?php

namespace Bitrix\Crm\Component\EntityList;

class FieldRestrictionManagerTypes
{
	public const CLIENT = 'CLIENT_FIELDS_RESTRICTIONS';
	public const OBSERVERS = 'OBSERVERS_FIELD_RESTRICTIONS';
	public const ACTIVITY = 'ACTIVITY_FIELD_RESTRICTIONS';

	private static array $instanceMap = [
		self::CLIENT => ClientFieldRestrictionManager::class,
		self::OBSERVERS => ObserversFieldRestrictionManager::class,
		self::ACTIVITY => ActivityFieldRestrictionManager::class,
	];

	public static function createManagerByType(string $type): ?FieldRestrictionManagerBase
	{
		if (self::isValidType($type))
		{
			return new static::$instanceMap[$type];
		}

		return null;
	}

	private static function isValidType(string $type): bool
	{
		$supportedTypes = [
			self::CLIENT,
			self::OBSERVERS,
			self::ACTIVITY,
		];

		return in_array($type, $supportedTypes, true);
	}
}
