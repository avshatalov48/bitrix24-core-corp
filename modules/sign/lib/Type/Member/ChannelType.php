<?php

namespace Bitrix\Sign\Type\Member;

final class ChannelType
{
	public const PHONE = 'PHONE';
	public const EMAIL = 'EMAIL';
	public const IDLE = 'IDLE';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::PHONE,
			self::EMAIL,
			self::IDLE,
		];
	}

	public static function isValid(string $type): bool
	{
		return in_array($type, static::getAll(), true);
	}
}
