<?php

namespace Bitrix\Crm\Client;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\Mode;
use Bitrix\Main\Type\DateTime;
use CCrmOwnerType;

final class ClientTypeResolver
{
	private ItemIdentifier $client;
	private ?Item $clientInfo;
	private ClientType $type;
	private array $recognisableTypes = [
		CCrmOwnerType::Lead,
		CCrmOwnerType::Contact,
		CCrmOwnerType::Company,
	];

	public function isValidType(): bool
	{
		return in_array($this->client->getEntityTypeId(), $this->recognisableTypes, true);
	}

	private function isClientCompanyOrContact(): bool
	{
		return in_array(
			$this->client->getEntityTypeId(),
			[CCrmOwnerType::Contact, CCrmOwnerType::Company],
			true
		);
	}

	private function isCrmAtClassicMode(): bool
	{
		return Mode::getCurrentName() === Mode::CLASSIC_NAME;
	}

	public function getType(ItemIdentifier $client): ClientType
	{
		$this->client = $client;

		if (!$this->isValidType())
		{
			return ClientType::Unrecognised;
		}

		$fieldsToSelect = ['ID', 'CREATED_TIME'];
		if ($this->client->getEntityTypeId() === CCrmOwnerType::Lead)
		{
			$fieldsToSelect[] = 'CONTACT_ID';
			$fieldsToSelect[] = 'COMPANY_ID';
		}

		$this->clientInfo = Container::getInstance()
			->getFactory($this->client->getEntityTypeId())
			?->getItem($this->client->getEntityId(), $fieldsToSelect);

		if (!$this->clientInfo)
		{
			return ClientType::Unrecognised;
		}

		$this->type = ClientType::Existing;

		if ($this->clientInfo->getCreatedTime() > (new DateTime())->add('-1 hour'))
		{
			$this->type = ClientType::New;

			//exclude for Company and Contact in classic CRM
			if ($this->isClientCompanyOrContact() && $this->isCrmAtClassicMode())
			{
				$this->type = ClientType::Existing;
			}
		}

		if ($this->client->getEntityTypeId() === CCrmOwnerType::Lead)
		{
			$this->recognizeLeadClientType();
		}

		if ($this->isClientCompanyOrContact())
		{
			$this->recognizeClientType();
		}

		return $this->type;
	}

	private function getFailDealItem(ItemIdentifier $identifier): ?Item
	{
		return $this->getDealItemBySemantic(PhaseSemantics::FAILURE, $identifier);
	}

	private function getSuccessDealItem(ItemIdentifier $identifier): ?Item
	{
		return $this->getDealItemBySemantic(PhaseSemantics::SUCCESS, $identifier);
	}

	private function getFilterFieldByEntityType(int $entityTypeId = CCrmOwnerType::Lead): string
	{
		$filterField = Item::FIELD_NAME_LEAD_ID;

		if ($entityTypeId === CCrmOwnerType::Company)
		{
			$filterField = Item::FIELD_NAME_COMPANY_ID;
		}

		if ($entityTypeId === CCrmOwnerType::Contact)
		{
			$filterField = Item::FIELD_NAME_CONTACT_BINDINGS . '.CONTACT_ID';
		}

		return $filterField;
	}

	private function getDealItemBySemantic(string $semanticId, ItemIdentifier $identifier): ?Item
	{
		$dealFactory = Container::getInstance()->getFactory(CCrmOwnerType::Deal);
		$filterField = $this->getFilterFieldByEntityType($identifier->getEntityTypeId());

		$items = $dealFactory?->getItems([
			'select' => ['ID'],
			'filter' => [
				'=' . $filterField => $identifier->getEntityId(),
				'=STAGE_SEMANTIC_ID' => $semanticId,
			],
			'limit' => 1,
		]);

		return $items[0] ?? null;
	}

	private function recognizeLeadClientType(): void
	{
		$companyId = $this->clientInfo->getCompanyId();
		$contactId = $this->clientInfo->getContactId();
		$successCompanyDeal = $companyId ? $this->getSuccessDealItem(new ItemIdentifier(CCrmOwnerType::Company, $companyId)) : null;
		$successContactDeal = $contactId ? $this->getSuccessDealItem(new ItemIdentifier(CCrmOwnerType::Contact, $contactId)) : null;

		if (($companyId && $successCompanyDeal) || ($contactId && $successContactDeal))
		{
			$this->type = ClientType::WithSale;
		}

		if ($this->type !== ClientType::WithSale && ($companyId || $contactId))
		{
			$this->type = ClientType::PreviouslyContacted;
		}
	}

	private function recognizeClientType(): void
	{
		if ($this->getSuccessDealItem($this->client))
		{
			$this->type = ClientType::WithSale;
		}

		if ($this->type !== ClientType::WithSale && $this->getFailDealItem($this->client))
		{
			$this->type = ClientType::PreviouslyContacted;
		}
	}
}
