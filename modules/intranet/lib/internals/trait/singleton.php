<?php

namespace Bitrix\Intranet\Internals\Trait;

trait Singleton
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
