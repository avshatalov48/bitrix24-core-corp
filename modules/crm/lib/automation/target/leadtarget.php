<?php
namespace Bitrix\Crm\Automation\Target;

use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\PhaseSemantics;

class LeadTarget extends BaseTarget
{
	protected $entityStatuses;

	public function isAvailable()
	{
		return Factory::isAutomationAvailable(\CCrmOwnerType::Lead);
	}

	public function canTriggerSetExecuteBy(): bool
	{
		return true;
	}

	public function getEntityTypeId()
	{
		return \CCrmOwnerType::Lead;
	}

	public function getEntityId()
	{
		$entity = $this->getEntity();
		return isset($entity['ID']) ? (int)$entity['ID'] : 0;
	}

	public function getResponsibleId()
	{
		$entity = $this->getEntity();
		return isset($entity['ASSIGNED_BY_ID']) ? (int)$entity['ASSIGNED_BY_ID'] : 0;
	}

	public function setEntityById($id)
	{
		$id = (int)$id;
		if ($id > 0)
		{
			$entity = \CCrmLead::GetByID($id, false);
			if ($entity)
			{
				$this->setEntity($entity);
				$this->setDocumentId('LEAD_'.$id);
			}
		}
	}

	public function getEntity()
	{
		if ($this->entity === null && $id = $this->getDocumentId())
		{
			$id = (int) str_replace('LEAD_', '', $id);
			$this->setEntityById($id);
		}

		return parent::getEntity();
	}

	public function getEntityStatus()
	{
		$entity = $this->getEntity();
		return isset($entity['STATUS_ID']) ? $entity['STATUS_ID'] : '';
	}

	public function setEntityStatus($statusId, $executeBy = null)
	{
		$id = $this->getEntityId();

		$fields = ['STATUS_ID' => $statusId];
		if ($executeBy)
		{
			$fields['MODIFY_BY_ID'] = $executeBy;
		}

		$CCrmLead = new \CCrmLead(false);
		$CCrmLead->Update($id, $fields, true, true, array(
			'DISABLE_USER_FIELD_CHECK' => true,
			'REGISTER_SONET_EVENT' => true,
			'CURRENT_USER' => $executeBy ?? 0 //System user
		));

		$this->setEntityField('STATUS_ID', $statusId);
	}

	public function getEntityStatuses()
	{
		if ($this->entityStatuses === null)
		{
			$this->entityStatuses = array_keys(\CCrmStatus::GetStatusList('STATUS'));
		}

		return $this->entityStatuses;
	}

	public function getStatusInfos($categoryId = 0)
	{
		$processColor = \CCrmViewHelper::PROCESS_COLOR;
		$successColor = \CCrmViewHelper::SUCCESS_COLOR;
		$failureColor = \CCrmViewHelper::FAILURE_COLOR;

		$statuses = \CCrmViewHelper::GetLeadStatusInfos();

		foreach ($statuses as $id => $statusInfo)
		{
			if (!empty($statusInfo['COLOR']))
				continue;

			$semanticId = \CAllCrmLead::GetSemanticID($statusInfo["STATUS_ID"]);

			if ($semanticId == PhaseSemantics::PROCESS)
				$statuses[$id]["COLOR"] = $processColor;
			else if ($semanticId == PhaseSemantics::FAILURE)
				$statuses[$id]["COLOR"] = $failureColor;
			else if ($semanticId == PhaseSemantics::SUCCESS)
				$statuses[$id]["COLOR"] = $successColor;
		}

		return $statuses;
	}
}
