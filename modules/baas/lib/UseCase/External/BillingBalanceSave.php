<?php

namespace Bitrix\Baas\UseCase\External;

use \Bitrix\Main;
use \Bitrix\Baas;

class BillingBalanceSave extends BaseAction
{
	protected Baas\Repository\ServiceRepositoryInterface $serviceRepository;
	protected Baas\Repository\PackageRepositoryInterface $packageRepository;
	protected Baas\Repository\PurchaseRepositoryInterface $purchaseRepository;

	protected Baas\Model\EO_Purchase_Collection $purchases;
	protected Baas\Model\EO_PurchasedPackage_Collection $purchasedPackages;
	protected Baas\Model\EO_ServiceInPurchasedPackage_Collection $servicesInPurchasedPackages;

	public function __construct(protected Request\BillingBalanceSaveRequest $request)
	{
		parent::__construct($request);

		$this->serviceRepository = $request->serviceRepository;
		$this->packageRepository = $request->packageRepository;
		$this->purchaseRepository = $request->purchaseRepository;

		$this->purchases = $this->request->purchases;
		$this->purchasedPackages = $this->request->purchasedPackages;
		$this->servicesInPurchasedPackages = $this->request->servicesInPurchasedPackages;
	}

	protected function run(): Main\Result
	{
		$result = new Main\Result();

		$this->purchaseRepository->update(
			$this->purchases,
			$this->purchasedPackages,
			$this->servicesInPurchasedPackages,
		);

		$this->purchaseRepository->recalculateBalance();

		return $result;
	}
}
