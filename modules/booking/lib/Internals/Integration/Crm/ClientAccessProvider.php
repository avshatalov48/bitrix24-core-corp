<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Crm;

use Bitrix\Booking\Entity\Client\ClientCollection;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;

class ClientAccessProvider
{
	/**
	 * Access map to determine permissions for current user.
	 * Contain array like [<type>_<id> => ['read' => bool]], ex. ['COMPANY_24' => ['read' => true], 'CONTACT_12' => ['read' => false], ...]
	 */
	public function getReadAccessMap(ClientCollection $clientCollection): array
	{
		if (
			!Loader::includeModule('crm')
			|| $clientCollection->isEmpty()
		)
		{
			return [];
		}

		$companyIds = [];
		$contactIds = [];

		foreach ($clientCollection as $client)
		{
			switch ($client->getType()->getCode())
			{
				case \CCrmOwnerType::CompanyName:
					$companyIds[] = $client->getId();
					break;
				case \CCrmOwnerType::ContactName:
					$contactIds[] = $client->getId();
					break;
				default:
					continue 2;
			}
		}

		if (empty($companyIds) && empty($contactIds))
		{
			return [];
		}

		$this->preloadPermissions($companyIds, $contactIds);

		return $this->buildAccessMap($clientCollection);
	}

	private function preloadPermissions(array $companyIds, array $contactIds): void
	{
		$permissionItem = Container::getInstance()->getUserPermissions()->item();

		if (!empty($companyIds))
		{
			$permissionItem->preloadPermissionAttributes(\CCrmOwnerType::Company, $companyIds);
		}

		if (!empty($contactIds))
		{
			$permissionItem->preloadPermissionAttributes(\CCrmOwnerType::Contact, $contactIds);
		}
	}

	private function buildAccessMap(ClientCollection $clientCollection): array
	{
		$accessMap = [];
		$permissionItem = Container::getInstance()->getUserPermissions()->item();

		foreach ($clientCollection as $client)
		{
			$type = $client->getType()->getCode();
			$id = $client->getId();
			$crmOwnerType = $this->getCrmOwnerType($type);

			if (!$crmOwnerType)
			{
				continue;
			}

			$accessKey = sprintf('%s_%s', $type, $id);
			$accessMap[$accessKey] = [
				'read' => $permissionItem->canRead($crmOwnerType, $id),
			];
		}

		return $accessMap;
	}

	private function getCrmOwnerType(string $clientType): ?int
	{
		return match ($clientType)
		{
			\CCrmOwnerType::CompanyName => \CCrmOwnerType::Company,
			\CCrmOwnerType::ContactName => \CCrmOwnerType::Contact,
			default => null,
		};
	}
}
