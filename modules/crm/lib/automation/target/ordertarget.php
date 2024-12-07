<?php
namespace Bitrix\Crm\Automation\Target;

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

	protected function getEntityIdByDocumentId(string $documentId): int
	{
		return (int)str_replace('ORDER_', '', $documentId);
	}

	protected function getEntityFields(array $select): array
	{
		$id = $this->getEntityId();
		if (empty($id))
		{
			return [];
		}

		$order = Order\Order::loadByFilter([
			'filter' => ['ID' => $id],
			'select' => $select
		]);
		if (!empty($order) && is_array($order))
		{
			$order = reset($order);
			$fields = $order ? $order->getFieldValues() : [];
		}

		return $fields ?? [];
	}

	public function getResponsibleId()
	{
		$entity = $this->getEntityFields(['RESPONSIBLE_ID']);

		return (int)$entity['RESPONSIBLE_ID'];
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
			$this->entityStatuses = array_keys($this->getStatusInfos());
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
}