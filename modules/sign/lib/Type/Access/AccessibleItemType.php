<?php

namespace Bitrix\Sign\Type\Access;

final class AccessibleItemType
{
	public const DOCUMENT = 'document';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::DOCUMENT
		];
	}
}