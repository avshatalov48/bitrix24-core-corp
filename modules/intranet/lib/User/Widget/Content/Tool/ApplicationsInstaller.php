<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content\Tool;

use Bitrix\Intranet;
use Bitrix\Intranet\Portal;
use Bitrix\Intranet\Internal;
use Bitrix\Intranet\User;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\UserAgent\platform;

class ApplicationsInstaller extends BaseTool
{
	private bool $mobileInstalled;
	private bool $desktopInstalled;
	private platform $platform;

	public function __construct(Intranet\User $user)
	{
		parent::__construct($user);
		$platformUsageChecker = new Internal\Service\Platform\UsageChecker();
		$request = Application::getInstance()->getContext()->getRequest();
		$userAgent = (string)$request->getUserAgent();
		$this->platform = platform::fromUserAgent($userAgent);
		$this->mobileInstalled = $platformUsageChecker->isMobileUsedByUserId($this->user->getId());
		$this->desktopInstalled = $this->platform->isDesktop() && $platformUsageChecker->isPlatformUsedApplication($this->platform, $this->user->getId());
	}

	public static function isAvailable(User $user): bool
	{
		return true;
	}

	public function getConfiguration(): array
	{
		$request = Application::getInstance()->getContext()->getRequest();
		$userAgent = (string)$request->getUserAgent();

		$configuration = [
			'title' => $this->getTitle(),
			'desktop' => [
				'installLink' => Portal::getInstance()->getSettings()->getDesktopDownloadLinkByUserAgent($userAgent),
				'buttonName' => Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_APPLICATION_INSTALLER_BUTTON_NAME'),
				'installed' => $this->desktopInstalled,
			],
			'mobile' => [
				'installed' => $this->mobileInstalled,
			],
		];

		if ($this->mobileInstalled && $this->desktopInstalled)
		{
			$configuration['menu'] = [
				[
					'title' => Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_APPLICATION_INSTALLER_MENU_DESKTOP'),
					'type' => 'desktop',
				],
				[
					'title' => Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_APPLICATION_INSTALLER_MENU_MOBILE'),
					'type' => 'mobile',
				],
			];
		}

		return $configuration;
	}

	public function getName(): string
	{
		return 'applicationsInstaller';
	}

	private function getTitle(): string
	{
		if ($this->desktopInstalled && $this->mobileInstalled)
		{
			return Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_APPLICATION_INSTALLER_ALL_INSTALLED');
		}

		if ($this->platform->isLinux())
		{
			return $this->desktopInstalled
				? Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_APPLICATION_INSTALLER_INSTALLED_LINUX')
				: Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_APPLICATION_INSTALLER_SHOULD_INSTALL_LINUX');
		}

		if ($this->platform === platform::Macos)
		{
			return $this->desktopInstalled
				? Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_APPLICATION_INSTALLER_INSTALLED_MACOS')
				: Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_APPLICATION_INSTALLER_SHOULD_INSTALL_MACOS');
		}

		return $this->desktopInstalled
			? Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_APPLICATION_INSTALLER_INSTALLED_WINDOWS')
			: Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_APPLICATION_INSTALLER_SHOULD_INSTALL_WINDOWS');
	}
}
