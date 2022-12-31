<?php

namespace Bitrix\Crm\Requisite;

abstract class Country
{
	public const ID_UNDEFINED = 0;
	public const ID_RUSSIA = 1;
	public const ID_BELARUS = 4;
	public const ID_KAZAKHSTAN = 6;
	public const ID_UKRAINE = 14;
	public const ID_BRAZIL = 34;
	public const ID_GERMANY = 46;
	public const ID_COLOMBIA = 77;
	public const ID_POLAND = 110;
	public const ID_USA = 122;
	public const ID_FRANCE = 132;

	public static function getAllIds(): array
	{
		$reflection = new \ReflectionClass(static::class);

		$ids = [];
		foreach ($reflection->getReflectionConstants() as $const)
		{
			if (
				$const->isPublic()
				&& $const->getValue() !== static::ID_UNDEFINED
				&& (mb_strpos($const->getName(), 'ID_') !== false)
			)
			{
				$ids[] = $const->getValue();
			}
		}

		return $ids;
	}

	public static function isIdDefined(int $id): bool
	{
		return in_array($id, static::getAllIds(), true);
	}
}
