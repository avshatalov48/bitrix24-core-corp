<?php

namespace Bitrix\SignMobile\Type;

final class NotificationType
{
	//It is necessary to sign the document or postpone the signing.
	public const PUSH_FOUND_FOR_SIGNING = 1;

	//You need to confirm the signing of the document from your mobile device.
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
