<?php

declare(strict_types=1);

namespace Bitrix\Baas\Internal\Integration\Bitrix24;

use Bitrix\Main;
use Bitrix\Baas;
use Bitrix\Bitrix24;

class License implements Baas\Contract\License
{
	protected Baas\Config\MarketplaceConfig $config;
	protected ?Bitrix24\License $license = null;
	protected bool $active = false;

	/**
	 * @param Baas\Config\MarketplaceConfig $config
	 * @throws Main\LoaderException
	 */
	public function __construct(Baas\Config\MarketplaceConfig $config)
	{
		$this->config = $config;

		if (Main\Loader::includeModule('bitrix24'))
		{
			$this->license = Bitrix24\License::getCurrent();
			$this->active = $this->license->isActive() && (
					\CBitrix24::IsLicensePaid()
					|| \CBitrix24::IsNfrLicense()
					|| \CBitrix24::IsDemoLicense() && !\CBitrix24::isLicenseNeverPayed()
				);
		}
	}

	public function isAvailable(): bool
	{
		return $this->license instanceof Bitrix24\License;
	}

	public function isActive(): bool
	{
		return $this->active;
	}

	public function isBaasAvailable(): bool
	{
		if ($this->license instanceof Bitrix24\License)
		{
			return in_array($this->license->getRegion(), $this->config->getBaasRegions());
		}

		return false;
	}

	public function isSellableToAll(): bool
	{
		return Main\Config\Option::get('bitrix24', 'buy_tariff_by_all', 'Y') !== 'N';
	}
}
