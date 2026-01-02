<?php

namespace Bitrix\Baas\Entity;

use Bitrix\Baas;

class Package implements \JsonSerializable, Baas\Contract\Package
{
	public function __construct(
		private string $code,
		private Baas\Model\EO_Package $data,
		private ?string $languageId = null,
	)
	{
		$this->languageId = $languageId ?? (defined('LANGUAGE_ID') ? constant('LANGUAGE_ID') : 'en');
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function setLanguage(string $languageId): static
	{
		$this->languageId = substr($languageId, 0, 2);

		return $this;
	}

	public function getTitle(): string
	{
		return $this->data->getLanguageInfo()[$this->languageId]['title']
			?? $this->data->getLanguageInfo()[$this->languageId]['TITLE']
			?? $this->data->getTitle()
			?? $this->code;
	}

	public function getDescription(): ?string
	{
		return $this->data->getLanguageInfo()[$this->languageId]['description']
			?? $this->data->getLanguageInfo()[$this->languageId]['DESCRIPTION']
			?? $this->data->getDescription()
			?? null;
	}

	public function getPriceDescription(): ?string
	{
		return $this->data->getLanguageInfo()[$this->languageId]['priceDescription']
			?? $this->data->getLanguageInfo()[$this->languageId]['PRICE_DESCRIPTION']
			?? $this->data->getPriceDescription()
			?? null;
	}

	public function getPrice(): string
	{
		return (new Baas\Integration\Currency\Price(
			$this->data->getPriceValue(),
			$this->data->getPriceCurrencyId()
		))->getFormatted();
	}

	public function getPurchaseUrl(): ?string
	{
		return $this->data->getPurchaseUrl();
	}

	/**
	 * @return array<Baas\Contract\PurchasedServiceInPackage>
	 */
	public function getPurchasedServices(): array
	{
		$purchasedServices = [];
		$purchases = $this->getPurchases();

		/** @var Baas\Model\Dto\Purchase $purchase */
		foreach ($purchases as $purchase)
		{
			/** @var Baas\Model\Dto\PurchasedPackage $purchasedPack */
			foreach ($purchase->getPurchasedPackages() as $purchasedPack)
			{
				foreach ($purchasedPack->getPurchasedServices() as $service)
				{
					$purchasedServices[] = $service;
				}
			}
		}

		return $purchasedServices;
	}

	public function getPurchaseInfo(bool $includeDepleted = false): Baas\Contract\PurchasesSummary
	{
		$purchases = $this->getPurchases($includeDepleted);

		$packBalance = [];
		/** @var Baas\Model\Dto\Purchase $purchase */
		foreach ($purchases as $purchase)
		{
			/** @var Baas\Model\Dto\PurchasedPackage $purchasedPack */
			foreach ($purchase->getPurchasedPackages() as $purchasedPack)
			{
				if ($purchasedPack->isActual())
				{
					foreach ($purchasedPack->getPurchasedServices() as $service)
					{
						$packBalance[$service->getServiceCode()] ??= ['current' => 0, 'max' => 0];
						$packBalance[$service->getServiceCode()]['current'] += $service->getCurrentValue();
						$packBalance[$service->getServiceCode()]['max'] += $service->getInitialValue();
					}
				}
			}
		}

		$balance = $this->calculatePackBalance($packBalance);

		return new Baas\Model\Dto\PurchasesSummary($balance, $purchases);
	}

	public function isActive(): bool
	{
		return $this->data->getActive() === 'Y';
	}

	/**
	 * @return array<Baas\Contract\Purchase>
	 */
	public function getPurchases(bool $includeDepleted = false): array
	{
		return Baas\Service\PurchaseService::getInstance()->getByPackageCode($this->getCode(), $includeDepleted);
	}

	/**
	 * @return array
	 * // 'purchaseInfo' => [
	 * // 	'purchaseCount' => 1,
	 * // 	'purchaseBalance' => 70 / 100, // currentValue / initialValue
	 * // 	'purchasedPackages' => [
	 * // 		'code' => 'PURCHASED_PACKAGE_CODE',
	 * // 		'purchaseCode' => 'PURCHASE_CODE',
	 * // 		'startDate' => $purchasedPack->getStartDate()->format('Y-m-d'),
	 * // 		'expirationDate' => $purchasedPack->getExpirationDate()->format('Y-m-d'),
	 * // 		'actual' => $purchasedPack->getActual(),
	 * // 		'services' => [
	 * // 			[
	 * // 				'code' => $serviceCode,
	 * // 				'current' => $serviceInPurchasedPackage->getCurrentValue(),
	 * // 				'maximal' => $serviceInPurchasedPackage->getServicesInPackage()->getMaximalValue(),
	 * // 			],
	 * // 			[
	 * // 				'code' => $serviceCode,
	 * // 				'current' => $serviceInPurchasedPackage->getCurrentValue(),
	 * // 				'maximal' => $serviceInPurchasedPackage->getServicesInPackage()->getMaximalValue(),
	 * // 			]
	 * // 		],
	 * // 	]
	 * // ],
	 */
	public function jsonSerialize(): array
	{
		return [
			'code' => $this->getCode(),
			'purchaseUrl' => $this->data->getPurchaseUrl(),

			'title' => $this->getTitle(),
			'description' => $this->getDescription(),
			'featurePromotionCode' => $this->data->getFeaturePromotionCode(),
			'helperCode' => $this->data->getHelperCode(),
			'isActive' => $this->data->getActive() === 'Y',
			'icon' => [
				'className' => $this->data->getIconClass(),
				'color' => $this->data->getIconColor(),
				'style' => $this->data->getIconStyle(),
			],
			'price' => [
				'value' => $this->getPrice(),
				'description' => $this->getPriceDescription(),
			],
			'purchaseInfo' => $this->getPurchaseInfo(includeDepleted: true)->__serialize(),
		];
	}

	public function __serialize(): array
	{
		return $this->jsonSerialize();
	}

	private function calculatePackBalance(array $packBalance): float
	{
		return array_reduce(
			$packBalance,
			function ($carry, $item)
			{
				if ($item['max'] <= 0)
				{
					return 100;
				}

				$balancePercentage = $item['current'] / $item['max'] * 100;

				return min($carry, $balancePercentage < 1 ? $balancePercentage : round($balancePercentage));
			},
			100,
		);
	}
}
