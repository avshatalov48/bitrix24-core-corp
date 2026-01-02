<?php

namespace Bitrix\Baas\UseCase\External;

use \Bitrix\Main;
use \Bitrix\Baas;

class BillingDataSave extends BaseAction
{
	protected Baas\Repository\ServiceRepositoryInterface $serviceRepository;
	protected Baas\Repository\PackageRepositoryInterface $packageRepository;
	protected Baas\Repository\PurchaseRepositoryInterface $purchaseRepository;

	protected Baas\Model\EO_Service_Collection $services;
	protected Baas\Model\EO_ServiceAds_Collection $servicesAds;
	protected Baas\Model\EO_Package_Collection $packages;
	protected Baas\Model\EO_ServiceInPackage_Collection $servicesInPackages;
	protected Baas\Model\EO_Purchase_Collection $purchases;
	protected Baas\Model\EO_PurchasedPackage_Collection $purchasedPackages;
	protected Baas\Model\EO_ServiceInPurchasedPackage_Collection $servicesInPurchasedPackages;

	public function __construct(
		protected Request\BillingDataSaveRequest $request,
	)
	{
		parent::__construct($request);
		$this->serviceRepository = $request->serviceRepository;
		$this->packageRepository = $request->packageRepository;
		$this->purchaseRepository = $request->purchaseRepository;

		$this->services = $this->request->services;
		$this->servicesAds = $this->request->servicesAds;
		$this->packages = $this->request->packages;
		$this->servicesInPackages = $this->request->servicesInPackages;
		$this->purchases = $this->request->purchases;
		$this->purchasedPackages = $this->request->purchasedPackages;
		$this->servicesInPurchasedPackages = $this->request->servicesInPurchasedPackages;
	}

	protected function run(): Main\Result
	{
		$result = new Main\Result();

		if ($this->services->isEmpty())
		{
			$this->client->turnOff();

			return $result;
		}

		if ($this->client->isTurnedOn() !== true)
		{
			$this->client->turnOn();
		}

		$this->serviceRepository->save(
			$this->services,
			$this->servicesAds,
		);

		$this->packageRepository->save(
			$this->packages,
			$this->servicesInPackages,
		);

		$this->purchaseRepository->save(
			$this->purchases,
			$this->purchasedPackages,
			$this->servicesInPurchasedPackages,
		);

		$this->purchaseRepository->recalculateBalance();

		return $result;
	}
}
