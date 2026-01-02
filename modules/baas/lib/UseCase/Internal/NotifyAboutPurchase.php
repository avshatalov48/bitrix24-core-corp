<?php

declare(strict_types=1);

namespace Bitrix\Baas\UseCase\Internal;

use \Bitrix\Baas;
use Bitrix\Baas\UseCase\Internal\Request\NotifyAboutPurchaseRequest;
use \Bitrix\Main;

class NotifyAboutPurchase
{
	public function __construct(protected Baas\Repository\PurchaseRepository $purchaseRepository)
	{
	}

	public function __invoke(NotifyAboutPurchaseRequest $request): Main\Result
	{
		$packageCode = $request->packageCode;
		$purchaseCode = $request->purchaseCode;

		$servicesInAPurchase = $this->purchaseRepository->getServicesInPurchase(
			$packageCode,
			$purchaseCode,
		);
		if (empty($servicesInAPurchase))
		{
			Main\Application::getInstance()->getExceptionHandler()->writeToLog(
				new Main\SystemException(
					'Baas is notified about empty purchase: package: ' . $packageCode . ', purchase: ' . $purchaseCode,
				),
			);
		}
		if (!empty($servicesInAPurchase) && ($purchaseInfo = reset($servicesInAPurchase)))
		{
			$eventData = [
				'startDate' => $purchaseInfo['WILL_START'],
				'expiredDate' => $purchaseInfo['WILL_EXPIRED'],
				'services' => [
					$purchaseInfo['SERVICE_CODE'] => $purchaseInfo['CURRENTV'],
				],
			];
			//region compatibility reasons. Will be deleted soon.
			if (count($servicesInAPurchase) === 1)
			{
				$eventData['serviceCode'] = $purchaseInfo['SERVICE_CODE'];
				$eventData['purchasedValue'] = $purchaseInfo['CURRENTV'];
			}
			//endregion
			else
			{
				foreach ($servicesInAPurchase as $purchaseInfo)
				{
					$eventData['services'][$purchaseInfo['SERVICE_CODE']] = $purchaseInfo['CURRENTV'];
				}
			}
			Baas\Internal\Diag\Logger::getInstance()->info('NotifyAboutPurchase', $eventData);
			(new Main\Event('baas', 'onPackagePurchased', $eventData))->send();

			$purchase = $this->purchaseRepository->findPurchaseByCode($purchaseCode);
			$purchase?->setNotified(true);
			$purchase?->save();
		}

		return new Main\Result();
	}
}
