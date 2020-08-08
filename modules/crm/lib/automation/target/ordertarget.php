<?php
namespace Bitrix\Crm\Automation\Target;

use Bitrix\Bizproc\Automation\Engine\ConditionGroup;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Order;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OrderTarget extends BaseTarget
{
	protected $entityStatuses;

	public function getEntityTypeId()
	{
		return \CCrmOwnerType::Order;
	}

	public function getEntityId()
	{
		$entity = $this->getEntity();
		return isset($entity['ID']) ? (int)$entity['ID'] : 0;
	}

	public function getResponsibleId()
	{
		$entity = $this->getEntity();
		return isset($entity['RESPONSIBLE_ID']) ? (int)$entity['RESPONSIBLE_ID'] : 0;
	}

	public function setEntityById($id)
	{
		$id = (int)$id;
		if ($id > 0)
		{
			$order = Order\Order::load($id);
			$fields = $order ? $order->getFieldValues() : null;

			if ($fields)
			{
				$this->setEntity($fields);
				$this->setDocumentId('ORDER_'.$id);
			}
		}
	}

	public function getEntity()
	{
		if ($this->entity === null && $id = $this->getDocumentId())
		{
			$id = (int) str_replace('ORDER_', '', $id);
			$this->setEntityById($id);
		}

		return parent::getEntity();
	}

	public function getEntityStatus()
	{
		$entity = $this->getEntity();
		return isset($entity['STATUS_ID']) ? $entity['STATUS_ID'] : '';
	}

	public function setEntityStatus($statusId)
	{
		$id = $this->getEntityId();
		$oldStatus = $this->getEntityStatus();
		$this->setEntityField('STATUS_ID', $oldStatus);

		$result = Order\Manager::setOrderStatus($id, $statusId, Loc::getMessage('CRM_AUTOMATION_TARGET_ORDER_TRIGGER_APPLY'));
		if (!$result->isSuccess())
		{
			return false;
		}

		return true;
	}

	public function getEntityStatuses()
	{
		if ($this->entityStatuses === null)
		{
			$this->entityStatuses = array_keys(static::getStatusInfos());
		}

		return $this->entityStatuses;
	}

	public function getStatusInfos($categoryId = 0)
	{
		$processColor = \CCrmViewHelper::PROCESS_COLOR;
		$successColor = \CCrmViewHelper::SUCCESS_COLOR;
		$failureColor = \CCrmViewHelper::FAILURE_COLOR;

		$statuses = Order\OrderStatus::getListInCrmFormat();

		foreach ($statuses as $id => $statusInfo)
		{
			if (!empty($statusInfo['COLOR']))
				continue;

			$semanticId = Order\OrderStatus::getSemanticID($statusInfo["STATUS_ID"]);

			if ($semanticId == PhaseSemantics::PROCESS)
				$statuses[$id]["COLOR"] = $processColor;
			else if ($semanticId == PhaseSemantics::FAILURE)
				$statuses[$id]["COLOR"] = $failureColor;
			else if ($semanticId == PhaseSemantics::SUCCESS)
				$statuses[$id]["COLOR"] = $successColor;
		}

		return $statuses;
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

		if (isset($rules['shipmentCondition']))
		{
			$condition = new ConditionGroup($rules['shipmentCondition']);
			if ($external)
			{
				$condition->externalizeValues($this->getDocumentType());
			}
			else
			{
				$condition->internalizeValues($this->getDocumentType());
			}
			$rules['shipmentCondition'] = $condition->toArray();
		}

		return $rules;
	}

}