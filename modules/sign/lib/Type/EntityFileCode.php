<?php

namespace Bitrix\Sign\Type;

final class EntityFileCode
{
	public const SIGNED = 0;
	public const PRINT_VERSION = 1;
	/**
	 * @return list<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::SIGNED,
			self::PRINT_VERSION,
		];
	}
}
