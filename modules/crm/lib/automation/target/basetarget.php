<?php
namespace Bitrix\Crm\Automation\Target;

use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Loader;
use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\Automation\Trigger\Entity\TriggerTable;

if (!Loader::includeModule('bizproc'))
{
	return;
}

abstract class BaseTarget extends \Bitrix\Bizproc\Automation\Target\BaseTarget
{
	protected $entity;

	/**
	 * @param $entity
	 * @return $this
	 */
	public function setEntity($entity)
	{
		$this->entity = $entity;
		return $this;
	}

	public function setEntityField($field, $value)
	{
		if ($this->entity === null)
		{
			throw new InvalidOperationException('Entity must be set by setEntity method.');
		}

		$this->entity[$field] = $value;
	}

	/**
	 * @return mixed
	 */
	public function getEntity()
	{
		if ($this->entity === null)
			return array();

		return $this->entity;
	}

	public function getEntityTypeId()
	{
		return \CCrmOwnerType::Undefined;
	}

	abstract public function getEntityId();
	abstract public function setEntityById($id);
	abstract public function getResponsibleId();

	abstract public function getEntityStatus();
	abstract public function setEntityStatus($statusId);

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

	public function setDocumentStatus($statusId)
	{
		return $this->setEntityStatus($statusId);
	}

	public function getTriggers(array $statuses)
	{
		$result = [];
		$iterator = TriggerTable::getList(array(
			'filter' => array(
				'=ENTITY_TYPE_ID' => $this->getEntityTypeId(),
				'@ENTITY_STATUS' => $statuses
			)
		));

		while ($row = $iterator->fetch())
		{
			$result[] = [
				'DOCUMENT_TYPE' => $this->getDocumentType(),
				'DOCUMENT_STATUS' => $row['ENTITY_STATUS'],

				'ID' => $row['ID'],
				'NAME' => $row['NAME'],
				'CODE' => $row['CODE'],
				'APPLY_RULES' => $row['APPLY_RULES']
			];
		}

		return $result;
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
				TriggerTable::update($triggerId, array(
					'NAME' => $trigger['NAME'],
					'ENTITY_STATUS' => $trigger['DOCUMENT_STATUS'],
					'APPLY_RULES' => is_array($trigger['APPLY_RULES']) ? $trigger['APPLY_RULES'] : null
				));
			}
			elseif (isset($trigger['CODE']) && isset($trigger['DOCUMENT_STATUS']))
			{
				$triggerClass = Factory::getTriggerByCode($trigger['CODE']);
				if (!$triggerClass)
					continue;

				$addResult = TriggerTable::add(array(
					'NAME' => $trigger['NAME'],
					'ENTITY_TYPE_ID' => $this->getEntityTypeId(),
					'ENTITY_STATUS' => $trigger['DOCUMENT_STATUS'],
					'CODE' => $trigger['CODE'],
					'APPLY_RULES' => is_array($trigger['APPLY_RULES']) ? $trigger['APPLY_RULES'] : null
				));

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
}