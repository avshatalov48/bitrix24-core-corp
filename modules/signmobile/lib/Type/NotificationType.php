<?php

namespace Bitrix\SignMobile\Type;

final class NotificationType
{
	public const PUSH_FOUND_FOR_SIGNING = 1;
	public const PUSH_RESPONSE_SIGNING = 2;

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::PUSH_FOUND_FOR_SIGNING,
			self::PUSH_RESPONSE_SIGNING,
		];
	}
}
