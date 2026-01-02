<?php

namespace Bitrix\Baas\Model;

use Bitrix\Main;
use Bitrix\Baas;

class Package extends EO_Package
{
	public static function purge(): Main\ORM\Data\Result
	{
		$purchasedPackages = Baas\Model\PurchasedPackageTable::getList([
			'select' => ['CODE', 'PACKAGE_CODE'],
		])
			->fetchCollection()
		;
		$purchasedPackageCodes = $purchasedPackages->getCodeList();
		$packageCodes = $purchasedPackages->getPackageCodeList();

		if (!empty($purchasedPackageCodes))
		{
			Baas\Model\ServiceInPurchasedPackageTable::deleteBatch(['=PURCHASED_PACKAGE_CODE' => $purchasedPackageCodes]);
			Baas\Model\PurchasedPackageTable::deleteBatch(['=CODE' => $purchasedPackageCodes]);
		}
		Baas\Model\PurchaseTable::deleteBatch(['=PACKAGE_CODE' => $packageCodes]);
		Baas\Model\ServiceInPackageTable::deleteBatch(['=PACKAGE_CODE' => $packageCodes]);

		return Baas\Model\PackageTable::deleteBatch(['=CODE' => $packageCodes]);
	}
}
