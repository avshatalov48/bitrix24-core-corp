<?php

namespace Bitrix\Crm\Automation\Target;

use Bitrix\Bizproc\Automation\Engine\ConditionGroup;
use Bitrix\Crm\Automation\Engine\TemplatesScheme;
use Bitrix\Crm\Automation\Trigger\Entity\EO_Trigger;
use Bitrix\Main\Loader;
use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\Automation\Trigger\Entity\TriggerTable;

if (!Loader::includeModule('bizproc'))
{
	return;
}

abstract class BaseTarget extends \Bitrix\Bizproc\Automation\Target\BaseTarget
{
	protected $entityId;

	/**
	 * @param $entityId
	 * @return $this
	 */
	public function setEntityId($entityId)
	{
		$this->entityId = (int)$entityId;

		return $this;
	}

	abstract protected function getEntityIdByDocumentId(string $documentId): int;

	abstract protected function getEntityFields(array $select): array;

	public function getEntityTypeId()
	{
		return \CCrmOwnerType::Undefined;
	}

	public function getEntityId(): int
	{
		if ($this->entityId)
		{
			return $this->entityId;
		}

		$documentId = $this->getDocumentId();
		if (empty($this->entityId) && $documentId)
		{
			return $this->getEntityIdByDocumentId($documentId);
		}

		return 0;
	}

	public function getResponsibleId()
	{
		return \CCrmOwnerType::loadResponsibleId(
			$this->getEntityTypeId(),
			$this->getEntityId()
		);
	}

	abstract public function getEntityStatus();

	public function setEntityStatus($statusId, $executeBy = null)
	{
		return;
	}

	public function getDocumentStatus()
	{
		return $this->getEntityStatus();
	}

	abstract public function getStatusInfos($categoryId = 0);

	public function getDocumentStatusList($categoryId = 0)
	{
		return $this->getStatusInfos($categoryId);
	}

	abstract public function getEntityStatuses();

	public function setDocumentStatus($statusId, $executeBy = null)
	{
		$this->setEntityStatus($statusId, $executeBy);
	}

	public function getTriggers(array $statuses)
	{
		$result = [];
		$iterator = TriggerTable::getList([
			'filter' => [
				'=ENTITY_TYPE_ID' => $this->getEntityTypeId(),
				'@ENTITY_STATUS' => $statuses,
			],
		]);

		while ($row = $iterator->fetch())
		{
			$result[] = [
				'DOCUMENT_TYPE' => $this->getDocumentType(),
				'DOCUMENT_STATUS' => $row['ENTITY_STATUS'],

				'ID' => $row['ID'],
				'NAME' => $row['NAME'],
				'CODE' => $row['CODE'],
				'APPLY_RULES' => is_array($row['APPLY_RULES']) ? $row['APPLY_RULES'] : [],
			];
		}

		return $result;
	}

	/**
	 * @param string[] $statuses
	 * @return EO_Trigger[]
	 */
	public function getTriggerObjects(array $statuses): array
	{
		$iterator = TriggerTable::getList([
			'filter' => [
				'=ENTITY_TYPE_ID' => $this->getEntityTypeId(),
				'@ENTITY_STATUS' => $statuses,
			],
		]);

		return $iterator->fetchCollection()->getAll();
	}

	public function getAvailableTriggers()
	{
		return Factory::getAvailableTriggers($this->getEntityTypeId());
	}

	public function setTriggers(array $triggers)
	{
		$updatedTriggers = [];
		foreach ($triggers as $trigger)
		{
			$triggerId = isset($trigger['ID']) ? (int)$trigger['ID'] : 0;

			if (isset($trigger['DELETED']) && $trigger['DELETED'] === 'Y')
			{
				if ($triggerId > 0)
				{
					TriggerTable::delete($triggerId);
				}
				continue;
			}

			if ($triggerId > 0)
			{
				TriggerTable::update($triggerId, [
					'NAME' => $trigger['NAME'],
					'ENTITY_STATUS' => $trigger['DOCUMENT_STATUS'],
					'APPLY_RULES' => is_array($trigger['APPLY_RULES']) ? $trigger['APPLY_RULES'] : null,
				]);
			}
			elseif (isset($trigger['CODE']) && isset($trigger['DOCUMENT_STATUS']))
			{
				$triggerClass = Factory::getTriggerByCode($trigger['CODE']);
				if (!$triggerClass)
				{
					continue;
				}

				$addResult = TriggerTable::add([
					'NAME' => $trigger['NAME'],
					'ENTITY_TYPE_ID' => $this->getEntityTypeId(),
					'ENTITY_STATUS' => $trigger['DOCUMENT_STATUS'],
					'CODE' => $trigger['CODE'],
					'APPLY_RULES' => is_array($trigger['APPLY_RULES']) ? $trigger['APPLY_RULES'] : null,
				]);

				if ($addResult->isSuccess())
				{
					$trigger['ID'] = $addResult->getId();
				}
			}
			$updatedTriggers[] = $trigger;
		}

		return $updatedTriggers;
	}

	public function getDocumentType()
	{
		return \CCrmBizProcHelper::ResolveDocumentType($this->getEntityTypeId());
	}

	public function prepareTriggersToSave(array &$triggers)
	{
		parent::prepareTriggersToSave($triggers);

		foreach ($triggers as $i => $trigger)
		{
			if (isset($trigger['DELETED']) && $trigger['DELETED'] === 'Y')
			{
				continue;
			}

			$triggers[$i]['APPLY_RULES'] = $this->prepareApplyRules($trigger['APPLY_RULES']);
		}
	}

	public function prepareTriggersToShow(array &$triggers)
	{
		parent::prepareTriggersToShow($triggers);
		foreach ($triggers as $i => $trigger)
		{
			$triggers[$i]['APPLY_RULES'] = $this->prepareApplyRules($trigger['APPLY_RULES'], true);
		}
	}

	private function prepareApplyRules($rules, $external = false): ?array
	{
		if (!is_array($rules))
		{
			return null;
		}

		foreach (['shipmentCondition', 'taskCondition', 'imolCondition'] as $key)
		{
			if (isset($rules[$key]))
			{
				$condition = new ConditionGroup($rules[$key]);
				$docType = $this->getConditionDocumentType($key);
				if ($external)
				{
					$condition->externalizeValues($docType);
				}
				else
				{
					$condition->internalizeValues($docType);
				}
				$rules[$key] = $condition->toArray();
			}
		}

		return $rules;
	}

	private function getConditionDocumentType($conditionId)
	{
		$docType = $this->getDocumentType();
		switch ($conditionId)
		{
			case 'shipmentCondition':
				$docType = \CCrmBizProcHelper::ResolveDocumentType(\CCrmOwnerType::OrderShipment);
				break;
			case 'taskCondition':
				if (Loader::includeModule('tasks'))
				{
					$docType = ['tasks', \Bitrix\Tasks\Integration\Bizproc\Document\Task::class, 'TASK'];
				}
				break;
			case 'imolCondition':
				$docType = \Bitrix\Crm\Automation\Trigger\OpenLineAnswerControlTrigger::getConditionDocumentType();
				break;
		}
		return $docType;
	}

	public function getTemplatesScheme(): ?\Bitrix\Bizproc\Automation\Engine\TemplatesScheme
	{
		$templateScheme = new TemplatesScheme();
		$templateScheme->build();

		return $templateScheme;
	}
}