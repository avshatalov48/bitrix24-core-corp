<?php

namespace Bitrix\Crm\Integration\AI\Operation\Payload\Payload;

use Bitrix\Crm\Activity\Provider\Call;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Copilot\CallAssessment\AssessmentClientTypeResolver;
use Bitrix\Crm\EO_Activity;
use Bitrix\Crm\EO_Company;
use Bitrix\Crm\EO_Contact;
use Bitrix\Crm\EO_Deal;
use Bitrix\Crm\EO_Lead;
use Bitrix\Crm\Integration\AI\Operation\Orchestrator;
use Bitrix\Crm\Integration\AI\Operation\Payload\CalcMarkersInterface;
use Bitrix\Crm\Integration\AI\Operation\Payload\PayloadInterface;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Entity\ReferenceField;
use CCrmOwnerType;

final class CallScoring extends AbstractPayload implements CalcMarkersInterface
{
	private ItemIdentifier|null $ownerIdentifier = null;
	private EO_Deal|EO_Lead|null $ownerItem = null;
	private EO_Company|EO_Contact|null $clientItem = null;
	
	private bool $isContactClient = false;
	private bool $isCompanyClient = false;
	
	public function getPayloadCode(): string
	{
		return 'call_scoring';
	}
	
	public function setMarkers(array $markers): PayloadInterface
	{
		$this->markers = array_merge($markers, $this->calcMarkers());
		
		return $this;
	}

	public function calcMarkers(): array
	{
		$this->initOwnerData();
		
		$activity = $this->getActivity();
		$lastItem = $this->getLastClientItem();
		$lastCall = $this->getLastCall((int)($activity['ID'] ?? 0));
		
		return [
			'client_type' => $this->getClientTypeName(),
			'manager_name' => $this->getUserName((int)($activity['RESPONSIBLE_ID'] ?? 0)),
			'manager_name_entity' => $this->getUserName($this->ownerItem?->getAssignedById()),
			'manager_name_client' => $this->getUserName($this->clientItem?->getAssignedById()),
			'last_entity_datetime' => $lastItem?->getCreatedTime()?->toString() ?? '',
			'last_entity_opportunity' => $this->getEntityOpportunity($lastItem),
			'company_name' => $this->getClientCompanyName(),
			'last_call_datetime' => $lastCall ? $lastCall->getCreated()->toString() : '',
			'last_call_manager_name' => $lastCall ? $this->getUserName($lastCall->getResponsibleId()) : '',
		];
	}
	
	private function initOwnerData(): void
	{
		$ownerIdentifier = (new Orchestrator())->findPossibleFillFieldsTarget($this->identifier->getEntityId());
		if (!$ownerIdentifier)
		{
			return;
		}

		$this->ownerIdentifier = $ownerIdentifier;
		$this->ownerItem = Container::getInstance()
			->getEntityBroker($ownerIdentifier->getEntityTypeId())
			?->getById($ownerIdentifier->getEntityId())
		;
		if ($this->ownerItem instanceof EO_Deal || $this->ownerItem instanceof EO_Lead)
		{
			$contactId = $this->ownerItem->getContactId();
			$companyId = $this->ownerItem->getCompanyId();
			
			if ($contactId)
			{
				$this->clientItem = Container::getInstance()->getContactBroker()->getById($contactId);
			}

			if (!$this->clientItem && $companyId)
			{
				$this->clientItem = Container::getInstance()->getCompanyBroker()->getById($companyId);
			}
			
			$this->isContactClient = $this->clientItem instanceof EO_Contact;
			$this->isCompanyClient = $this->clientItem instanceof EO_Company;
		}
	}
	
	private function getClientTypeName(): string
	{
		$clientType = (new AssessmentClientTypeResolver())
			->resolveByActivityId($this->identifier->getEntityId())
		;
		
		return $clientType?->name ?? '';
	}
	
	private function getEntityOpportunity(?Item $item): string
	{
		if ($item === null)
		{
			return '';
		}
		
		$opportunity = $item->getOpportunity();
		if ($opportunity <= 0)
		{
			return '';
		}
		
		$currencyId = $item->getCurrencyId();

		return $currencyId
			? sprintf('%.2f %s', $opportunity, $currencyId)
			: (string)$opportunity
		;
	}

	private function getClientCompanyName(): string
	{
		if ($this->isContactClient)
		{
			$companyId = $this->clientItem->getCompanyId();

			return $companyId ? $this->getCompanyName($companyId) : '';
		}
		
		if ($this->isCompanyClient)
		{
			return $this->clientItem->getTitle() ?? '';
		}
		
		return '';
	}
	
	private function getLastClientItem(): Item|null
	{
		if ($this->isContactClient)
		{
			$filter = [
				'=' . Item::FIELD_NAME_CONTACT_BINDINGS . '.CONTACT_ID' => $this->clientItem->getId(),
				'!=' . Item::FIELD_NAME_ID => $this->ownerItem->getId(),
			];
		}
		
		if ($this->isCompanyClient)
		{
			$filter = [
				'=' . Item::FIELD_NAME_COMPANY_ID => $this->clientItem->getId(),
				'!=' . Item::FIELD_NAME_ID => $this->ownerItem->getId(),
			];
		}
		
		if (!isset($filter))
		{
			return null;
		}
		
		$factory = Container::getInstance()->getFactory($this->ownerIdentifier?->getEntityTypeId());
		if (
			!$factory?->isStagesEnabled()
			|| !$factory?->isLinkWithProductsEnabled()
			|| !$factory?->isClientEnabled()
		)
		{
			return null;
		}
		
		$select = [
			Item::FIELD_NAME_CREATED_TIME,
			Item::FIELD_NAME_OPPORTUNITY,
			Item::FIELD_NAME_CURRENCY_ID,
		];
		$order = [
			Item::FIELD_NAME_CREATED_TIME => 'DESC',
		];

		$items = $factory?->getItems([
			'select' => $select,
			'filter' => $filter,
			'order' => $order,
			'limit' => 1,
		]);
		
		return $items[0] ?? null;
	}
	
	private function getLastCall(int $currentActivityId): EO_Activity|null
	{
		if ($currentActivityId <= 0)
		{
			return null;
		}
		
		if ($this->isContactClient)
		{
			$entityTypeId = CCrmOwnerType::Contact;
		}
		elseif ($this->isCompanyClient)
		{
			$entityTypeId = CCrmOwnerType::Company;
		}
		
		if (!isset($entityTypeId))
		{
			return null;
		}

		return ActivityTable::query()
			->setSelect(['ID', 'RESPONSIBLE_ID', 'CREATED'])
			->registerRuntimeField(
				'',
				new ReferenceField('B',
					ActivityBindingTable::getEntity(),
					['=ref.ACTIVITY_ID' => 'this.ID'],
				)
			)
			->where('B.OWNER_ID', '=', $this->clientItem->getId())
			->where('B.OWNER_TYPE_ID', '=', $entityTypeId)
			->where('PROVIDER_ID', Call::ACTIVITY_PROVIDER_ID)
			->whereNot('ID', $currentActivityId)
			->whereNotNull('ORIGIN_ID')
			->addOrder('CREATED', 'DESC')
			->setLimit(1)
			->fetchObject()
		;
	}
}
