<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Access\Install\AgentInstaller;

use Bitrix\HumanResources\Contract\AgentInstaller;

class InstallerFactory
{
	/**
	 * @return array<int, string>
	 */
	public static function getVersionMap(): array
	{
		return [
			-1 => PermissionReInstaller::class,
			0 => DefaultPermissionInstaller::class,
			1 => DeputyAndHRPermissionInstaller::class,
			2 => InviteAndFirePermissionInstaller::class,
			3 => TeamRolesInstaller::class,
			4 => TeamRoleViewAllCompanyInstaller::class,
			5 => UpdateAdminRoleInstaller::class,
			6 => TeamRolesInstallerV2::class,
			7 => CommunicationInstaller::class,
			8 => CommunicationInstallerV2::class,
			9 => CommunicationInstallerV3::class,
			10 => DepartmentSettingsInstaller::class,
		];
	}

	public static function getInstaller(int $version): ?AgentInstaller
	{
		$installerClass = self::getVersionMap()[$version] ?? null;

		if ($installerClass && class_exists($installerClass))
		{
			return new $installerClass();
		}

		return null;
	}
}