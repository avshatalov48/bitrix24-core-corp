<?php

declare(strict_types=1);

namespace Bitrix\Crm\Integration\Booking;

use Bitrix\Booking\Entity\Booking\Client;
use Bitrix\Booking\Entity\Booking\ClientCollection;
use Bitrix\Booking\Entity\Booking\ClientType;
use Bitrix\Booking\Entity\Booking\ClientTypeCollection;
use Bitrix\Booking\Interfaces\ClientProviderInterface;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Config\Option;
use CCrmOwnerType;
use DateTimeImmutable;

class ClientProvider implements ClientProviderInterface
{
	private const MODULE_ID = 'crm';

	public function getClientTypeCollection(): ClientTypeCollection
	{
		return new ClientTypeCollection(
			(new ClientType())
				->setModuleId(self::MODULE_ID)
				->setCode(CCrmOwnerType::ContactName),
			(new ClientType())
				->setModuleId(self::MODULE_ID)
				->setCode(CCrmOwnerType::CompanyName),
		);
	}

	public function getClientName(Client $client): string
	{
		$clientId = $client->getId();
		if (!$clientId)
		{
			return '';
		}

		$factory = $this->getFactoryByClient($client);
		if (!$factory)
		{
			return '';
		}

		if ($client->getType()?->getCode() === CCrmOwnerType::ContactName)
		{
			$contact = $factory->getItem($clientId, ['NAME']);
			if (!$contact)
			{
				return '';
			}

			return $contact->getName() ?? '';
		}

		if ($client->getType()?->getCode() === CCrmOwnerType::CompanyName)
		{
			$company = $factory->getItem($clientId, ['TITLE']);
			if (!$company)
			{
				return '';
			}

			return $company->getTitle() ?? '';
		}

		return '';
	}

	public function getMessageSenders(): array
	{
		return [
			new MessageSender(),
		];
	}

	public function pickPrimaryClient(ClientCollection $clientCollection): Client|null
	{
		foreach ($clientCollection as $client)
		{
			if ($client->getType()?->getCode() === CCrmOwnerType::ContactName)
			{
				return $client;
			}
		}

		return $clientCollection->getFirstCollectionItem();
	}

	public function loadClientDataForCollection(...$clientCollections): void
	{
		$contactIds = [];
		$companyIds = [];

		foreach ($clientCollections as $clientCollection)
		{
			foreach ($clientCollection as $client)
			{
				$clientId = $client->getId();
				$clientTypeCode = $client->getType()?->getCode();

				if ($clientTypeCode === CCrmOwnerType::ContactName)
				{
					$contactIds[$clientId] = $clientId;
				}

				if ($clientTypeCode === CCrmOwnerType::CompanyName)
				{
					$companyIds[$clientId] = $clientId;
				}
			}
		}

		$clientData = [
			CCrmOwnerType::ContactName => $this->getContacts($contactIds),
			CCrmOwnerType::CompanyName => $this->getCompanies($companyIds),
		];

		foreach ($clientCollections as $clientCollection)
		{
			foreach ($clientCollection as $client)
			{
				$clientId = $client->getId();
				$clientTypeCode = $client->getType()?->getCode();

				if ($clientTypeCode === CCrmOwnerType::ContactName)
				{
					if (isset($clientData[CCrmOwnerType::ContactName][$clientId]))
					{
						$client->setData($clientData[CCrmOwnerType::ContactName][$clientId]);
					}
				}

				if ($clientTypeCode === CCrmOwnerType::CompanyName)
				{
					if (isset($clientData[CCrmOwnerType::CompanyName][$clientId]))
					{
						$client->setData($clientData[CCrmOwnerType::CompanyName][$clientId]);
					}
				}
			}
		}
	}

	public function getClientDataRecent(): array
	{
		$contactIds = [];
		$companyIds = [];

		//@todo why do we take it from deal?
		$lastContacts = \CUserOptions::GetOption('crm.deal.details', 'contact', []);
		$lastCompanies = \CUserOptions::GetOption('crm.deal.details', 'company', []);
		foreach ($lastContacts as $lastContact)
		{
			$parts = explode(':', $lastContact);
			$contactId = (int)($parts[1] ?? 0);
			if (!empty($contactId))
			{
				$contactIds[$contactId] = $contactId;
			}
		}

		foreach ($lastCompanies as $lastCompany)
		{
			$parts = explode(':', $lastCompany);
			$companyId = (int)($parts[1] ?? 0);
			if (!empty($companyId))
			{
				$companyIds[$companyId] = $companyId;
			}
		}

		return [
			CCrmOwnerType::ContactName => $this->getContacts($contactIds),
			CCrmOwnerType::CompanyName => $this->getCompanies($companyIds),
		];
	}

	public function getClientUrl(Client $client): string
	{
		if ($client->getType()?->getCode() === CCrmOwnerType::ContactName)
		{
			$contactUrl = Option::get('crm', 'path_to_contact_details', '/crm/contact/details/#contact_id#/');
			return \CComponentEngine::MakePathFromTemplate($contactUrl, ['contact_id' => $client->getId()]);
		}

		$companyUrl = Option::get('crm', 'path_to_company_details', '/crm/company/details/#company_id#/');
		return \CComponentEngine::MakePathFromTemplate($companyUrl, ['company_id' => $client->getId()]);
	}

	private function getContacts(array $contactIds): array
	{
		if (empty($contactIds))
		{
			return [];
		}

		$contactIds = array_keys($contactIds);

		$result = \CCrmContact::GetListEx(
			[],
			[
				'@ID' => $contactIds,
				//@todo review later
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			[
				'ID',
				'HONORIFIC',
				'NAME',
				'SECOND_NAME',
				'LAST_NAME',
				'CATEGORY_ID',
				'PHOTO',
			],
		);

		$contacts = [];
		while ($row = $result->fetch())
		{
			$id = (int)$row['ID'];

			$contacts[$id] = [
				'id' => $id,
				'name' => \CCrmContact::PrepareFormattedName($row),
				'image' => $this->getImageSrc((int)($row['PHOTO'] ?? 0)),
				'phones' => [],
				'emails' => [],
			];
		}

		$communicationsResult = \CCrmFieldMulti::GetListEx(
			[],
			[
				'=ENTITY_ID' => CCrmOwnerType::ContactName,
				'@ELEMENT_ID' => $contactIds,
				'@TYPE_ID' => ['PHONE', 'EMAIL'],
			],
		);
		while ($communication = $communicationsResult->fetch())
		{
			if (empty($contacts[$communication['ELEMENT_ID']]))
			{
				continue;
			}

			if ($communication['TYPE_ID'] === 'PHONE')
			{
				$contacts[$communication['ELEMENT_ID']]['phones'][] = $communication['VALUE'];
			}
			if ($communication['TYPE_ID'] === 'EMAIL')
			{
				$contacts[$communication['ELEMENT_ID']]['emails'][] = $communication['VALUE'];
			}
		}

		return $contacts;
	}

	private function getCompanies(array $companyIds): array
	{
		if (empty($companyIds))
		{
			return [];
		}

		$companyIds = array_keys($companyIds);

		$result = \CCrmCompany::GetListEx(
			[],
			[
				'@ID' => $companyIds,
				//@todo review later
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			[
				'ID',
				'TITLE',
				'CATEGORY_ID',
				'LOGO',
			]
		);

		$companies = [];
		while ($row = $result->fetch())
		{
			$id = (int)$row['ID'];

			$companies[$id] = [
				'id' => $id,
				'name' => $row['TITLE'],
				'image' => $this->getImageSrc((int)($row['LOGO'] ?? 0)),
				'phones' => [],
				'emails' => [],
			];
		}

		$communicationsResult = \CCrmFieldMulti::GetListEx(
			[],
			[
				'=ENTITY_ID' => CCrmOwnerType::CompanyName,
				'@ELEMENT_ID' => $companyIds,
				'@TYPE_ID' => ['PHONE', 'EMAIL'],
			],
		);
		while ($communication = $communicationsResult->Fetch())
		{
			if (empty($companies[$communication['ELEMENT_ID']]))
			{
				continue;
			}

			if ($communication['TYPE_ID'] === 'PHONE')
			{
				$companies[$communication['ELEMENT_ID']]['phones'][] = $communication['VALUE'];
			}
			if ($communication['TYPE_ID'] === 'EMAIL')
			{
				$companies[$communication['ELEMENT_ID']]['emails'][] = $communication['VALUE'];
			}
		}

		return $companies;
	}

	private function getFactoryByClient(Client $client): Factory|null
	{
		$entityTypeId = $this->getEntityTypeIdByCode($client->getType()?->getCode());
		if (!$entityTypeId)
		{
			return null;
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}

		return $factory;
	}

	private function getEntityTypeIdByCode(string|null $code): int|null
	{
		$result = CCrmOwnerType::ResolveID($code);

		return $result === CCrmOwnerType::Undefined ? null : $result;
	}

	private function getImageSrc(int $imageId): string
	{
		$tmpData = \CFile::resizeImageGet(
			$imageId,
			[
				'width' => 100,
				'height' => 100,
			],
			BX_RESIZE_IMAGE_EXACT,
			false,
			false,
			true
		);

		return (!empty($tmpData['src']) ? $tmpData['src'] : '');
	}
}
