<?php

namespace Bitrix\Sign\Type;

final class BlockSection
{
	public const PARTNER = 'partner';
	public const GENERAL = 'general';
	public const INITIATOR = 'initiator';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::PARTNER,
			self::GENERAL,
			self::INITIATOR,
		];
	}
}