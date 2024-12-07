<?php

namespace Bitrix\AI\Facade;

use Bitrix\AI\Config;
use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Application;
use Bitrix\Main\IO\File;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use CBitrix24;

class Bitrix24
{
	private const SHARD_ZONE = [
		'ru' => 'ru',
		'de' => 'de',
		'us' => 'us',
	];

	/**
	 * Shows that we should use Bitrix24 module in the project.
	 * @return bool
	 * @internal
	 */
	public static function shouldUseB24(): bool
	{
 		if (!ModuleManager::isModuleInstalled('bitrix24'))
		{
			return false;
		}

		$value = Config::getValue('use_bitrix24');
		if (empty($value))
		{
			return true;
		}

		return $value === 'Y';
	}

	/**
	 * Returns portal's license type.
	 *
	 * @return string|null
	 */
	public static function getLicenseType(): ?string
	{
		if (Loader::includeModule('bitrix24'))
		{
			return CBitrix24::getLicenseType() ?: null;
		}

		return null;
	}

	/**
	 * Returns true if portal has demo license.
	 *
	 * @return bool
	 */
	public static function isDemoLicense(): bool
	{
		if (Loader::includeModule('bitrix24'))
		{
			return CBitrix24::isDemoLicense();
		}

		return false;
	}

	/**
	 * Returns true if current portal (tariff) is free.
	 *
	 * @return bool
	 */
	public static function isFreeLicense(): bool
	{
		if (Loader::includeModule('bitrix24'))
		{
			return CBitrix24::isFreeLicense();
		}

		return true;
	}

	/**
	 * Returns code of portal's zone.
	 *
	 * @return string
	 */
	public static function getPortalZone(): string
	{
		return Application::getInstance()->getLicense()->getRegion() ?? 'ru';
	}

	/**
	 * @return bool
	 */
	public static function isWestZone(): bool
	{
		$zone = self::getPortalZone();

		return $zone !== 'ru' && $zone !== 'by' && $zone !== 'kz';
	}

	/**
	 * Returns zone of portal shard.
	 *
	 * @return string
	 */
	public static function getShardZone(): string
	{
		if (defined('BX24_MEMCACHE_HOST') && preg_match('/multi/', BX24_MEMCACHE_HOST)) {
			return self::SHARD_ZONE['ru'];
		} elseif (defined('BX24_MEMCACHE_HOST') && preg_match('/de/', BX24_MEMCACHE_HOST)) {
			return self::SHARD_ZONE['de'];
		}

		return self::SHARD_ZONE['us'];
	}

	/**
	 * Checks that feature is enabled within tariff.
	 *
	 * @param string $code Feature code.
	 * @return bool
	 */
	public static function isFeatureEnabled(string $code): bool
	{
		if (Loader::includeModule('bitrix24'))
		{
			return Feature::isFeatureEnabled($code);
		}

		return true;
	}

	/**
	 * Returns variable from Bitrix24 module.
	 * @param string $name Variable name.
	 * @return mixed
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getVariable(string $name): mixed
	{
		if (Loader::includeModule('bitrix24'))
		{
			return Feature::getVariable($name);
		}

		return null;
	}

	/**
	 * Returns Portal's Languages.
	 *
	 * @param string $dir Relative dir to languages file.
	 * @return array
	 */
	public static function getLanguages(string $dir = '/bitrix/templates/bitrix24'): array
	{
		$langFile = Application::getDocumentRoot();
		$langFile .= rtrim($dir, '/');
		$langFile .= '/languages.php';

		if (File::isFileExists($langFile))
		{
			include $langFile;

			if (!empty($b24Languages) && is_array($b24Languages))
			{
				$return = [];

				foreach ($b24Languages as $code => $lang)
				{
					if (isset($lang['NAME']))
					{
						$return[$code] = $lang['NAME'];
					}
				}

				return $return;
			}
		}

		return [];
	}

	/**
	 * Returns User's language in full naming ("English" for example).
	 *
	 * @return string
	 */
	public static function getUserLanguage(): string
	{
		$languages = self::getLanguages();
		$userLanguage = User::getUserLanguage();

		return $languages[$userLanguage] ?? $languages['en'];
	}
}
