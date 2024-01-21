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

class PortalSettings
{
	private static array $instances = [];
	protected string $siteId = 's1';
	protected string $siteName;
	protected string $siteTitle;
	protected string $siteLogo24 = '24';
	protected ?int $siteLogoId = null;
	protected ?int $siteLogoIdForRetina = null;

	protected function __construct(?string $siteId = null)
	{
		if (is_string($siteId))
		{
			$this->siteId = $siteId;
		}
		else if (
			defined('SITE_ID')
			&& Main\Config\Option::get('main', '~wizard_id', null, SITE_ID) === 'portal'
		)
		{
			$this->siteId = SITE_ID;
		}
		$this->init();
	}

	protected function init()
	{
		$this->siteTitle = Main\Config\Option::get('bitrix24', 'site_title', null, $this->siteId) ??
			Main\Config\Option::get('bitrix24', 'site_title', null) ?? $this->getTitleByDefault();

		// bitrix24 as module id here is for the migrated CPs
		if (Main\Config\Option::get('bitrix24', 'logo24show', 'Y', $this->siteId) === 'N')
		{
			$this->siteLogo24 = '';
		}

		$this->siteLogoIdForRetina = Main\Config\Option::get('bitrix24', 'client_logo_retina', null, $this->siteId)
			?? Main\Config\Option::get('bitrix24', 'client_logo_retina', null);

		$this->siteLogoId = Main\Config\Option::get('bitrix24', 'client_logo', null, $this->siteId)
			?? Main\Config\Option::get('bitrix24', 'client_logo', null) ?? $this->siteLogoIdForRetina;
	}

	public function getName(): string
	{
		if (!isset($this->siteName))
		{
			$siteName = Main\Config\Option::get('main', 'site_name', '', $this->siteId);
			if (strlen($siteName) <= 0)
			{
				$siteName = Main\Config\Option::get('main', 'site_name');
			}
			$this->siteName = strlen($siteName) > 0 ? $siteName : Main\Context::getCurrent()->getServer()->getServerName();
		}

		return $this->siteName;
	}

	public function setName(string $siteName): Main\Result
	{
		$result = new Main\Result();
		$siteName = trim($siteName);
		$siteName  = $siteName <> '' ? $siteName : Main\Context::getCurrent()->getServer()->getServerName();
		$currentSiteName = $this->getName();

		if ($siteName <> '' && $siteName !== $currentSiteName)
		{
			$this->siteName = $siteName;
			Main\Config\Option::set('main', 'site_name', $siteName, $this->siteId);
			$result->setData(['value' => $siteName]);
		}

		return $result;
	}

	public function setLogo24(string $showLogo24 = 'Y')
	{
		$result = new Main\Result();

		if ($this->canCurrentUserEditTitle())
		{
			$logo24 = $showLogo24 === 'Y' ? '24' : '';

			if ($this->siteLogo24 !== $logo24)
			{
				$this->siteLogo24 = $logo24;
				Main\Config\Option::set('bitrix24', 'logo24show', $showLogo24, $this->siteId);
			}

			$result->setData(['value' => $logo24]);
		}

		return $result;
	}

	public function getTitle(): string
	{
		$this->siteTitle = strlen($this->siteTitle) > 0 ? $this->siteTitle : $this->getTitleByDefault();

		return $this->siteTitle;
	}

	protected function getTitleByDefault(): string
	{
		return $this->getName();
	}

	public function canCurrentUserEditTitle(): bool
	{
		return Intranet\CurrentUser::get()->isAdmin();
	}

	public function setTitle(string $siteTitle)
	{
		$result = new Main\Result();
		$siteTitle = trim($siteTitle);
		$siteTitle = $siteTitle <> '' ? $siteTitle : $this->getTitleByDefault();
		$currentSiteTitle = $this->getTitle();

		if ($siteTitle !== $currentSiteTitle)
		{
			$this->siteTitle = $siteTitle;
			// bitrix24 as a module_id here is for a historical reason
			Main\Config\Option::set('bitrix24', 'site_title', $siteTitle, $this->siteId);
			$result->setData(['value' => $siteTitle]);
		}

		return $result;
	}

	public function canCurrentUserEditName(): bool
	{
		return Intranet\CurrentUser::get()->isAdmin();
	}

	public function getLogo24(): string
	{
		return $this->siteLogo24;
	}

	public function canCurrentUserEditLogo24(): bool
	{
		return Intranet\CurrentUser::get()->isAdmin();
	}

	public function setLogo(int $logo, ?int $logo2x = null)
	{
		$this->removeLogo();
		$this->siteLogoId = $logo;
		Main\Config\Option::set('bitrix24', 'client_logo', $this->siteLogoId, $this->siteId);
		if (!empty($logo2x))
		{
			$this->siteLogoIdForRetina = (int) $logo2x;
			Main\Config\Option::set('bitrix24', 'client_logo_retina', $this->siteLogoIdForRetina, $this->siteId);
		}
	}

	public function removeLogo(): void
	{
		\CFile::Delete($this->siteLogoId);
		\CFile::Delete($this->siteLogoIdForRetina);

		$this->siteLogoId = null;
		$this->siteLogoIdForRetina = null;

		Main\Config\Option::delete('bitrix24', ['name' => 'client_logo']);
		Main\Config\Option::delete('bitrix24', ['name' => 'client_logo_retina']);
	}

	public function getLogo(): ?array
	{
		$result = null;

		if ($this->siteLogoId && $image = \CFile::_GetImgParams($this->siteLogoId))
		{
			$result = [
				'id' => $this->siteLogoId,
				'src' => $image['SRC'],
				'width' => $image['WIDTH'],
				'height' => $image['HEIGHT'],
			];

			if ($this->siteLogoIdForRetina && $image = \CFile::_GetImgParams($this->siteLogoIdForRetina))
			{
				$result['srcset'] = $image['SRC'];
			}
		}

		return $result;
	}

	public function canCurrentUserEditLogo(): bool
	{
		return Intranet\CurrentUser::get()->isAdmin();
	}

	public function getSettingsUrl(): string
	{
		return '/configs/';
	}

	final public static function getInstance(): static
	{
		if (!isset(static::$instances[static::class]))
		{
			self::$instances[static::class] = new static();
		}

		return self::$instances[static::class];
	}
}