<?php

namespace Bitrix\Baas\Entity;

use Bitrix\Main;
use Bitrix\Baas;

class Service implements \JsonSerializable, Baas\Contract\Service
{
	public const CODE_MAIN_SERVICE = 'baas';
	private string $code;
	private ?Baas\Model\EO_Service $data;
	private Main\Type\Date $expirationDate;
	private string $languageId;
	private Baas\Baas $baasService;
	private Baas\Service\ServiceService $serviceService;

	public function __construct(
		string $code,
		?Baas\Model\EO_Service $data = null,
		Baas\Baas $baasService = null,
		Baas\Service\ServiceService $serviceService = null,
	)
	{
		$this->code = $code;
		$this->data = $data;
		$this->baasService = $baasService ?? Baas\Baas::getInstance();
		$this->serviceService = $serviceService ?? $this->baasService->getServiceManager();

		$this->init();
	}

	protected function init(): void
	{
		$this->expirationDate = $this->data?->getExpirationDate() ?? Main\Type\Date::createFromTimestamp(0);
		$this->languageId = defined('LANGUAGE_ID') ? constant('LANGUAGE_ID') : 'en';
	}

	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * It is not recommended to use this method on a local portal.
	 * @param int $units
	 * @return bool
	 */
	public function canConsume(int $units = 1): bool
	{
		return ($this->data->getCurrentValue() - $units) >= $this->data->getMinimalValue();
	}

	/**
	 * It is not recommended to use this method on a local portal.
	 */
	public function consume(int $units = 1, ?array $attributes = null): Main\Result
	{
		$result = $this->serviceService->consumeService($this, $units, false, $attributes);

		return $result;
	}

	/**
	 * It is not recommended to use this method on a local portal.
	 */
	public function forceConsume(int $units = 1, ?array $attributes = null): Main\Result
	{
		$result = $this->serviceService->consumeService($this, $units, true, $attributes);

		return $result;
	}

	/**
	 * @deprecated
	 */
	public function release(string $consumptionId): Main\Result
	{
		return $this->refund($consumptionId);
	}

	/**
	 * It is not recommended to use this method on a local portal.
	 */
	public function refund(string $consumptionId, ?array $attributes = null): Main\Result
	{
		$result = $this->serviceService->refundService($this, $consumptionId, $attributes);

		return $result;
	}

	public function applyProxyState(array $proxyServiceBalanceWithTheState): void
	{
		$this->serviceService->applyProxyState($this, $proxyServiceBalanceWithTheState);
	}

	public function applyBillingState(array $billingServiceBalanceWithTheState): void
	{
		$this->serviceService->applyBillingServiceBalance($this, $billingServiceBalanceWithTheState);
	}

	public function setLanguage(string $languageId): static
	{
		$this->languageId = substr($languageId, 0, 2);

		return $this;
	}

	public function __call($name, $arguments)
	{
		if (substr($name, 0, 3) == 'get')
		{
			return $this->data?->{$name}();
		}

		throw new Main\SystemException(sprintf(
			'Unknown method `%s` for object `%s`', $name, get_called_class(),
		));
	}

	public function getTitle(): string
	{
		return $this->data?->getLanguageInfo()[$this->languageId]['TITLE']
			?? $this->data?->getLanguageInfo()[$this->languageId]['title']
			?? $this->data?->getTitle()
			?? $this->code;
	}

	public function getActiveSubtitle(): string
	{
		return $this->data?->getLanguageInfo()[$this->languageId]['activeSubtitle']
			?? $this->data?->getLanguageInfo()[$this->languageId]['ACTIVE_SUBTITLE']
			?? $this->data?->getActiveSubtitle()
			?? '';
	}

	public function getInactiveSubtitle(): string
	{
		return $this->data?->getLanguageInfo()[$this->languageId]['inactiveSubtitle']
			?? $this->data?->getLanguageInfo()[$this->languageId]['INACTIVE_SUBTITLE']
			?? $this->data?->getInactiveSubtitle()
			?? '';
	}

	public function getSubtitle(): string
	{
		if ($this->isEnabled())
		{
			return $this->getActiveSubtitle();
		}

		return $this->getInactiveSubtitle();
	}

	public function getDescription(): string
	{
		return $this->data?->getLanguageInfo()[$this->languageId]['description']
			?? $this->data?->getLanguageInfo()[$this->languageId]['DESCRIPTION']
			?? $this->data?->getDescription()
			?? '';
	}

	public function getExpirationDate(): Main\Type\Date
	{
		return $this->expirationDate;
	}

	public function getData(): ?Baas\Model\EO_Service
	{
		return $this->data;
	}

	public function getAdsInfo(): ?Baas\Model\EO_ServiceAds
	{
		return $this->serviceService->getAdsInfo($this, $this->languageId);
	}

	/**
	 * This service can be bought in this region in general.
	 */
	public function isAvailable(): bool
	{
		return $this->baasService->isAvailable() && !empty($this->data);
	}

	/**
	 * This service has actual packages.
	 */
	public function isActual(): bool
	{
		return $this->expirationDate >= (new Main\Type\Date());
	}

	/**
	 * This portal has the boost.
	 * This service is available in this region and this service has active packages.
	 */
	public function isEnabled(): bool
	{
		return $this->isAvailable()
			&& $this->isActual();
	}

	/**
	 * This portal has the boost service and can use it.
	 * The service has active values, there are actual packages,
	 * the service can be purchased in this region,
	 * the portal license is active and paid.
	 * Ex. If License is not paid, the service can not be used, and this method will return false.
	 */
	public function isActive(): bool
	{
		return $this->isEnabled()
			&& $this->getValue() > $this->data?->getMinimalValue() || $this->data?->getRenewable() === true;
	}

	public function isRenewable(): bool
	{
		return $this->data?->getRenewable() === true;
	}

	public function getValue(): ?int
	{
		return $this->data?->getCurrentValue();
	}

	public function getMaximalValue(): ?int
	{
		return $this->data?->getMaximalValue();
	}

	public function jsonSerialize(): array
	{
		return [
			'code' => $this->data?->getCode(),
			'value' => $this->getValue(),
			'isAvailable' => $this->isAvailable(),
			'isEnabled' => $this->isEnabled(),
			'isActual' => $this->isActual(),
			'isActive' => $this->isActive(),
			'title' => $this->getTitle(),
			'activeSubtitle' => $this->getActiveSubtitle(),
			'inactiveSubtitle' => $this->getInactiveSubtitle(),
			'description' => $this->getDescription(),
			'featurePromotionCode' => $this->data?->getFeaturePromotionCode(),
			'helperCode' => $this->data?->getHelperCode(),
			'advertisingStrategy' => $this->data?->getAdvertisingStrategy(),
			'icon' => [
				'className' => $this->data?->getIconClass(),
				'color' => $this->data?->getIconColor(),
				'style' => $this->data?->getIconStyle(),
			],
		];
	}

	public function __serialize(): array
	{
		return $this->jsonSerialize();
	}
}
