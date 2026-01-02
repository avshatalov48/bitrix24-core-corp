<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Service\Platform;

use Bitrix\Intranet\Util;
use Bitrix\Main\Web\UserAgent\Platform;

class UsageChecker
{
	private ?array $appsConfig = null;

	public function isMobileUsedByUserId(int $userId): bool
	{
		$apps = $this->getAppsConfig($userId);

		return ($apps['APP_IOS_INSTALLED'] ?? false) || ($apps['APP_ANDROID_INSTALLED'] ?? false);
	}

	public function isDesktopApplicationUsedByUserId(int $userId): bool
	{
		$apps = $this->getAppsConfig($userId);

		return ($apps['APP_WINDOWS_INSTALLED'] ?? false)
			|| ($apps['APP_MAC_INSTALLED'] ?? false)
			|| ($apps['APP_LINUX_INSTALLED'] ?? false);
	}

	public function isPlatformUsedApplication(Platform $platform, int $userId): bool
	{
		if ($platform->isMobile())
		{
			return $this->isMobileUsedByUserId($userId);
		}

		$apps = $this->getAppsConfig($userId);

		if ($platform === Platform::Windows)
		{
			return $apps['APP_WINDOWS_INSTALLED'] ?? false;
		}

		if ($platform === Platform::Macos)
		{
			return $apps['APP_MAC_INSTALLED'] ?? false;
		}

		if ($platform->isLinux())
		{
			return $apps['APP_LINUX_INSTALLED'] ?? false;
		}

		return false;
	}

	private function getAppsConfig(int $userId): array
	{
		if (!$this->appsConfig)
		{
			$this->appsConfig = Util::getAppsInstallationConfig($userId);
		}

		return $this->appsConfig;
	}
}
