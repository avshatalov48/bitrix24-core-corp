<?php
namespace Bitrix\Crm\Automation\Target;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\InvoiceTable;

class InvoiceTarget extends BaseTarget
{
	protected $entityStatuses;

	public function getEntityTypeId()
	{
		return \CCrmOwnerType::Invoice;
	}

	protected function getEntityIdByDocumentId(string $documentId): int
	{
		return (int)str_replace('INVOICE_', '', $documentId);
	}

	protected function getEntityFields(array $select): array
	{
		$id = $this->getEntityId();
		if (empty($id))
		{
			return [];
		}

		return InvoiceTable::query()
			->setSelect($select)
			->where('ID', $id)
			->fetch() ?: [];
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

		//TODO: use new API
		$fields = array('STATUS_ID' => $statusId);
		$CCrmInvoice = new \CCrmInvoice(false);
		$result = $CCrmInvoice->Update($id, $fields, array(
			'DISABLE_USER_FIELD_CHECK' => true,
			'REGISTER_SONET_EVENT' => true
		));

		return $result;
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

		$statuses = [];
		$result = \Bitrix\Crm\Invoice\InvoiceStatus::getList();
		while ($row = $result->fetch())
		{
			$statuses[$row['STATUS_ID']] = $row;
		}

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