<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External;

use Bitrix\Baas;
use Bitrix\Baas\Internal\Service\MarketplaceService;

class BillingDataGet extends BaseClientAction
{
	protected string $languageId = 'en';
	protected MarketplaceService $marketplaceService;

	public function __construct(
		Baas\UseCase\External\Request\BillingDataGetRequest $request,
		?MarketplaceService $marketplaceService = null,
	)
	{
		parent::__construct($request);
		$this->languageId = $request->languageId;
		$this->marketplaceService = $marketplaceService ?? MarketplaceService::createInstance();
	}

	protected function run(): Response\BillingDataGetResult
	{
		$data = [
			'hostKey' => $this->hostKey,
			'languageId' => $this->languageId,
			'portalZone' => $this->server->getRegionId(),
			'billingCurrency' => $this->server->getBillingCurrency(),
		];

		$result = $this
			->getSender()
			->performRequest('get', $data)
		;

		$rawData = $result->getData();

		if (!isset($rawData['services']) && !isset($rawData['packages']))
		{
			throw new Exception\BaasControllerRespondsInWrongFormatException(['services', 'packages']);
		}

		$servicesRaw = $rawData['services'] ?? [];
		$servicesAdsRaws = isset($rawData['adsInfo']) ? [Baas\Entity\Service::CODE_MAIN_SERVICE => $rawData['adsInfo']] : [];
		foreach ($servicesRaw as $datum)
		{
			$ads = $datum['adsInfo'] ?? null;
			if (!empty($ads) && is_array($ads))
			{
				$servicesAdsRaws[$datum['code']] = $ads;
			}
		}

		[
			$packages, $servicesInPackages,
			$purchases,
			$purchasedPackages, $servicesInPurchasedPackages,
		] = $this->parsePackageArray($rawData['packages'] ?? []);

		$services = $this->makeServiceArray($servicesRaw);

		$result = new Response\BillingDataGetResult(
			services: $services,
			servicesAds: $this->makeServiceAdsArray($servicesAdsRaws),
			packages: $packages,
			servicesInPackages: $servicesInPackages,
			purchases: $purchases,
			purchasedPackages: $purchasedPackages,
			servicesInPurchasedPackages: $servicesInPurchasedPackages,
		);

		$result->setData(['rawData' => $rawData]);

		return $result;
	}

	private function makeServiceArray(array $servicesRaw): Baas\Model\EO_Service_Collection
	{
		$services = Baas\Model\ServiceTable::createCollection();

		foreach ($servicesRaw as $datum)
		{
			if ($datum['code'] === Baas\Entity\Service::CODE_MAIN_SERVICE)
			{
				continue;
			}
			$localizedInfo = reset($datum['languageInfo']);
			$service = Baas\Model\ServiceTable::createObject()
				->setCode($datum['code'])
				->setTitle($datum['title'] ?? $localizedInfo['title'] ?? $datum['code'])
				->setIconClass($datum['iconClass'] ?? '')
				->setIconColor($datum['iconColor'] ?? '')
				->setIconStyle($datum['iconStyle'] ?? '')
				->setActiveSubtitle($datum['activeSubtitle'] ?? $localizedInfo['activeSubtitle'] ?? '')
				->setInactiveSubtitle($datum['inactiveSubtitle'] ?? $localizedInfo['inactiveSubtitle'] ?? '')
				->setDescription($datum['description'] ?? $localizedInfo['description'] ?? '')
				->setFeaturePromotionCode($datum['featurePromotionCode'] ?? '')
				->setHelperCode($datum['helperCode'] ?? '')
//				->setAdvertisingStrategy($datum['advertisingStrategy'] ?? '') // Set later after detection. Here for clarity
				->setRenewable($datum['renewable'] ?? 'N')
				->setCurrentValue($datum['currentValue'] ?? 0)
				->setLanguageInfo($datum['languageInfo'] ?? '')
			;
			$service->setAdvertisingStrategy(
				$this
					->marketplaceService
					->detectAdvertisingStrategy($service, $datum['advertisingStrategy'] ?? '')
					->value
			);

			$this->marketplaceService->adaptFeaturePromotionAndHelperCodes($service);

			$services->add($service);
		}

		return $services;
	}

	private function makeServiceAdsArray(array $servicesAdsRaws): Baas\Model\EO_ServiceAds_Collection
	{
		$servicesAds = Baas\Model\ServiceAdsTable::createCollection();

		foreach ($servicesAdsRaws as $serviceId => $ads)
		{
			foreach ($ads as $languageId => $adsDatum)
			{
				$serviceAds = (new Baas\Model\EO_ServiceAds())
					->setServiceCode($serviceId)
					->setLanguageId($languageId)
					->setTitle($adsDatum['title'])
					->setSubtitle($adsDatum['subtitle'])
					->setSubtitleDescription($adsDatum['subtitleDescription'])
					->setFeaturePromotionCode($adsDatum['featurePromotionCode'] ?? '')
					->setHelperCode($adsDatum['helperCode'] ?? '')
				;
				if (!empty($adsDatum['iconUrl']))
				{
					$serviceAds->setIconUrl($adsDatum['iconUrl']);
					$serviceAds->setIconFileType($adsDatum['iconType'] ?? '');
				}
				if (!empty($adsDatum['videoUrl']))
				{
					$serviceAds->setVideoUrl($adsDatum['videoUrl']);
					$serviceAds->setVideoFileType($adsDatum['videoType'] ?? '');
				}
				$servicesAds->add($serviceAds);
			}
		}

		return $servicesAds;
	}

	private function parsePackageArray(array $packagesRaw): array
	{
		$packages = new Baas\Model\EO_Package_Collection();
		$servicesInPackage = new Baas\Model\EO_ServiceInPackage_Collection();

		$purchases = new Baas\Model\EO_Purchase_Collection();
		$purchasedPacks = new Baas\Model\EO_PurchasedPackage_Collection();
		$servicesInPurchasedPack = new Baas\Model\EO_ServiceInPurchasedPackage_Collection();

		foreach ($packagesRaw as $datum)
		{
			$localizedInfo = reset($datum['languageInfo']);
			$package = Baas\Model\PackageTable::createObject()
				->setCode($datum['code'])
				->setTitle(empty($datum['title']) ? ($localizedInfo['title'] ?? '') : $datum['title'])
				->setIconClass($datum['iconClass'] ?? '')
				->setIconColor($datum['iconColor'] ?? '')
				->setIconStyle($datum['iconStyle'] ?? '')
				->setPurchaseUrl($datum['purchaseUrl'] ?? '')
				->setDescription(empty($datum['description']) ? ($localizedInfo['description'] ?? '') : $datum['description'])
				->setPriceValue($datum['priceValue'])
				->setPriceCurrencyId($datum['priceCurrencyId'])
				->setPriceDescription($datum['priceDescription'] ?? '')
				->setActive($datum['sellable'] ?? 'N')
				->setFeaturePromotionCode($datum['featurePromotionCode'] ?? '')
				->setHelperCode($datum['helperCode'] ?? '')
//				->setDistributionStrategy($datum['distributionStrategy'] ?? '') // Set later after adding services but here for clarity
				->setLanguageInfo($datum['languageInfo'] ?? '')
			;
			$packages->add($package);

			foreach ($datum['servicesInPack'] as $serviceCode => $serviceMaxValueInPackage)
			{
				$serviceInPackage = Baas\Model\ServiceInPackageTable::createObject()
					->setServiceCode($serviceCode)
					->setValue($serviceMaxValueInPackage)
					->setPackageCode($package->getCode())
				;
				$servicesInPackage->add($serviceInPackage);
				$package->addToServiceInPackage($serviceInPackage);
			}

			$package->setDistributionStrategy(
				$this->marketplaceService->detectDistributionStrategy(
					$package,
					$datum['distributionStrategy'] ?? null,
				)->value,
			);
			// delete serviceInPackage temporary collection to avoid problems during next iteration
			$package->unsetServiceInPackage();

			if (!empty($datum['purchaseInfo']) && is_array($datum['purchaseInfo']))
			{
				BillingBalanceParse::parseAndCollectPurchase(
					$package,
					$purchases,
					$purchasedPacks,
					$servicesInPurchasedPack,
					$datum['purchaseInfo'],
				);
			}
		}

		return [$packages, $servicesInPackage, $purchases, $purchasedPacks, $servicesInPurchasedPack];
	}
}
