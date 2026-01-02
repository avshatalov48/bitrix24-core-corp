<?php

namespace Bitrix\Baas\Internal\Trait;

trait SingletonConstructor
{
	protected static $instance;

	public static function getInstance(): static
	{
		if (!isset(static::$instance))
		{
			static::$instance = new static();
		}

		return static::$instance;
	}
}
