<?php

namespace Bitrix\Crm\Relation;

abstract class RelationType
{
	public const CONVERSION = 'CONVERSION';
	public const BINDING = 'BINDING';

	public static function isDefined(string $relationType): bool
	{
		return in_array($relationType, static::getAll(), true);
	}

	public static function getAll(): array
	{
		return [
			static::CONVERSION,
			static::BINDING,
		];
	}
}
