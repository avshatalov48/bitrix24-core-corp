<?php

namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\Automation\Trigger\Entity\TriggerTable;
use Bitrix\Crm\EntityManageFacility;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Main;

if (!Main\Loader::includeModule('bizproc'))
{
	return;
}

class BaseTrigger extends \Bitrix\Bizproc\Automation\Trigger\BaseTrigger
{
	protected $inputData;

	/**
	 * @param int $entityTypeId Target entity id
	 * @return bool
	 */
	public static function isSupported($entityTypeId)
	{
		$supported = [
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Order,
			\CCrmOwnerType::Invoice,
			\CCrmOwnerType::Quote,
			\CCrmOwnerType::SmartInvoice,
			\CCrmOwnerType::SmartDocument,
		];

		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);

			return
				static::areDynamicTypesSupported()
				&& !is_null($factory)
				&& $factory->isAutomationEnabled()
				&& $factory->isStagesEnabled()
			;
		}

		return in_array($entityTypeId, $supported, true);
	}

	protected static function areDynamicTypesSupported(): bool
	{
		return true;
	}

	public static function execute(array $bindings, array $inputData = null, bool $useEntitySearch = true)
	{
		$triggersSent = false;
		$triggersApplied = false;
		$clientBindings = [];
		$bindingDocuments = [];

		$result = new Main\Result();

		foreach ($bindings as $binding)
		{
			$entityTypeId = (int)$binding['OWNER_TYPE_ID'];
			$entityId = (int)$binding['OWNER_ID'];

			if ($entityTypeId === \CCrmOwnerType::Contact || $entityTypeId === \CCrmOwnerType::Company)
			{
				$clientBindings[] = $binding;

				continue;
			}

			$bindingDocuments[] = [$entityTypeId, $entityId];

			if (Factory::isSupported($entityTypeId))
			{
				if ($entityTypeId === \CCrmOwnerType::Lead && !LeadSettings::isEnabled())
				{
					continue;
				}

				$triggersApplied = static::sendTrigger([$entityTypeId, $entityId], $inputData);
				$triggersSent = true;
			}
		}

		if ($clientBindings && $useEntitySearch)
		{
			$facilitySelector = (new EntityManageFacility())->getSelector();

			foreach ($clientBindings as $binding)
			{
				if (!$facilitySelector)
				{
					break;
				}

				$facilitySelector
					->setEntity($binding['OWNER_TYPE_ID'], $binding['OWNER_ID'])
					->search()
				;

				$documents = [];

				$dealId = $facilitySelector->getDealId();
				$orderIds = $facilitySelector->getOrders();

				if ($dealId)
				{
					$documents[] = [\CCrmOwnerType::Deal, $dealId];
				}
				foreach ($orderIds as $orderId)
				{
					$documents[] = [\CCrmOwnerType::Order, $orderId];
				}

				if (static::areDynamicTypesSupported())
				{
					$entityTypeIdsWithTrigger = static::getEntityTypeIdsBySelfCode();

					$ownerIdFieldName = '';
					if ($binding['OWNER_TYPE_ID'] === \CCrmOwnerType::Contact)
					{
						$ownerIdFieldName = 'CONTACT_ID';
					}
					elseif ($binding['OWNER_TYPE_ID'] === \CCrmOwnerType::Company)
					{
						$ownerIdFieldName = 'COMPANY_ID';
					}

					foreach ($entityTypeIdsWithTrigger as $entityTypeIdWithTrigger)
					{
						if (empty($ownerIdFieldName))
						{
							break;
						}

						if (
							!\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeIdWithTrigger)
							|| !Factory::isSupported($entityTypeIdWithTrigger)
						)
						{
							continue;
						}

						$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeIdWithTrigger);
						if (!$factory)
						{
							continue;
						}

						$items = $factory->getItems([
							'select' => ['ID'],
							'filter' => [
								'=' . $ownerIdFieldName => (int)$binding['OWNER_ID'],
							],
						]);

						foreach ($items as $item)
						{
							$documents[] = [(int)$entityTypeIdWithTrigger, $item->getId()];
						}

					}
				}

				foreach ($documents as $document)
				{
					if (in_array($document, $bindingDocuments, true))
					{
						continue;
					}

					if (static::sendTrigger($document, $inputData))
					{
						$triggersApplied = true;
					}
					$triggersSent = true;
				}
			}
		}

		$result->setData(['triggersSent' => $triggersSent, 'triggersApplied' => $triggersApplied]);

		return $result;
	}

	public function setInputData($data)
	{
		$this->inputData = $data;

		return $this;
	}

	public function getInputData($key = null)
	{
		if ($key !== null)
		{
			return is_array($this->inputData) && isset($this->inputData[$key]) ? $this->inputData[$key] : null;
		}

		return $this->inputData;
	}

	protected static function sendTrigger(array $document, array $inputData = null)
	{
		[$entityTypeId, $entityId] = $document;
		if (!Factory::isAutomationRunnable($entityTypeId))
		{
			return false;
		}

		$automationTarget = Factory::getTarget($entityTypeId, $entityId);
		$trigger = new static();
		$trigger->setTarget($automationTarget);
		if ($inputData !== null)
		{
			$trigger->setInputData($inputData);
		}

		return $trigger->send($entityId);
	}

	public function send($entityId)
	{
		$applied = false;
		$triggers = $this->getPotentialTriggers();
		if ($triggers)
		{
			foreach ($triggers as $trigger)
			{
				if ($this->checkApplyRules($trigger))
				{
					$this->applyTrigger($trigger);
					$applied = true;

					break;
				}
			}
		}

		return $applied;
	}

	protected function applyTrigger(array $trigger)
	{
		$statusId = $trigger['DOCUMENT_STATUS'];

		/** @var \Bitrix\Crm\Automation\Target\BaseTarget $target */
		$target = $this->getTarget();

		if (is_callable([$this, 'getReturnValues']))
		{
			$trigger['RETURN'] = $this->getReturnValues();
		}

		$executeBy = null;

		if (isset($trigger['APPLY_RULES']['ExecuteBy']))
		{
			$docId = $target->getDocumentType();
			$docId[2] = $target->getDocumentId();
			$executeBy = \CBPHelper::ExtractUsers($trigger['APPLY_RULES']['ExecuteBy'], $docId, true);
		}

		$target->setAppliedTrigger($trigger);
		$result = $target->setEntityStatus($statusId, $executeBy);

		//Fake document update for clearing document cache
		$ds = \CBPRuntime::GetRuntime(true)->getDocumentService();
		$ds->UpdateDocument($target->getComplexDocumentId(), []);

		if ($result !== false)
		{
			Factory::onFieldsChanged(
				$target->getEntityTypeId(),
				$target->getEntityId(),
				[$target->getEntityTypeId() === \CCrmOwnerType::Lead ? 'STATUS_ID' : 'STAGE_ID']
			);
			Factory::runOnStatusChanged($target->getEntityTypeId(), $target->getEntityId());
		}

		return true;
	}

	protected static function getEntityTypeIdsBySelfCode(): array
	{
		$triggerCode = static::getCode();
		if (empty($triggerCode) || $triggerCode === self::getCode())
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
			]
		])->fetchAll();

		return array_unique(array_column($entityTypeIds, 'ENTITY_TYPE_ID'));
	}
}
