<?php
namespace Bitrix\Crm\Automation\Target;

use Bitrix\Crm\PhaseSemantics;

class InvoiceTarget extends BaseTarget
{
	protected $entityStatuses;

	public function getEntityTypeId()
	{
		return \CCrmOwnerType::Invoice;
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
			//TODO: use new API
			$entity = \CCrmInvoice::GetByID($id, false);
			if ($entity)
			{
				$this->setEntity($entity);
				$this->setDocumentId('INVOICE_'.$id);
			}
		}
	}

	public function getEntity()
	{
		if ($this->entity === null && $id = $this->getDocumentId())
		{
			$id = (int) str_replace('INVOICE_', '', $id);
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

		//TODO: use new API
		$fields = array('STATUS_ID' => $statusId);
		$CCrmInvoice = new \CCrmInvoice(false);
		$CCrmInvoice->Update($id, $fields, array(
			'DISABLE_USER_FIELD_CHECK' => true,
			'REGISTER_SONET_EVENT' => true
		));

		$this->setEntityField('STATUS_ID', $statusId);
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

		//TODO: use new API
		$statuses = \CCrmViewHelper::GetInvoiceStatusInfos();

		foreach ($statuses as $id => $statusInfo)
		{
			if (!empty($statusInfo['COLOR']))
				continue;

			$semanticId = \CCrmInvoice::GetSemanticID($statusInfo["STATUS_ID"]);

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