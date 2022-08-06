<?php
namespace Bitrix\Crm\Automation\Target;

use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\PhaseSemantics;

class DealTarget extends BaseTarget
{
	protected $entityStages;

	public function isAvailable()
	{
		return Factory::isAutomationAvailable(\CCrmOwnerType::Deal);
	}

	public function canTriggerSetExecuteBy(): bool
	{
		return true;
	}

	public function getEntityTypeId()
	{
		return \CCrmOwnerType::Deal;
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
			$entity = \CCrmDeal::GetByID($id, false);
			if ($entity)
			{
				$this->setEntity($entity);
				$this->setDocumentId('DEAL_'.$id);
			}
		}
	}

	public function getEntity()
	{
		if ($this->entity === null && $id = $this->getDocumentId())
		{
			$id = (int) str_replace('DEAL_', '', $id);
			$this->setEntityById($id);
		}

		return parent::getEntity();
	}

	public function getEntityStatus()
	{
		$entity = $this->getEntity();
		return isset($entity['STAGE_ID']) ? $entity['STAGE_ID'] : '';
	}

	public function getDocumentCategory(): int
	{
		$entity = $this->getEntity();

		return (int)$entity['CATEGORY_ID'];
	}

	public function setEntityStatus($statusId, $executeBy = null)
	{
		$id = $this->getEntityId();

		$fields = ['STAGE_ID' => $statusId];
		if ($executeBy)
		{
			$fields['MODIFY_BY_ID'] = $executeBy;
		}

		$CCrmDeal = new \CCrmDeal(false);
		$updateResult = $CCrmDeal->Update($id, $fields, true, true, array(
			'DISABLE_USER_FIELD_CHECK' => true,
			'REGISTER_SONET_EVENT' => true,
			'CURRENT_USER' => $executeBy ?? 0 //System user
		));

		if ($updateResult)
		{
			$this->setEntityField('STAGE_ID', $statusId);
		}

		return $updateResult;
	}

	public function getEntityStatuses()
	{
		if ($this->entityStages === null)
		{
			$entity = $this->getEntity();
			$categoryId = isset($entity['CATEGORY_ID']) ? (int)$entity['CATEGORY_ID'] : 0;
			$this->entityStages = array_keys(DealCategory::getStageList($categoryId));
		}

		return $this->entityStages;
	}

	public function getStatusInfos($categoryId = 0)
	{
		$entity = $this->getEntity();
		if ($entity && !empty($entity['CATEGORY_ID']))
		{
			$categoryId = (int)$entity['CATEGORY_ID'];
		}

		$processColor = \CCrmViewHelper::PROCESS_COLOR;
		$successColor = \CCrmViewHelper::SUCCESS_COLOR;
		$failureColor = \CCrmViewHelper::FAILURE_COLOR;

		$statuses = \CCrmViewHelper::GetDealStageInfos($categoryId);

		foreach ($statuses as $id => $stageInfo)
		{
			if (!empty($stageInfo['COLOR']))
				continue;

			$stageSemanticID = \CCrmDeal::GetSemanticID($stageInfo['STATUS_ID'], $categoryId);
			$isSuccess = $stageSemanticID === PhaseSemantics::SUCCESS;
			$isFailure = $stageSemanticID === PhaseSemantics::FAILURE;

			$statuses[$id]['COLOR'] = ($isSuccess ? $successColor : ($isFailure ? $failureColor : $processColor));
		}
		return $statuses;
	}

	public function getDocumentCategoryCode(): string
	{
		return 'CATEGORY_ID';
	}
}