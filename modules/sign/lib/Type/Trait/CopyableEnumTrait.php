<?php

namespace Bitrix\Sign\Type\Trait;

use Bitrix\Sign\Attribute\Copyable;
use Bitrix\Sign\Exception\LogicException;

trait CopyableEnumTrait
{
	/**
	 * @return array<static>
	 */
	public static function listCopiable(): array
	{
		if (!is_subclass_of(static::class, \BackedEnum::class))
		{
			throw new LogicException('Class must be a subclass of UnitEnum');
		}

		$reflection = new \ReflectionEnum(static::class);
		$cases = $reflection->getCases();

		$copyable = [];
		foreach ($cases as $case)
		{
			$attribute = $case->getAttributes(Copyable::class);
			if ($attribute)
			{
				$copyable[] = $case->getValue();
			}
		}

		return $copyable;
	}
}