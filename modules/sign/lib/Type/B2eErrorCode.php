<?php

namespace Bitrix\Sign\Type;

class B2eErrorCode
{
	public const EXPIRED = 'expired';
	public const REQUEST_ERROR = 'request_error';
	public const SNILS_NOT_FOUND = 'snils_not_found';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::EXPIRED,
			self::REQUEST_ERROR,
			self::SNILS_NOT_FOUND,
		];
	}
}
