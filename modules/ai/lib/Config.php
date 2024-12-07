<?php

namespace Bitrix\AI;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Config\Option;
use CUserOptions;

class Config
{
	private const MODULE_ID = 'ai';

	/**
	 * Returns config value from settings.php.
	 *
	 * @param string $code Config code.
	 * @return mixed
	 */
	private static function getSettingsValue(string $code): mixed
	{
		static $configuration = null;

		if ($configuration === null)
		{
			$configuration = [
				'values' => Configuration::getValue(self::MODULE_ID),
			];
		}

		return $configuration['values'][$code] ?? null;
	}

	/**
	 * Returns config value from options.php.
	 *
	 * @param string $code Config code.
	 * @return string
	 */
	private static function getOptionsValue(string $code): string
	{
		return Option::get(self::MODULE_ID, $code);
	}

	/**
	 * Returns configuration value by code.
	 *
	 * @param string $code Config code.
	 * @return mixed
	 */
	public static function getValue(string $code): mixed
	{
		if ($value = self::getSettingsValue($code))
		{
			return $value;
		}
		if ($value = self::getOptionsValue($code))
		{
			return $value;
		}

		return null;
	}

	/**
	 * Returns user option value by code.
	 *
	 * @param string $code Config code.
	 * @return mixed
	 */
	public static function getPersonalValue(string $code): mixed
	{
		return CUserOptions::getOption('ai', 'config', [])[$code] ?? null;
	}

	/**
	 * Saves option value by code. Only in main's options.
	 *
	 * @param string $code Option code.
	 * @param string $value Option value.
	 * @return void
	 */
	public static function setOptionsValue(string $code, string $value): void
	{
		Option::set('ai', $code, $value);
	}

	/**
	 * Saves user option value by code.
	 *
	 * @param string $code Option code.
	 * @param string $value Option value.
	 * @return void
	 */
	public static function setPersonalValue(string $code, string $value): void
	{
		$config = CUserOptions::getOption('ai', 'config', []);
		if (!is_array($config))
		{
			$config = [];
		}

		$config[$code] = $value;

		CUserOptions::setOption('ai', 'config', $config);
	}

	/**
	 * Saves user option value by code for all users
	 *
	 * @param string $code Option code.
	 * @param string $value Option value.
	 * @return void
	 */
	public static function setPersonalValueForAll(string $code, string $value): void
	{
		$res = \CUserOptions::GetList(
			["ID" => "ASC"],
			[
				'CATEGORY' => 'ai',
				'NAME' => 'config',
			]
		);

		while ($option = $res->fetch())
		{
			$config = unserialize($option["VALUE"], ['allowed_classes' => false]);
			if (!is_array($config))
			{
				$config = [];
			}

			$config[$code] = $value;

			CUserOptions::setOption('ai', 'config', $config, false, $option['USER_ID']);
		}
	}
}
