<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;

use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\PhoneNumber\Parser;
use CCrmEntitySelectorHelper;
use CCrmFieldMulti;
use CCrmOwnerType;

final class Communications
{
	private Factory $factory;
	private UserPermissions $userPermissions;

	public function __construct(private readonly int $entityTypeId, private readonly int $entityId)
	{
		$this->factory = Container::getInstance()->getFactory($entityTypeId);
		$this->userPermissions = Container::getInstance()->getUserPermissions();
	}

	public function get(): array
	{
		$result = SmsManager::getEntityPhoneCommunications($this->entityTypeId, $this->entityId);
		$item = $this->factory->getItem($this->entityId);
		if (!$item)
		{
			return $result;
		}

		$this->addPhonesFromContacts($result, $item);
		$this->addPhonesFromCompanies($result, $item);
		$this->addPhonesFromCompany($result, $item);

		return $result;
	}

	private function addPhonesFromContacts(array &$communications, Item $item): void
	{
		if (!$item->hasField(Item::FIELD_NAME_CONTACT_BINDINGS))
		{
			return;
		}

		$contacts = $item->getContacts();
		foreach ($contacts as $contact)
		{
			if ($this->userPermissions->checkReadPermissions(CCrmOwnerType::Contact, $contact->getId()))
			{
				$this->appendClientPhones(
					CCrmOwnerType::Contact,
					$communications,
					$contact
				);
			}
		}
	}

	private function addPhonesFromCompanies(array &$communications, Item $item): void
	{
		if (
			$item->getEntityTypeId() !== CCrmOwnerType::Contact
			|| !$item->hasField(Item\Contact::FIELD_NAME_COMPANY_BINDINGS)
		)
		{
			return;
		}

		$companies = $item->getCompanies();
		foreach ($companies as $company)
		{
			if ($this->userPermissions->checkReadPermissions(CCrmOwnerType::Company, $company->getId()))
			{
				$this->appendClientPhones(
					CCrmOwnerType::Company,
					$communications,
					$company
				);
			}
		}
	}

	private function addPhonesFromCompany(array &$communications, Item $item): void
	{
		if (!$item->hasField(Item::FIELD_NAME_COMPANY))
		{
			return;
		}

		$company = $item->getCompany();
		if (!$company)
		{
			return;
		}

		if ($this->userPermissions->checkReadPermissions(CCrmOwnerType::Contact, $company->getId()))
		{
			$this->appendClientPhones(
				CCrmOwnerType::Company,
				$communications,
				$company
			);
		}
	}

	private function appendClientPhones(int $entityTypeId, array &$communications, object $client): void
	{
		$clientId = $client->getId();
		$clientTypeName = CCrmOwnerType::ResolveName($entityTypeId);
		$clientInfo = CCrmEntitySelectorHelper::PrepareEntityInfo($clientTypeName, $clientId);

		if (isset($clientInfo['ADVANCED_INFO']['MULTI_FIELDS']))
		{
			$communication = [
				'entityId' => $clientId,
				'entityTypeId' => $entityTypeId,
				'entityTypeName' => $clientTypeName,
				'caption' => (
				$entityTypeId === CCrmOwnerType::Contact
					? $client->getFormattedName()
					: $client->getTitle()
				),
			];

			$multiFieldEntityTypes = CCrmFieldMulti::GetEntityTypes();
			foreach ($clientInfo['ADVANCED_INFO']['MULTI_FIELDS'] as $mf)
			{
				if ($mf['TYPE_ID'] !== Phone::ID)
				{
					continue;
				}

				$communication['phones'][] = [
					'value' => $mf['VALUE'],
					'valueFormatted' => Parser::getInstance()?->parse($mf['VALUE'])->format(),
					'type' => $mf['VALUE_TYPE'],
					'typeLabel' => $multiFieldEntityTypes[Phone::ID][$mf['VALUE_TYPE']]['SHORT'],
					'id' => $mf['ENTITY_ID'],
				];
			}

			$communications[] = $communication;
		}
	}
}
