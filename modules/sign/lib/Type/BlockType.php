<?php

namespace Bitrix\Sign\Type;

final class BlockType
{
	public const TEXT = 'text';
	public const MULTILINE_TEXT = 'multilineText';
	public const IMAGE = 'image';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::TEXT,
			self::MULTILINE_TEXT,
			self::IMAGE,
		];
	}

	public static function isValid(string $type): bool
	{
		return in_array($type, self::getAll(), true);
	}
}