<?php

namespace Bitrix\Sign\Type;

final class EntityType
{
	public const DOCUMENT = 0;
	public const MEMBER = 1;

	/**
	 * @return list<self::*>
	 */
	public function getAll(): array
	{
		return [
			self::DOCUMENT,
			self::MEMBER,
		];
	}
}
