<?php

namespace Bitrix\Baas\UseCase\External;

use \Bitrix\Main;
use \Bitrix\Baas;

class BillingDataPurge extends BaseAction
{
	protected Baas\Repository\ServiceRepositoryInterface $serviceRepository;
	protected Baas\Repository\PackageRepositoryInterface $packageRepository;
	protected Baas\Repository\PurchaseRepositoryInterface $purchaseRepository;

	public function __construct(
		Request\BillingDataPurgeRequest $request,
	)
	{
		parent::__construct($request);

		$this->serviceRepository = $request->serviceRepository;
		$this->packageRepository = $request->packageRepository;
		$this->purchaseRepository = $request->purchaseRepository;
	}

	protected function run(): Main\Result
	{
		$this->serviceRepository->purge();

		$this->packageRepository->purge();

		$this->purchaseRepository->purge();

		return new Main\Result();
	}
}
