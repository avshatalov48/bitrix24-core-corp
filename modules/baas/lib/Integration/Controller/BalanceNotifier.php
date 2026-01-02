<?php

declare(strict_types=1);

namespace Bitrix\Baas\Integration\Controller;

use Bitrix\Baas;
use Bitrix\Main;

class BalanceNotifier
{
	private const TOO_EARLY_MESSAGE = 'TooEarly';

	/**
	 * @param string $packageCode
	 * @param string $purchaseCodeOrPurchasedPackage
	 * @return void
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public function sendAboutPurchase(string $packageCode, string $purchaseCodeOrPurchasedPackage): void
	{
		try
		{
			$this->runNotification($packageCode, $purchaseCodeOrPurchasedPackage);
		}
		catch (Main\SystemException $e)
		{
			if ($e->getMessage() === self::TOO_EARLY_MESSAGE)
			{
				(new DeferredBalanceNotifier($packageCode, $purchaseCodeOrPurchasedPackage))->bind();
			}
			else
			{
				throw $e;
			}
		}
	}

	/**
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public function runNotification(string $packageCode, string $purchaseCodeOrPurchasedPackage): void
	{
		$baas = Baas\Baas::getInstance();
		if (($baas->sync()->isSuccess())
			&& ($purchase = $this->findPurchase($purchaseCodeOrPurchasedPackage))
		)
		{
			Baas\Service\PurchaseService::getInstance()->notifyAboutPurchase($packageCode, $purchase->getCode());
			return;
		}

		throw new Main\SystemException(self::TOO_EARLY_MESSAGE);
	}

	protected function findPurchase(string $purchaseCodeOrPurchasedPackage): ?Baas\Model\EO_Purchase
	{
		$purchaseRepo = Baas\Repository\PurchaseRepository::getInstance();
		return $purchaseRepo->findPurchaseByPurchasedPackageCode($purchaseCodeOrPurchasedPackage)
			?? $purchaseRepo->findPurchaseByCode($purchaseCodeOrPurchasedPackage)
		;
	}
}
