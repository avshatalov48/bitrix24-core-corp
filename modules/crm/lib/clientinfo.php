<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Binding\LeadContactTable;
use Bitrix\Crm\Order\Order;
use Bitrix\Crm\Service\Container;
use CCrmInvoice;
use CCrmOwnerType;

/**
 * Client info.
 *
 * Contains the ids of the company, contacts, as well as the owner.
 * To create instances, you can use the factory method @see ClientInfo::createFromOwner.
 */
class ClientInfo
{
	public ?int $companyId;
	public array $contactIds;
	public ?int $ownerTypeId = null;
	public ?int $ownerId = null;

	/**
	 * @param int|null $companyId
	 * @param array $contactIds
	 */
	public function __construct(
		?int $companyId,
		array $contactIds
	)
	{
		$this->companyId = $companyId;
		$this->contactIds = $contactIds;
	}

	/**
	 * Return array with values.
	 *
	 * @param bool $withOwner if true, the owner values will be added, but only if it is set.
	 *
	 * @return array
	 */
	public function toArray(bool $withOwner = true): array
	{
		$result = [
			'COMPANY_ID' => $this->companyId,
			'CONTACT_IDS' => $this->contactIds,
		];

		if ($withOwner && isset($this->ownerTypeId) && isset($this->ownerId))
		{
			$result['OWNER_TYPE_ID'] = $this->ownerTypeId;
			$result['OWNER_ID'] = $this->ownerId;
		}

		return $result;
	}

	/**
	 * Gets true if client (contacts or company) exists
	 *
	 * @return bool
	 */
	public function isClientExists(): bool
	{
		return $this->companyId || $this->contactIds;
	}

	/**
	 * Create instance by owner values.
	 *
	 * For some entities, owner values will also be added automatically.
	 * If you need to use them, you can set them manually.
	 *
	 * @param int $ownerTypeId
	 * @param int $ownerId
	 *
	 * @return self
	 */
	public static function createFromOwner(int $ownerTypeId, int $ownerId): self
	{
		$withOwner = false;

		$companyId = null;
		$contactIds = [];

		if ($ownerTypeId === CCrmOwnerType::Lead)
		{
			$lead = LeadTable::getById($ownerId)->fetch();
			if ($lead)
			{
				$companyId = (int)$lead['COMPANY_ID'];
				$contactIds = LeadContactTable::getLeadContactIDs($ownerId);
			}
		}
		elseif ($ownerTypeId === CCrmOwnerType::Deal)
		{
			$deal = DealTable::getById($ownerId)->fetch();
			if ($deal)
			{
				$contactIds = DealContactTable::getDealContactIDs($ownerId);
				$companyId = (int)$deal['COMPANY_ID'];
				$withOwner = true;
			}
		}
		elseif ($ownerTypeId === CCrmOwnerType::Contact)
		{
			$contactIds = [(int)$ownerId];
		}
		elseif ($ownerTypeId === CCrmOwnerType::Company)
		{
			$companyId = (int)$ownerId;
		}
		elseif ($ownerTypeId === CCrmOwnerType::Order)
		{
			$order = Order::load($ownerId);
			if ($order)
			{
				$collection = $order->getContactCompanyCollection();
				$company = $collection->getPrimaryCompany();
				if ($company)
				{
					$companyId = (int)$company->getField('ENTITY_ID');
				}

				$contacts = $collection->getContacts();
				foreach ($contacts as $contact)
				{
					$contactIds[] = (int)$contact->getField('ENTITY_ID');
				}
			}
		}
		elseif ($ownerTypeId === CCrmOwnerType::Invoice)
		{
			$invoice = CCrmInvoice::GetByID($ownerId);
			if ($invoice)
			{
				$companyID = $invoice['UF_COMPANY_ID'] ?? 0;
				if ($companyID)
				{
					$companyId = $companyID;
				}

				$contactID = $invoice['UF_CONTACT_ID'] ?? 0;
				if ($contactID)
				{
					$contactIds[] = $contactID;
				}
			}
		}
		else
		{
			$factory = Container::getInstance()->getFactory($ownerTypeId);
			if ($factory)
			{
				$item = $factory->getItem($ownerId);
				if ($item && $item->getCompanyId())
				{
					$companyId = (int)$item->getCompanyId();
				}

				if ($item && $item->getContactId())
				{
					$contactIds = [(int)$item->getContactId()];
				}
			}
			$withOwner = true;
		}

		$self = new static(
			$companyId,
			$contactIds
		);
		if ($withOwner)
		{
			$self->ownerId = $ownerId;
			$self->ownerTypeId = $ownerTypeId;
		}

		return $self;
	}
}