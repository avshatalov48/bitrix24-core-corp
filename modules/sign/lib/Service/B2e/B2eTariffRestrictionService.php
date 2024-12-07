<?php

namespace Bitrix\Sign\Service\B2e;

use Bitrix\Main;
use Bitrix\Sign\Result\Service\License\LoadTariffResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\LicenseService;
use Bitrix\Main\Service;

class B2eTariffRestrictionService
{
	private const DATETIME_FORMAT = 'Y-m-d H:i:s';
	private const TARIFF_DATE_TO_START_RESTRICTION_MAP = [
		'BITRIX24_SHOP' => '2024-07-29 00:00:00',
	];

	private LicenseService $licenseService;

	public function __construct(
		?LicenseService $licenseService = null,
	)
	{
		$this->licenseService = $licenseService ?? Container::instance()->getLicenseService();
	}

	public function check(): Main\Result
	{
		if (!$this->isPortalTypeIsBox())
		{
			return new Main\Result();
		}

		$result = $this->licenseService->loadTariff();
		if (!$result instanceof LoadTariffResult)
		{
			return (new Main\Result())->addError(
				new Main\Error('', 'SIGN_CLIENT_CONNECTION_ERROR'),
			);
		}

		$restrictionDate = self::TARIFF_DATE_TO_START_RESTRICTION_MAP[$result->tariffCode] ?? null;
		if (!$restrictionDate)
		{
			return new Main\Result();
		}

		$restrictionDate = new Main\Type\DateTime($restrictionDate, self::DATETIME_FORMAT);
		$now = new Main\Type\DateTime();
		if ($now->getTimestamp() < $restrictionDate->getTimestamp())
		{
			return new Main\Result();
		}

		$firstB2eDocumentCreationDate = $this->getFirstB2eDocumentCreationDate();
		if (
			$firstB2eDocumentCreationDate
			&& $firstB2eDocumentCreationDate->getTimestamp() <= $restrictionDate->getTimestamp()
		)
		{
			return new Main\Result();
		}

		return (new Main\Result())->addError(new Main\Error('', 'LICENSE_LIMITATIONS'));
	}

	private function isPortalTypeIsBox(): bool
	{
		return Service\MicroService\Client::getPortalType() === Service\MicroService\Client::TYPE_BOX;
	}

	private function getFirstB2eDocumentCreationDate(): ?Main\Type\DateTime
	{
		return Container::instance()->getDocumentRepository()->getFirstCreatedB2eDocument()?->dateCreate;
	}
}