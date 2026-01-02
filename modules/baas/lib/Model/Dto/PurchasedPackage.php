<?php

declare(strict_types=1);

namespace Bitrix\Baas\Model\Dto;

use Bitrix\Baas;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\Type\Date;

class PurchasedPackage implements Baas\Contract\PurchasedPackage
{
	public function __construct(
		private string $code,
		private string $packageCode,
		private string $purchaseCode,
		private Main\Type\Date $startDate,
		private Main\Type\Date $expirationDate,
		private bool $active,
		private bool $actual,
		private array $purchasedServices = [],
	)
	{
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getPackageCode(): string
	{
		return $this->packageCode;
	}

	public function getPurchaseCode(): string
	{
		return $this->purchaseCode;
	}

	public function getStartDate(): Main\Type\Date
	{
		return $this->startDate;
	}

	public function getExpirationDate(): Main\Type\Date
	{
		return $this->expirationDate;
	}

	public function getExpirationFormattedDate(): string
	{
		$expirationDate = $this->getExpirationDate();

		if ($expirationDate->format('y') === (new Date())->format('y'))
		{
			$format = Context::getCurrent()->getCulture()->getDayMonthFormat();
		}
		else
		{
			$format = Context::getCurrent()->getCulture()->getLongDateFormat();
		}

		return FormatDate($format, $expirationDate->getTimestamp());
	}

	public function isActive(): bool
	{
		return $this->active;
	}

	public function isActual(): bool
	{
		return $this->actual;
	}

	/**
	 * @inheritDoc
	 */
	public function &getPurchasedServices(): array
	{
		return $this->purchasedServices;
	}

	public function __serialize(): array
	{
		return [
			'code' => $this->getCode(),
			'purchaseCode' => $this->getPurchaseCode(),
			'startDate' => $this->getStartDate()->format(
				Baas\Contract\DateTimeFormat::LOCAL_DATE->value
			),
			'expirationDate' => $this->getExpirationDate()->format(
				Baas\Contract\DateTimeFormat::LOCAL_DATE->value
			),
			'actual' => $this->isActual() ? 'Y' : 'N',
			/** @var  $purchasedService */
			'services' => array_values(array_map(
				function (PurchasedServiceInPackage $service) {
					return [
						'code' => $service->getServiceCode(),
						'current' => $service->getCurrentValue(),
						'maximal' => $service->getInitialValue(),
					];
				},
				$this->getPurchasedServices(),
			)),
		];
	}
}
