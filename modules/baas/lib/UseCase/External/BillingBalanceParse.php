<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\External;

use \Bitrix\Main;
use \Bitrix\Baas;

class BillingBalanceParse extends BaseClientAction
{
	protected array $rawBalance;

	public function __construct(
		Baas\UseCase\External\Request\BillingBalanceParseRequest $request,
	)
	{
		parent::__construct($request);
		$this->rawBalance = $request->data;
	}

	protected function run(): Response\BillingBalanceParseResult
	{
		[
			$purchases,
			$purchasedPackages,
			$servicesInPurchasedPackages,
		] = $this->parsePackageArray($this->rawBalance['packages'] ?? []);

		return new Response\BillingBalanceParseResult(
			purchases: $purchases,
			purchasedPackages: $purchasedPackages,
			servicesInPurchasedPackages: $servicesInPurchasedPackages,
		);
	}

	public static function parseAndCollectPurchase(
		Baas\Model\EO_Package $package,
		Baas\Model\EO_Purchase_Collection $purchases,
		Baas\Model\EO_PurchasedPackage_Collection $purchasedPacks,
		Baas\Model\EO_ServiceInPurchasedPackage_Collection $servicesInPurchasedPack,
		array $purchaseInfo,
	): void
	{
		foreach ($purchaseInfo as $purchaseDatum)
		{
			$purchasedPackageCode = $purchaseDatum['packageId'];
			$purchaseCode = $purchaseDatum['purchaseId'] ?? $purchasedPackageCode;
			if (empty($purchases->getByPrimary($purchaseCode)))
			{
				$purchases->add(Baas\Model\PurchaseTable::createObject()
					->setCode($purchaseCode)
					->setPurchaseUrl($purchaseDatum['purchaseUrl'] ?? ''),
				);
			}

			$purchasedPack = Baas\Model\PurchasedPackageTable::createObject()
				->setCode($purchasedPackageCode) // This is a unique identifier for the purchased package
				->setPackageCode($package->getCode())
				->setPurchaseCode($purchaseCode) // This is a bill ID to group purchased packages
				->setStartDate(Main\Type\Date::createFromTimestamp($purchaseDatum['startDate'] ?? time()))
				->setExpirationDate(Main\Type\Date::createFromTimestamp($purchaseDatum['expirationDate'] ?? time()))
			;
			$purchasedPacks->add($purchasedPack);
			foreach ($purchaseDatum['servicesInPurchase'] as $serviceCode => $serviceCurrentValue)
			{
				$servicesInPurchasedPack->add(Baas\Model\ServiceInPurchasedPackageTable::createObject()
					->setPurchasedPackageCode($purchasedPack->getCode())
					->setCurrentValue($serviceCurrentValue)
					->setServiceCode($serviceCode),
				);
			}
		}
	}

	private function parsePackageArray(array $packagesRaw): array
	{
		$purchases = new Baas\Model\EO_Purchase_Collection();
		$purchasedPacks = new Baas\Model\EO_PurchasedPackage_Collection();
		$servicesInPurchasedPack = new Baas\Model\EO_ServiceInPurchasedPackage_Collection();

		foreach ($packagesRaw as $datum)
		{
			if (!empty($datum['purchaseInfo']) && is_array($datum['purchaseInfo']))
			{
				$package = Baas\Model\PackageTable::createObject()->setCode($datum['code']);

				self::parseAndCollectPurchase(
					$package,
					$purchases,
					$purchasedPacks,
					$servicesInPurchasedPack,
					$datum['purchaseInfo'],
				);
			}
		}

		return [$purchases, $purchasedPacks, $servicesInPurchasedPack];
	}
}
