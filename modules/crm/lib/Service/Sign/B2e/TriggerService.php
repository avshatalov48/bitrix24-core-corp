<?php

namespace Bitrix\Crm\Service\Sign\B2e;

use Bitrix\Crm\Automation\Trigger\Entity\TriggerTable;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\CompletedTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\CoordinationAndFillingTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\SigningTrigger;
use CCrmOwnerType;
use ReflectionClass;

/**
 * Service for working with b2e triggers.
 */
final class TriggerService
{
	public function getDefaultTriggers(): array
	{
		return [
			SigningTrigger::class => 'SIGNING',
			CoordinationAndFillingTrigger::class => 'COORDINATION_AND_FILLING',
			CompletedTrigger::class => 'COMPLETED',
		];
	}

	/**
	 * @param array<string, string> $triggers
	 */
	public function addTriggers(array $triggers): void
	{
		foreach ($triggers as $className => $status)
		{
			$data = $this->prepareData($className, $status);
			TriggerTable::add($data);
		}
	}

	private function prepareData(string $className, string $status): array
	{
		$triggerClass = new ReflectionClass($className);
		$triggerInstance = $triggerClass->newInstanceWithoutConstructor();

		return [
			'NAME' => $triggerInstance->getName(),
			'CODE' => $triggerInstance->getCode(),
			'ENTITY_TYPE_ID' => CCrmOwnerType::SmartB2eDocument,
			'ENTITY_STATUS' => $status,
			'APPLY_RULES' => '',
		];
	}

	public function isTriggersCreated(): bool
	{
		$result = TriggerTable::query()
			->addSelect('ID')
			->where('ENTITY_TYPE_ID', CCrmOwnerType::SmartB2eDocument)
			->setLimit(1)
			->fetchObject()
		;

		return is_object($result);
	}

	public function removeAll(): void
	{
		TriggerTable::deleteByEntityTypeId(CCrmOwnerType::SmartB2eDocument);
	}
}
