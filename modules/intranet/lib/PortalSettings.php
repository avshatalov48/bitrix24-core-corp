<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage bitrix24
 * @copyright 2001-2023 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Main;
use Bitrix\Intranet;
use Bitrix\Main\IO\File;

class PortalSettings
{
	private static array $instances = [];
	protected string $siteName;
	protected string $siteTitle;
	protected string $siteLogo24 = '24';
	protected ?string $settingsPath = null;
	private ?array $desktopDownloadLinks = null;
	private Portal\PortalLogo $siteLogo;

	protected function __construct()
	{
		$this->init();
	}

	protected function init()
	{
		$this->siteTitle = $this->getSettingsService()->titleSettings()->getTitle();
		$this->siteLogo24 = $this->getSettingsService()->logo24Settings()->getLogo24();
		$this->siteLogo = new Intranet\Portal\PortalLogo($this->getSettingsService());
	}

	public function getName(): string
	{
		if (!isset($this->siteName))
		{
			$this->siteName = $this->getSettingsService()->nameSettings()->getName();
		}

		return $this->siteName;
	}

	public function setName(string $siteName): void
	{
		$this->getSettingsService()->nameSettings()->setName($siteName);
	}

	public function getTitle(): string
	{
		return $this->siteTitle;
	}

	public function setTitle(string $siteTitle): void
	{
		$this->getSettingsService()->titleSettings()->setTitle($siteTitle);
	}

	public function getLogo24(): string
	{
		return $this->siteLogo24;
	}

	public function getLogo(): ?array
	{
		return $this->siteLogo->getLogo();
	}

	public function getSettingsUrl(): string
	{
		if (!$this->settingsPath)
		{
			$this->settingsPath = '/configs/';

			if (!File::isFileExists($_SERVER['DOCUMENT_ROOT'] . $this->settingsPath . '/index.php'))
			{
				$this->settingsPath = '/settings/configs/';
			}
		}

		return $this->settingsPath;
	}

	public function getDefaultLogo(): array
	{
		if (LANGUAGE_ID === 'ru')
		{
			$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();

			if ($region === 'by')
			{
				return [
					'white' => '/bitrix/images/intranet/logo/bitrix24/by/bitrix24-logo-by-white.svg',
					'black' => '/bitrix/images/intranet/logo/bitrix24/by/bitrix24-logo-by-black.svg',
				];
			}
			else
			{
				return [
					'white' => '/bitrix/images/intranet/logo/bitrix24/ru/bitrix24-logo-ru-white.svg',
					'black' => '/bitrix/images/intranet/logo/bitrix24/ru/bitrix24-logo-ru-black.svg',
				];
			}
		}

		return [
			'white' => '/bitrix/images/intranet/logo/bitrix24/en/bitrix24-logo-en-white.svg',
			'black' => '/bitrix/images/intranet/logo/bitrix24/en/bitrix24-logo-en-black.svg',
		];
	}

	final public function getDesktopDownloadLinks(): array
	{
		if (isset($this->desktopDownloadLinks))
		{
			return $this->desktopDownloadLinks;
		}

		$region = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();

		if (in_array($region, ['ru', 'by']))
		{
			$this->desktopDownloadLinks = [
				'windows' => 'https://repos.1c-bitrix.ru/b24/bitrix24_desktop_ru.exe',
				'macos' => 'https://repos.1c-bitrix.ru/b24/bitrix24_desktop_ru.dmg',
				'linuxDeb' => 'https://repos.1c-bitrix.ru/b24/bitrix24_desktop_ru.deb',
				'linuxRpm' => 'https://repos.1c-bitrix.ru/b24/bitrix24_desktop_ru.rpm',
				'msi' => 'https://repos.1c-bitrix.ru/b24/bitrix24_desktop_ru.msi',
				'macosArm' => 'https://repos.1c-bitrix.ru/b24/bitrix24_macos_arm_ru.dmg',
			];
		}
		else
		{
			$this->desktopDownloadLinks = [
				'windows' => 'https://dl.bitrix24.com/b24/bitrix24_desktop.exe',
				'macos' => 'https://dl.bitrix24.com/b24/bitrix24_desktop.dmg',
				'linuxDeb' => 'https://dl.bitrix24.com/b24/bitrix24_desktop.deb',
				'linuxRpm' => 'https://dl.bitrix24.com/b24/bitrix24_desktop.rpm',
				'msi' => 'https://dl.bitrix24.com/b24/bitrix24_desktop.msi',
				'macosArm' => 'https://dl.bitrix24.com/b24/bitrix24_macos_arm.dmg',
			];
		}

		return $this->desktopDownloadLinks;
	}

	/**
	 * @param string $userAgent from \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getUserAgent()
	 * @return string
	 */
	final public function getDesktopDownloadLinkByUserAgent(string $userAgent): string
	{
		$links = $this->getDesktopDownloadLinks();

		if (mb_stripos($userAgent, 'Windows') !== false)
		{
			return $links['windows'];
		}

		if (
			mb_stripos($userAgent, 'Macintosh') !== false
			|| mb_stripos($userAgent, 'Mac OS X') !== false
		)
		{
			return $links['macos'];
		}

		if (mb_stripos($userAgent, 'Linux') !== false)
		{
			if (
				mb_stripos($userAgent, 'Fedora') !== false
				|| mb_stripos($userAgent, 'CentOS') !== false
				|| mb_stripos($userAgent, 'Red Hat') !== false
			)
			{
				return $links['linuxRpm'];
			}

			return $links['linuxDeb'];
		}

		return $links['windows'];
	}

	final public static function getInstance(): static
	{
		if (!isset(static::$instances[static::class]))
		{
			self::$instances[static::class] = new static();
		}

		return self::$instances[static::class];
	}

	protected function getSettingsService(): Intranet\Service\PortalSettings
	{
		return Intranet\Service\PortalSettings::getInstance();
	}
}
