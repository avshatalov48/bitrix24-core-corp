<?php

declare(strict_types=1);

namespace Bitrix\Baas\Repository;

use Bitrix\Baas;

interface PurchaseRepositoryInterface {
	public function purge(): void;

	public function save(
		Baas\Model\EO_Purchase_Collection $purchases,
		Baas\Model\EO_PurchasedPackage_Collection $purchasedPackages,
		Baas\Model\EO_ServiceInPurchasedPackage_Collection $servicesInPurchasedPackages,
	): void;

	public function update(
		Baas\Model\EO_Purchase_Collection $purchases,
		Baas\Model\EO_PurchasedPackage_Collection $purchasedPackages,
		Baas\Model\EO_ServiceInPurchasedPackage_Collection $servicesInPurchasedPackages,
	): void;

	public function recalculateBalance(): void;

	public function updateByStateNumber(
		Baas\Model\EO_ServiceInPurchasedPackage_Collection $servicesInPurchasedPackages,
		int $stateNumber,
	): void;

	public function findPurchaseByPurchasedPackageCode(string $purchasedPackageCode): ?Baas\Model\EO_Purchase;

	public function findPurchaseByCode(string $purchaseCode): ?Baas\Model\EO_Purchase;

	public function getPurchasesToNotifyAbout(): Baas\Model\EO_Purchase_Collection;
}
