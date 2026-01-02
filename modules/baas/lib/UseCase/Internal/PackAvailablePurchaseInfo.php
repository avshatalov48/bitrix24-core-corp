<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\Internal;

use \Bitrix\Baas;

class PackAvailablePurchaseInfo
{
	public function __construct(protected Baas\Repository\PurchaseRepository $purchaseRepository)
	{
	}

	public function __invoke(Request\PackAvailablePurchaseInfoRequest $request): Response\PackAvailablePurchaseInfoResult
	{
		$packageCode = $request->packageCode;
		$onlyEnabled = $request->onlyEnabled;

		if ($onlyEnabled !== true)
		{
			$data = $this->purchaseRepository->getActualPurchasesDistributedByBaas();
		}
		elseif (is_string($packageCode) && $packageCode !== '')
		{
			$data = $this->purchaseRepository->getAvailableByPackageCode($packageCode, $request->includeDepleted);
		}
		else
		{
			$data = $this->purchaseRepository->getAvailablePurchasesDistributedByBaas();
		}

		return (new Response\PackAvailablePurchaseInfoResult())
			->setData(
				$this->createPurchaseDTOs($data),
			)
		;
	}

	protected function createPurchaseDTOs(
		Baas\Model\EO_ServiceInPurchasedPackage_Collection $servicesInPurchasedPackageCollection,
	): array
	{
		$purchases = [];

		foreach ($servicesInPurchasedPackageCollection as $serviceInPurchasedPackage)
		{
			$purchaseCode = $serviceInPurchasedPackage->getPurchasedPackage()->getPurchaseCode();
			$purchasedPackageCode = $serviceInPurchasedPackage->getPurchasedPackageCode();

			$purchases[$purchaseCode] ??= new Baas\Model\Dto\Purchase($purchaseCode);

			$purchase = $purchases[$purchaseCode];
			$purchase->getPurchasedPackages()[$purchasedPackageCode] ??= new Baas\Model\Dto\PurchasedPackage(
				$serviceInPurchasedPackage->getPurchasedPackageCode(),
				$serviceInPurchasedPackage->getPurchasedPackage()->getPackageCode(),
				$serviceInPurchasedPackage->getPurchasedPackage()->getPurchaseCode(),
				$serviceInPurchasedPackage->getPurchasedPackage()->getStartDate(),
				$serviceInPurchasedPackage->getPurchasedPackage()->getExpirationDate(),
				$serviceInPurchasedPackage->getPurchasedPackage()->getActive() === 'Y',
				$serviceInPurchasedPackage->getPurchasedPackage()->getActual(),
			);
			$purchase
				->getPurchasedPackages()[$purchasedPackageCode]
				->getPurchasedServices()[$serviceInPurchasedPackage->getServiceCode()]
				= new Baas\Model\Dto\PurchasedServiceInPackage(
					$serviceInPurchasedPackage->getServiceCode(),
					$purchase->getPurchasedPackages()[$purchasedPackageCode],
					$serviceInPurchasedPackage->getServicesInPackage()->getValue(),
					$serviceInPurchasedPackage->getCurrentValue(),
				)
			;
		}

		return $purchases;
	}
}
