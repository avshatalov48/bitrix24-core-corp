<?php

namespace Bitrix\StaffTrack\Trait;

trait Singleton
{
	protected static $instance = null;

	protected function __construct(){}
	public function __wakeup(){}
	protected function __clone(){}

	public static function getInstance(): static
	{
		if (static::$instance === null)
		{
			static::$instance = new static();
		}

		return static::$instance;
	}
}