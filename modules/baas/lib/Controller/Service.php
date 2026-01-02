<?php

namespace Bitrix\Baas\Controller;

use \Bitrix\Main;
use \Bitrix\Baas;
use \Bitrix\Baas\Internal;

class Service extends Main\Engine\Controller
{

	public function getAllAction(): Main\Engine\Response\Json
	{
		return $this->getAction(Baas\Entity\Service::CODE_MAIN_SERVICE);
	}

	public function getAction(string $code): Main\Engine\Response\Json
	{
		$baas = Baas\Baas::getInstance();
		$service = $baas->getService($code);
		$packageProvider = Baas\Public\Provider\PackageProvider::create();
		$packages = $code === Baas\Entity\Service::CODE_MAIN_SERVICE
			? $packageProvider->getDistributedByBaas()
			: $packageProvider->getDistributedByBaasForService($service);

		$data = [
			'service' => $service,
			'adsInfo' => $this->buildAdsBlock($service->getAdsInfo()),
			'packages' => $this->buildPackages($packages),
		];

		return Main\Engine\Response\AjaxJson::createSuccess(
			$data,
		);
	}

	public function buildAdsBlock(?Baas\Model\EO_ServiceAds $serviceAds = null): ?array
	{
		$data = null;
		if ($serviceAds instanceof Baas\Model\EO_ServiceAds)
		{
			$data = [
				'title' => $serviceAds->getTitle(),
				'subtitle' => $serviceAds->getSubtitle(),
				'subtitleDescription' => $serviceAds->getSubtitleDescription(),
				'featurePromotionCode' => $serviceAds->getFeaturePromotionCode(),
				'iconUrl' => $serviceAds->getIconUrl(),
				'videoUrl' => $serviceAds->getVideoUrl(),
				'videoFileType' => $serviceAds->getVideoFileType(),
			];
		}

		return $data;
	}

	public function buildPackages(iterable $packages): array
	{
		/* @var Baas\Entity\Package $package*/
		$result = [];
		foreach ($packages as $package)
		{
			$result[] = $package->setLanguage(LANGUAGE_ID);
		}

		return $result;
	}
}
