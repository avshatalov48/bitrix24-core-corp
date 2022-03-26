<?php

namespace Bitrix\Crm\Settings;

class CounterSettings
{
	private $isEnabled = null;

	private static $current = null;

	public static function getCurrent()
	{
		if (self::$current === null)
		{
			self::$current = new self();
		}

		return self::$current;
	}

	function __construct()
	{
		$this->isEnabled = new BooleanSetting('is_counters_enabled', true);
	}

	public function isEnabled(): bool
	{
		return (bool)$this->isEnabled->get();
	}
}
