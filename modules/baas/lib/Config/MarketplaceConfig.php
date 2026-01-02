<?php

declare(strict_types=1);

namespace Bitrix\Baas\Config;

class MarketplaceConfig extends Config
{
	protected const DEFAULT_REGIONS = ['ru', 'kz', 'by'];

	protected function getModuleId(): string
	{
		return 'baas';
	}

	public function getBaasRegions(): array
	{
		if ($regions = $this->get('regions'))
		{
			$regions = json_decode($regions);
		}

		return is_array($regions) ? $regions : self::DEFAULT_REGIONS;
	}

	public function getServicesAdvertisedByMarket(): array
	{
		if ($services = $this->get('services_on_market'))
		{
			$services = json_decode($services);
		}

		return is_array($services) ? $services : [];
	}
}
