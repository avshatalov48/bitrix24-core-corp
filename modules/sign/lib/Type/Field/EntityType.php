<?php

namespace Bitrix\Sign\Type\Field;

final class EntityType
{
	public const MEMBER = 'member';
	public const DOCUMENT = 'document';

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::MEMBER,
			self::DOCUMENT,
		];
	}
}