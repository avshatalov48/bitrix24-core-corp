<?php

namespace Bitrix\Mobile\Dto;

use Bitrix\Mobile\Dto\Caster\Caster;
use Bitrix\Mobile\Dto\Caster\IntCaster;
use Bitrix\Mobile\Dto\Caster\FloatCaster;
use Bitrix\Mobile\Dto\Caster\StringCaster;
use Bitrix\Mobile\Dto\Caster\BoolCaster;
use Bitrix\Mobile\Dto\Caster\ObjectCaster;

final class Type
{
	public static function int(): IntCaster
	{
		return new IntCaster();
	}

	public static function float(): FloatCaster
	{
		return new FloatCaster();
	}

	public static function string(): StringCaster
	{
		return new StringCaster();
	}

	public static function bool(): BoolCaster
	{
		return new BoolCaster();
	}

	public static function object(string $type): ObjectCaster
	{
		return new ObjectCaster($type);
	}

	/**
	 * @param string|Caster $type
	 * @return Caster
	 */
	public static function collection($type): Caster
	{
		if ($type instanceof Caster)
		{
			$type->markAsCollection();
			return $type;
		}
		else
		{
			return new ObjectCaster($type, true);
		}
	}
}