<?php

declare(strict_types=1);

namespace Bitrix\Baas\Internal\Service;

use Bitrix\Baas;
use Bitrix\Baas\Internal\Entity\Enum\ServiceAdvertisingStrategy;
use Bitrix\Baas\Internal\Entity\Enum\PackageDistributionStrategy;

class MarketplaceService
{
	protected static MarketplaceService $instance;

	function __construct(protected Baas\Config\MarketplaceConfig $config)
	{
	}

	public function detectAdvertisingStrategy(
		Baas\Model\EO_Service $service,
		?string $proposedStrategy = null,
	): ServiceAdvertisingStrategy
	{
		if (
			$proposedStrategy !== null &&
			($detectedStrategy = ServiceAdvertisingStrategy::tryFrom($proposedStrategy))
		)
		{
			return $detectedStrategy;
		}

		$servicesDistributedByMarketPreferably = $this->config->getServicesAdvertisedByMarket();
		if (in_array($service->getCode(), $servicesDistributedByMarketPreferably))
		{
			return ServiceAdvertisingStrategy::BY_MARKET;
		}

		return ServiceAdvertisingStrategy::BY_BAAS;
	}

	public function adaptFeaturePromotionAndHelperCodes(Baas\Model\EO_Service $service): void
	{
		//TODO: remove to the baascontroller
		if ($service->getAdvertisingStrategy() === ServiceAdvertisingStrategy::BY_MARKET->value
			&& $service->getCode() === 'ai_copilot_token')
		{
			$service->setFeaturePromotionCode('limit_subscription_market_access_buy_marketplus');
			$service->setHelperCode('limit_subscription_market_access_buy_marketplus');
		}
	}

	public function detectDistributionStrategy(
		Baas\Model\EO_Package $package,
		?string $proposedStrategy = null,
	): PackageDistributionStrategy
	{
		if (
			$proposedStrategy !== null &&
			($detectedStrategy = PackageDistributionStrategy::tryFrom($proposedStrategy))
		)
		{
			return $detectedStrategy;
		}

		$servicesDistributedByMarketPreferably = $this->config->getServicesAdvertisedByMarket();
		$servicesInPackage = $package->getServiceInPackage()->getServiceCodeList();

		$goodToSellByMarket = array_intersect($servicesInPackage, $servicesDistributedByMarketPreferably);
		if ($goodToSellByMarket == $servicesInPackage)
		{
			return PackageDistributionStrategy::BY_MARKET;
		}

		return PackageDistributionStrategy::BY_BAAS;
	}

	public static function createInstance(): static
	{
		return new static(
			new Baas\Config\MarketplaceConfig(),
		);
	}
}
