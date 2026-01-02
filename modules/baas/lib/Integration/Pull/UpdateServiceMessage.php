<?php

declare(strict_types=1);

namespace Bitrix\Baas\Integration\Pull;

use Bitrix\Main;
use Bitrix\Baas;

class UpdateServiceMessage
{
	public const COMMAND = 'updateService';

	public function __construct(
		protected Baas\Contract\Service $service,
		/** @var Baas\Contract\Package[] $packages */
		protected array $packages,
		/** @var Baas\Contract\Purchase[] $purchases */
		protected array $purchases,
	)
	{

	}

	public function send(): void
	{
		Channel::add(self::COMMAND, [
			'service' => $this->service,
			'packages' => array_map(static function(Baas\Contract\Package $package) {
				$purchaseSummary = $package->getPurchaseInfo();
				return [
					'code' => $package->getCode(),
					'purchaseUrl' => $package->getPurchaseUrl(),

					'title' => $package->getTitle(),
					'description' => $package->getDescription(),
					'price' => [
						'value' => $package->getPrice(),
						'description' => $package->getPriceDescription(),
					],
					'purchaseInfo' => [
						'purchaseCount' => $purchaseSummary->getCount(),
						'purchaseBalance' => $purchaseSummary->getBalance(),
						'purchases' => array_map(static function(Baas\Contract\Purchase $purchase) {
							return array_map(static function(Baas\Contract\PurchasedPackage $purchasedPackage) {
								return [
									'code' => $purchasedPackage->getCode(),
									'purchaseCode' => $purchasedPackage->getPurchaseCode(),
									'startDate' => $purchasedPackage->getStartDate()->format(
										Baas\Contract\DateTimeFormat::LOCAL_DATE->value
									),
									'expirationDate' => $purchasedPackage->getExpirationDate()->format(
										Baas\Contract\DateTimeFormat::LOCAL_DATE->value
									),
									'actual' => $purchasedPackage->isActual() ? 'Y' : 'N',
									'services' => array_map(static function($purchasedService) {
										return [
											'code' => $purchasedService->getServiceCode(),
											'current' => $purchasedService->getCurrentValue(),
											'maximal' => $purchasedService->getInitialValue(),
										];
									}, array_values($purchasedPackage->getPurchasedServices())),
								];
							}, array_values($purchase->getPurchasedPackages()));
						}, array_values($purchaseSummary->getPurchases())),
					],
				];
			}, $this->packages),
			'purchaseCount' => count($this->purchases),
		]);
	}
}
