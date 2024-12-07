<?php

namespace Bitrix\BIConnector\Superset\Updater;

use Bitrix\BIConnector\Superset\Logger\Logger;
use Bitrix\BIConnector\Superset\Updater\Versions\BaseVersion;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Result;

/**
 * Updates client data to actual version.
 */
final class ClientUpdater
{
	private const LAST_VERSION = 2;

	/**
	 * Get current version value.
	 *
	 * @return int
	 */
	public static function getCurrentVersion(): int
	{
		return (int)Option::get('biconnector', 'current_client_version', 0);
	}

	/**
	 * Does client have actual version.
	 *
	 * @return bool
	 */
	public static function isActualVersion(): bool
	{
		return self::getCurrentVersion() >= self::LAST_VERSION;
	}

	/**
	 * Sets current client version.
	 *
	 * @param int $version
	 *
	 * @return void
	 */
	private static function setCurrentVersion(int $version): void
	{
		Option::set('biconnector', 'current_client_version', $version);
	}

	/**
	 * Run updaters from ./Versions.
	 *
	 * @return Result
	 */
	public static function update(): Result
	{
		$result = new Result();
		$version = self::getCurrentVersion();

		while (!self::isActualVersion())
		{
			$version++;
			$className = __NAMESPACE__ . "\Versions\Version{$version}";
			if (class_exists($className) && is_subclass_of($className, BaseVersion::class))
			{
				$result = (new $className())->run();
				if (!$result->isSuccess())
				{
					break;
				}
			}

			self::setCurrentVersion($version);
		}

		if (!$result->isSuccess())
		{
			$loggerFields = [
				'message' => "Updater client error. Version {$version}",
			];
			Logger::logErrors($result->getErrors(), $loggerFields);
		}

		return $result;
	}
}
