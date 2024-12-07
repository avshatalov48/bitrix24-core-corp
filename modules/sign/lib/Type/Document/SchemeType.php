<?php

namespace Bitrix\Sign\Type\Document;

final class SchemeType
{
	public const DEFAULT = 'default';
	public const ORDER = 'order';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::DEFAULT,
			self::ORDER,
		];
	}

	public static function isValid(string $scheme): bool
	{
		return in_array($scheme, self::getAll(), true);
	}
}