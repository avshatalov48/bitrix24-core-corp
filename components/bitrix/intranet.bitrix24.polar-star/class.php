<?

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class IntranetPolarStarComponent extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	protected $lastShowTimeOption = 'release_polar_star:last_show_time';
	protected $sliderModeCntOption = 'release_polar_star:slider_mode_cnt';
	protected $stopChangeDefaultThemeOption = 'release_polar_star:stop_change_default_theme';
	protected $deactivatedOption = 'release_polar_star:deactivated';

	public function __construct($component = null)
	{
		parent::__construct($component);
	}

	public function executeComponent()
	{
		if (!ModuleManager::isModuleInstalled('bitrix24') || (defined('ERROR_404') && ERROR_404 ==='Y'))
		{
			return;
		}

		$this->arResult['show_time'] = false;
		if ($this->shouldShow())
		{
			$this->arResult['show_time'] = true;
			$this->arResult['options'] = $this->getOptions();

			// First set a new theme
			if ($this->getSliderModeCnt() === -1)
			{
				$this->incSliderModeCnt();
				if ($this->setDefaultTheme())
				{
					if (Loader::includeModule('intranet'))
					{
						\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache();
					}

					LocalRedirect($GLOBALS['APPLICATION']->getCurUri());
				}
			}

			// Show Slider for the first hit
			if ($this->getSliderModeCnt() === 0)
			{
				$this->arResult['mode'] = 'slider';
				$this->incSliderModeCnt();
			}
			else if ($this->getSliderModeCnt() === 1)
			{
				// Repeat after one day
				$lastShowTime = $this->getLastShowTime();
				if ((time() - $lastShowTime) > 24 * 3600)
				{
					$this->arResult['mode'] = 'slider';
					$this->incSliderModeCnt();
				}
			}
		}

		$this->includeComponentTemplate();
	}

	protected function getZone(): ?string
	{
		if (Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::getPortalZone();
		}

		return null;
	}

	protected static function createDate(string $date): int
	{
		$time = \DateTime::createFromFormat('d.m.Y H:i', $date, new \DateTimeZone('Europe/Moscow'));

		// $time = new \Bitrix\Main\Type\DateTime($date, 'd.m.Y');
		// $time->setTime(0, 0);
		// $diff = \CTimeZone::GetOffset();
		// $time->add(($diff > 0 ? '-' : '') . 'PT' . abs($diff) . 'S');

		return $time->getTimestamp();
	}

	protected function shouldShow(): bool
	{
		$release = $this->getRelease();
		if (!$release)
		{
			return false;
		}

		$now = time();
		$startDate = static::createDate($release['releaseDate']);
		$endDate = $startDate + 24 * 3600 * 14;
		if ($now < $startDate || $now > $endDate)
		{
			return false;
		}

		if (Loader::includeModule('extranet') && \CExtranet::isExtranetSite())
		{
			return false;
		}

		if ($this->isDeactivated())
		{
			return false;
		}

		if (Loader::includeModule('bitrix24'))
		{
			$creationTime = intval(\CBitrix24::getCreateTime());
			if ($creationTime === 0 || $creationTime > $startDate)
			{
				return false;
			}
		}

		$spotlight = new \Bitrix\Main\UI\Spotlight('release_polar_star');
		$spotlight->setUserTimeSpan(3600 * 24 * 14);
		$isAvailable = $spotlight->isAvailable();
		if (!$isAvailable)
		{
			$this->deactivate();
		}

		return $isAvailable;
	}

	protected function getOptions(): array
	{
		return [
			'url' => $this->getUrl(),
			'zone' => $this->getZone(),
		];
	}

	protected function getRelease(): ?array
	{
		$zone = $this->getZone();
		if (in_array($zone, ['ua', 'ur']))
		{
			return null;
		}

		$releaseMap = [
			'ru' => ['https://www.bitrix24.ru/promo/the_north_star/', '11.11.2022 11:00'],
			'by' => ['https://www.bitrix24.by/promo/the_north_star/', '11.11.2022 11:00'],
			'kz' => ['https://www.bitrix24.kz/promo/the_north_star/', '11.11.2022 11:00'],
			'uk' => ['https://www.bitrix24.uk/promo/the_north_star_release/', '18.11.2022 13:00'],
			'in' => ['https://www.bitrix24.in/promo/the_north_star_release/', '18.11.2022 11:00'],
			'eu' => ['https://www.bitrix24.eu/promo/the_north_star_release/', '18.11.2022 13:00'],
			'en' => ['https://www.bitrix24.com/promo/the_north_star_release/', '18.11.2022 13:00'],
			'br' => ['https://www.bitrix24.com.br/promo/the_north_star_release/', '18.11.2022 17:00'],
			'la' => ['https://www.bitrix24.es/promo/the_north_star_release/', '18.11.2022 14:00'],
			'mx' => ['https://www.bitrix24.mx/promo/the_north_star_release/', '18.11.2022 18:00'],
			'co' => ['https://www.bitrix24.co/promo/the_north_star_release/', '18.11.2022 18:00'],
			'tr' => ['https://www.bitrix24.com.tr/promo/the_north_star_release/', '18.11.2022 12:00'],
			'fr' => ['https://www.bitrix24.fr/promo/the_north_star_release/', '18.11.2022 12:00'],
			'it' => ['https://www.bitrix24.it/promo/the_north_star_release/', '18.11.2022 16:00'],
			'pl' => ['https://www.bitrix24.pl/promo/the_north_star_release/', '18.11.2022 16:00'],
			'de' => ['https://www.bitrix24.de/promo/the_north_star_release/', '18.11.2022 15:00'],
			'cn' => ['https://www.bitrix24.com/promo/the_north_star_release/', '18.11.2022 11:00'],
			'vn' => ['https://www.bitrix24.com/promo/the_north_star_release/', '18.11.2022 11:00'],
			'jp' => ['https://www.bitrix24.com/promo/the_north_star_release/', '18.11.2022 11:00'],
			'id' => ['https://www.bitrix24.com/promo/the_north_star_release/', '18.11.2022 11:00'],
		];

		$zone = isset($releaseMap[$zone]) ? $zone : 'en';

		return [
			'zone' => $zone,
			'url' => $releaseMap[$zone][0],
			'releaseDate' => $releaseMap[$zone][1],
		];
	}

	protected function getUrl(): string
	{
		$release = $this->getRelease();
		if (!$release)
		{
			return '';
		}

		$host = \Bitrix\Main\Context::getCurrent()->getRequest()->getHttpHost();
		$salt = md5('POLAR' . $host . 'STAR');
		if (Loader::includeModule('bitrix24'))
		{
			$salt =\CBitrix24::requestSign($salt);
		}

		$url = new \Bitrix\Main\Web\Uri($release['url']);
		$url->addParams([
			'host' => $host,
			'auth' => $salt,
		]);

		return $url->getUri();
	}

	public function closeAction()
	{
		// just for analytics
	}

	public function showAction()
	{
		$this->setLastShowTime();
	}

	public function deactivateAction()
	{
		$this->deactivate();
	}

	protected function isDeactivated(): bool
	{
		return \CUserOptions::getOption('intranet', $this->deactivatedOption) === 'Y';
	}

	protected function deactivate()
	{
		\CUserOptions::setOption('intranet', $this->deactivatedOption, 'Y');
	}

	protected function activate()
	{
		\CUserOptions::deleteOption('intranet', $this->deactivatedOption);
	}

	protected function getLastShowTime(): ?int
	{
		$time = \CUserOptions::getOption('intranet', $this->lastShowTimeOption, null);

		return $time === null ? $time : (int)$time;
	}

	protected function setLastShowTime(): void
	{
		\CUserOptions::setOption('intranet', $this->lastShowTimeOption, time());
	}

	protected function getSliderModeCnt(): ?int
	{
		return (int)\CUserOptions::getOption('intranet', $this->sliderModeCntOption, -1);
	}

	protected function incSliderModeCnt(): void
	{
		\CUserOptions::setOption('intranet', $this->sliderModeCntOption, $this->getSliderModeCnt() + 1);
	}

	protected function setDefaultTheme(): bool
	{
		if (!Loader::includeModule('intranet'))
		{
			return false;
		}

		$newDefaultThemeId = 'light:milky-way';
		$theme = new \Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker('bitrix24', 's1');

		$currentDefaultThemeId = $theme->getDefaultThemeId();
		$currentThemeId = $theme->getCurrentThemeId();

		// Try to change the default theme only for the first time
		if ($currentDefaultThemeId !== $newDefaultThemeId && !$theme->isCustomThemeId($currentDefaultThemeId))
		{
			if (Option::get('intranet', $this->stopChangeDefaultThemeOption, 'N') !== 'Y')
			{
				$db = \CUserOptions::getList([], ['CATEGORY' => 'intranet', 'NAME' => $this->lastShowTimeOption]);
				if (!$db->fetch()) // fix for the 0162782
				{
					$theme->setDefaultTheme($newDefaultThemeId);
				}
			}
		}

		Option::set('intranet', $this->stopChangeDefaultThemeOption, 'Y');

		if ($currentThemeId !== $newDefaultThemeId && !$theme->isCustomThemeId($currentThemeId))
		{
			if ($currentDefaultThemeId !== $currentThemeId)
			{
				$theme->setCurrentThemeId($newDefaultThemeId);
			}

			return true;
		}

		return false;
	}

	public function configureActions()
	{
		// TODO: Implement configureActions() method.
	}
}