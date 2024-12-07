<?php

namespace Bitrix\Crm\Automation\ClientCommunications;

use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Service\Container;

final class ClientCommunications
{
	private int $entityTypeId;
	private int $entityId;
	private string $type;

	public function __construct(int $entityTypeId, int $entityId, string $type)
	{
		$this->entityTypeId = $entityTypeId;
		$this->entityId = $entityId;
		$this->type = $type;
	}

	public function getFirstFilled(string $valueType = null): array
	{
		if ($this->entityTypeId === \CCrmOwnerType::Deal)
		{
			return $this->getDealClientCommunications($valueType);
		}

		if ($this->entityTypeId === \CCrmOwnerType::Lead)
		{
			return $this->getLeadClientCommunications($valueType);
		}

		if ($this->entityTypeId === \CCrmOwnerType::Order)
		{
			return $this->getOrderClientCommunications($valueType);
		}

		if ($this->entityTypeId === \CCrmOwnerType::Contact || $this->entityTypeId === \CCrmOwnerType::Company)
		{
			return $this->getCommunicationsFromFieldMulti($this->entityTypeId, $this->entityId, $valueType);
		}

		return $this->getItemClientCommunications($valueType);
	}

	public function getLastFilled(string $valueType = null): array
	{
		$idDirection = 'desc';

		if ($this->entityTypeId === \CCrmOwnerType::Deal)
		{
			return $this->getDealClientCommunications($valueType, $idDirection);
		}

		if ($this->entityTypeId === \CCrmOwnerType::Lead)
		{
			return $this->getLeadClientCommunications($valueType, $idDirection);
		}

		if ($this->entityTypeId === \CCrmOwnerType::Order)
		{
			return $this->getOrderClientCommunications($valueType, $idDirection);
		}

		if ($this->entityTypeId === \CCrmOwnerType::Contact || $this->entityTypeId === \CCrmOwnerType::Company)
		{
			return $this->getCommunicationsFromFieldMulti($this->entityTypeId, $this->entityId, $valueType, $idDirection);
		}

		return $this->getItemClientCommunications($valueType, $idDirection);
	}

	private function getDealClientCommunications(string $valueType = null, string $idDirection = 'asc'): array
	{
		$deal = $this->entityId > 0 ? \CCrmDeal::GetByID($this->entityId, false) : null;
		if (!$deal)
		{
			return [];
		}

		$generalContactId = (int)($deal['CONTACT_ID'] ?? 0);
		if ($generalContactId > 0)
		{
			$communications = $this->getCommunicationsFromFieldMulti(
				\CCrmOwnerType::Contact, $generalContactId, $valueType, $idDirection
			);
			if ($communications)
			{
				return $communications;
			}
		}

		$dealContactIds = DealContactTable::getDealContactIDs($this->entityId);
		if ($dealContactIds)
		{
			foreach ($dealContactIds as $contactId)
			{
				if ($contactId !== $generalContactId)
				{
					$communications = $this->getCommunicationsFromFieldMulti(
						\CCrmOwnerType::Contact, $contactId, $valueType, $idDirection
					);
					if ($communications)
					{
						return $communications;
					}
				}
			}
		}

		$companyId = (int)($deal['COMPANY_ID'] ?? 0);
		if ($companyId > 0)
		{
			return $this->getCommunicationsFromFieldMulti(
				\CCrmOwnerType::Company, $companyId, $valueType, $idDirection
			);
		}

		return [];
	}

	private function getLeadClientCommunications(string $valueType = null, string $idDirection = 'asc'): array
	{
		if ($this->entityId <= 0)
		{
			return [];
		}

		$communications = $this->getCommunicationsFromFieldMulti(
			\CCrmOwnerType::Lead, $this->entityId, $valueType, $idDirection
		);
		if ($communications)
		{
			return $communications;
		}

		$lead = \CCrmLead::GetByID($this->entityId, false);
		if (!$lead)
		{
			return [];
		}

		$contactId = (int)($lead['CONTACT_ID'] ?? 0);
		if ($contactId > 0)
		{
			$communications = $this->getCommunicationsFromFieldMulti(
				\CCrmOwnerType::Contact, $contactId, $valueType, $idDirection
			);
			if ($communications)
			{
				return $communications;
			}
		}

		$companyId = (int)($lead['COMPANY_ID'] ?? 0);
		if ($companyId > 0)
		{
			return $this->getCommunicationsFromFieldMulti(
				\CCrmOwnerType::Company, $companyId, $valueType, $idDirection
			);
		}

		return [];
	}

	private function getOrderClientCommunications(string $valueType = null, string $idDirection = 'asc'): array
	{
		if ($this->entityId <= 0)
		{
			return [];
		}

		$iterator = \Bitrix\Crm\Order\ContactCompanyCollection::getList([
			'select' => ['ENTITY_ID', 'ENTITY_TYPE_ID'],
			'filter' => [
				'=ORDER_ID' => $this->entityId,
				'@ENTITY_TYPE_ID' => [\CCrmOwnerType::Contact, \CCrmOwnerType::Company],
				'=IS_PRIMARY' => 'Y',
			],
			'order' => ['ENTITY_TYPE_ID' => 'ASC'],
		]);
		while ($row = $iterator->fetch())
		{
			$communications = $this->getCommunicationsFromFieldMulti(
				(int)$row['ENTITY_TYPE_ID'], (int)$row['ENTITY_ID'], $valueType, $idDirection
			);
			if ($communications)
			{
				return $communications;
			}
		}

		return [];
	}

	private function getItemClientCommunications(string $valueType = null, string $idDirection = 'asc'): array
	{
		$factory = Container::getInstance()->getFactory($this->entityTypeId);
		$item = $factory && $this->entityId > 0 ? $factory->getItem($this->entityId) : null;
		if (!$item)
		{
			return [];
		}

		$contactBindings = $item->getContactBindings();
		foreach ($contactBindings as $binding)
		{
			$contactId = (int)($binding['CONTACT_ID'] ?? 0);
			if ($contactId > 0)
			{
				$communications = $this->getCommunicationsFromFieldMulti(
					\CCrmOwnerType::Contact, $contactId, $valueType, $idDirection
				);
				if ($communications)
				{
					return $communications;
				}
			}
		}

		$companyId = $item->getCompanyId();
		if ($companyId > 0)
		{
			return $this->getCommunicationsFromFieldMulti(
				\CCrmOwnerType::Company, $companyId, $valueType, $idDirection
			);
		}

		return [];
	}

	private function getCommunicationsFromFieldMulti(
		int $entityTypeId,
		int $entityId,
		string $valueType = null,
		string $idDirection = 'asc'
	): array
	{
		if ($entityId <= 0)
		{
			return [];
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);

		$filter = [
			'ENTITY_ID' => $entityTypeName,
			'ELEMENT_ID' => $entityId,
			'TYPE_ID' => $this->type,
		];
		if ($valueType)
		{
			$filter['VALUE_TYPE'] = $valueType;
		}

		$iterator = \CCrmFieldMulti::GetList(['ID' => $idDirection], $filter);

		$communications = [];
		while ($row = $iterator->fetch())
		{
			if (empty($row['VALUE']))
			{
				continue;
			}

			$communications[] = [
				'ENTITY_ID' => $entityId,
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_TYPE' => $entityTypeName,
				'TYPE' => $this->type,
				'VALUE' => $row['VALUE'],
				'VALUE_TYPE' => $row['VALUE_TYPE'] ?? null,
			];
		}

		return $communications;
	}
}
