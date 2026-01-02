<?php

namespace Bitrix\Crm\Integration\BizProc\Starter\Mixins;

use Bitrix\Bizproc\Automation\Trigger\BaseTrigger;
use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\Automation\Trigger\Entity\TriggerTable;
use Bitrix\Crm\EntityManageFacility;
use Bitrix\Crm\Integration\BizProc\Starter\Mixins\Dto\TriggerBindingDocumentsDto;
use Bitrix\Crm\Settings\LeadSettings;

trait TriggerBindingDocumentsTrait
{
	protected static function getBindingDocuments(TriggerBindingDocumentsDto $binding): array
	{
		$bindingDocuments = [];

		$isClientBinding = false;
		if (in_array($binding->entityTypeId, [\CCrmOwnerType::Contact, \CCrmOwnerType::Company], true))
		{
			$isClientBinding = true;
		}

		if (
			!$isClientBinding
			&& Factory::isSupported($binding->entityTypeId)
			&& !($binding->entityTypeId === \CCrmOwnerType::Lead && !LeadSettings::isEnabled())
		)
		{
			$bindingDocuments[] = [$binding->entityTypeId, $binding->entityId];
		}

		if ($binding->searchClientBindings && $isClientBinding && static::getFacilitySelector())
		{
			$documents = static::getClientBindingDocuments($binding);
			foreach ($documents as $document)
			{
				if (in_array($document, $bindingDocuments, true))
				{
					continue;
				}

				$bindingDocuments[] = $document;
			}
		}

		return $bindingDocuments;
	}

	private static function getClientBindingDocuments(TriggerBindingDocumentsDto $binding): array
	{
		$facilitySelector = static::getFacilitySelector();
		if (!$facilitySelector)
		{
			return [];
		}

		$facilitySelector->setEntity($binding->entityTypeId, $binding->entityId)->search();

		$documents = [];

		$dealId = $facilitySelector->getDealId();
		if ($dealId)
		{
			$documents[] = [\CCrmOwnerType::Deal, $dealId];
		}

		$orderIds = $facilitySelector->getOrders();
		foreach ($orderIds as $orderId)
		{
			$documents[] = [\CCrmOwnerType::Order, $orderId];
		}

		if ($binding->isDynamicSearchAvailable)
		{
			$documents = array_merge($documents, static::getDynamicClientBindingDocuments($binding));
		}

		return $documents;
	}

	private static function getFacilitySelector(): ?\Bitrix\Crm\Integrity\ActualEntitySelector
	{
		static $facilitySelector = false;
		if ($facilitySelector === false)
		{
			$facilitySelector = (new EntityManageFacility())->getSelector();
		}

		return $facilitySelector;
	}

	private static function getDynamicClientBindingDocuments(TriggerBindingDocumentsDto $binding): array
	{
		$ownerIdFieldName = '';
		if ($binding->entityTypeId === \CCrmOwnerType::Contact)
		{
			$ownerIdFieldName = 'CONTACT_ID';
		}
		elseif ($binding->entityTypeId === \CCrmOwnerType::Company)
		{
			$ownerIdFieldName = 'COMPANY_ID';
		}

		if (empty($ownerIdFieldName))
		{
			return [];
		}

		$documents = [];
		foreach (static::getEntityTypeIdsByTriggerCode($binding->triggerCode) as $typeId)
		{
			if (!\CCrmOwnerType::isPossibleDynamicTypeId($typeId) || !Factory::isSupported($typeId))
			{
				continue;
			}

			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($typeId);
			if (!$factory)
			{
				continue;
			}

			$items = $factory->getItems([
				'select' => ['ID'],
				'filter' => ['=' . $ownerIdFieldName => $binding->entityId],
			]);

			foreach ($items as $item)
			{
				$documents[] = [(int)$typeId, $item->getId()];
			}
		}

		return $documents;
	}

	private static function getEntityTypeIdsByTriggerCode(string $triggerCode): array
	{
		if (empty($triggerCode) || $triggerCode === BaseTrigger::getCode())
		{
			return [];
		}

		$entityTypeIds = TriggerTable::getList([
			'select' => ['ENTITY_TYPE_ID'],
			'filter' => [
				'=CODE' => $triggerCode
			],
			'cache' => [
				'ttl' => '7200'
			]]
		)->fetchAll();

		return array_unique(array_column($entityTypeIds, 'ENTITY_TYPE_ID'));
	}
}
