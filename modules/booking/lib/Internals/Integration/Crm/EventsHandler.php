<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Crm;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Model\ClientTypeTable;
use CCrmOwnerType;

class EventsHandler
{
	private const MODULE_ID = 'crm';

	public static function onContactDelete(int $contactId): void
	{
		self::onClientDelete(CCrmOwnerType::ContactName, $contactId);
	}

	public static function onCompanyDelete(int $companyId): void
	{
		self::onClientDelete(CCrmOwnerType::CompanyName, $companyId);
	}

	private static function onClientDelete(string $entityTypeName, int $entityId): void
	{
		$clientProvider = Container::getProviderManager()::getProviderByModuleId(self::MODULE_ID)?->getClientProvider();
		if (!$clientProvider)
		{
			return;
		}

		$clientTypeCollection = $clientProvider->getClientTypeCollection();

		$foundClientType = null;
		foreach ($clientTypeCollection as $clientType)
		{
			if ($clientType->getModuleId() === 'crm' && $clientType->getCode() === $entityTypeName)
			{
				$foundClientType = $clientType;

				break;
			}
		}

		if (!$foundClientType)
		{
			return;
		}

		$clientTypeRow = ClientTypeTable::getList([
			'filter' => [
				'=MODULE_ID' => $foundClientType->getModuleId(),
				'=CODE' => $foundClientType->getCode(),
			],
			'limit' => 1,
		])->fetch();
		if (!$clientTypeRow)
		{
			return;
		}

		Container::getBookingClientRepository()->unLinkByFilter([
			'=CLIENT_TYPE_ID' => (int)$clientTypeRow['ID'],
			'=CLIENT_ID' => $entityId,
		]);
	}

	public static function onDealDelete(int $dealId): void
	{
		Container::getBookingExternalDataRepository()->unLinkByFilter([
			'=MODULE_ID' => self::MODULE_ID,
			'=ENTITY_TYPE_ID' => CCrmOwnerType::DealName,
			'=VALUE' => (string)$dealId,
		]);
	}
}
