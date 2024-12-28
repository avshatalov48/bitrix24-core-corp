<?php

namespace Bitrix\Sign\Type\Access;

final class AccessibleItemType
{
	public const DOCUMENT = 'document';
	public const TEMPLATE = 'template';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::DOCUMENT,
			self::TEMPLATE,
		];
	}
}
