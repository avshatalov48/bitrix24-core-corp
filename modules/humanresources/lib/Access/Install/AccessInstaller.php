<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\Install;
use Bitrix\HumanResources\Access\Install\AgentInstaller\InstallerFactory;
use Bitrix\HumanResources\Access\Permission\PermissionDictionary;
use Bitrix\HumanResources\Access\Permission\PermissionVariablesDictionary;
use Bitrix\HumanResources\Access\Role\RoleDictionary;
use Bitrix\Main;
use Bitrix\Main\Application;

class AccessInstaller
{
	private const MODULE_ID = 'humanresources';
	public const STRUCTURE_ACCESS_VERSION_KEY = 'structure_access_version';
	private const LOCK_NAME = 'humanresources_access_install';


	public static function install(): string
	{
		$connection = Application::getInstance()->getConnection();
		if (!$connection->lock(self::LOCK_NAME))
		{
			return '';
		}

		try
		{
			$accessInstaller = (new AccessInstaller());
			$currentVersion = $accessInstaller->getAccessVersion();

			while ($installer = InstallerFactory::getInstaller($currentVersion))
			{
				$installer->install();
				$accessInstaller->setActualAccessVersion(++$currentVersion);
			}
		}
		finally
		{
			$connection->unlock(self::LOCK_NAME);
		}

		return '';
	}

	//region Default Access Agent
	public static function installAgent(): string
	{
		return self::install();
	}
	//endregion

	//region Old Access Agents
	public static function reInstallAgent(int $fromVersion = -1): string
	{
		(new AccessInstaller())->setActualAccessVersion($fromVersion);

		return self::install();
	}

	public static function installDeputyAndHR(): string
	{
		return self::install();
	}

	public static function installTeamRoles(): string
	{
		return self::install();
	}

	/**
	 * @deprecated
	 * @return string
	 */
	public static function reInstallInviteRuleAgent():string
	{
		return '';
	}

	/**
	 * @deprecated
	 * @return string
	 */
	public static function setInviteAndFirePermissionsAgent(): string
	{
		return self::install();
	}
	//endregion

	//region Access Version Actions
	public function setActualAccessVersion(int $version): void
	{
		Main\Config\Option::set(self::MODULE_ID, self::STRUCTURE_ACCESS_VERSION_KEY, $version);
	}

	public function getAccessVersion(): int
	{
		return (int)Main\Config\Option::get(self::MODULE_ID, self::STRUCTURE_ACCESS_VERSION_KEY, 0);
	}
	//endregion
}