<?php

namespace Bitrix\Baas\UseCase\External\Request;

use \Bitrix\Baas;

class BillingDataSaveRequest extends BaseRequest
{
	public function __construct(
		public Baas\UseCase\External\Entity\Server $server,
		public Baas\UseCase\External\Entity\Client $client,
		public Baas\Repository\ServiceRepositoryInterface $serviceRepository,
		public Baas\Repository\PackageRepositoryInterface $packageRepository,
		public Baas\Repository\PurchaseRepositoryInterface $purchaseRepository,
		public readonly Baas\Model\EO_Service_Collection $services,
		public readonly Baas\Model\EO_ServiceAds_Collection $servicesAds,
		public readonly Baas\Model\EO_Package_Collection $packages,
		public readonly Baas\Model\EO_ServiceInPackage_Collection $servicesInPackages,
		public readonly Baas\Model\EO_Purchase_Collection $purchases,
		public readonly Baas\Model\EO_PurchasedPackage_Collection $purchasedPackages,
		public readonly Baas\Model\EO_ServiceInPurchasedPackage_Collection $servicesInPurchasedPackages,
	)
	{
		parent::__construct($server, $client);
	}
}
