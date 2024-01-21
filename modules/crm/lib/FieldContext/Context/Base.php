<?php

namespace Bitrix\Crm\FieldContext\Context;

abstract class Base
{
	abstract public static function getId(): int;
	abstract public static function getName(): string;
	abstract public static function getIconSvg(): string;

	public function toArray(): array
	{
		return [
			'id' => static::getId(),
			'name' => static::getName(),
			'iconSvg' => static::getIconSvg(),
		];
	}
}
