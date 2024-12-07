<?php
namespace Bitrix\Crm\Automation\Target;

use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\StatusTable;

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

	protected function getEntityIdByDocumentId(string $documentId): int
	{
		return (int)str_replace('LEAD_', '', $documentId);
	}

	protected function getEntityFields(array $select): array
	{
		$id = $this->getEntityId();
		if (empty($id))
		{
			return [];
		}

		return \Bitrix\Crm\LeadTable::query()
			->setSelect($select)
			->where('ID', $id)
			->fetch() ?: [];
	}

	public function getResponsibleId()
	{
		$entity = $this->getEntityFields(['ASSIGNED_BY_ID']);

		return (int)$entity['ASSIGNED_BY_ID'];
	}

	public function getEntityStatus()
	{
		$entity = $this->getEntityFields(['STATUS_ID']);

		return $entity['STATUS_ID'] ?? '';
	}

	public function setEntityStatus($statusId, $executeBy = null)
	{
		$id = $this->getEntityId();
		if (empty($id))
		{
			return false;
		}

		$fields = ['STATUS_ID' => $statusId];
		if ($executeBy)
		{
			$fields['MODIFY_BY_ID'] = $executeBy;
		}

		$CCrmLead = new \CCrmLead(false);
		$result = $CCrmLead->Update($id, $fields, true, true, [
			'DISABLE_USER_FIELD_CHECK' => true,
			'REGISTER_SONET_EVENT' => true,
			'CURRENT_USER' => $executeBy ?? 0, //System user
		]);

		return $result;
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

		$statuses = StatusTable::getStatusesByEntityId('STATUS');
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
