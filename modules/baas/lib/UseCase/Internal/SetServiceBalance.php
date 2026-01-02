<?php

namespace Bitrix\Baas\UseCase\Internal;

use \Bitrix\Main;
use \Bitrix\Baas;

class SetServiceBalance
{
	public function __construct(
		protected Baas\Repository\ServiceRepositoryInterface $serviceRepository,
		protected Baas\Repository\PurchaseRepositoryInterface $purchaseRepository,
	)
	{
	}

	public function __invoke(Request\SetServiceBalanceRequest $request): Main\Result
	{
		$service = $request->service;
		$stateNumber = $request->stateNumber;
		$serviceInPurchasedPackages = $request->serviceInPurchasedPackages;

		$this->purchaseRepository->updateByStateNumber(
			$serviceInPurchasedPackages,
			$stateNumber,
		);

		if ($newServiceData = $this->serviceRepository->findByCode($service->getCode()))
		{
			$service->getData()?->setCurrentValue($newServiceData->getCurrentValue());
			$service->getData()?->setStateNumber($newServiceData?->getStateNumber());
		}

		(new Main\Event('baas', 'onServiceBalanceChanged', ['service' => $service]))->send();

		return new Main\Result();
	}
}
