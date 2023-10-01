<?php

namespace Bitrix\Crm\Automation\Trigger\Entity;

use Bitrix\Main\ORM\Objectify\State;

class TriggerObject extends EO_Trigger
{
	public function getDocumentType(): ?array
	{
		return \CCrmBizProcHelper::ResolveDocumentType($this->getEntityTypeId());
	}

	public function getDocumentStatus(): string
	{
		return $this->getEntityStatus();
	}

	public function getValues(): array
	{
		$values = $this->collectValues();

		$resultValues = [];
		$triggerFields = ['ID', 'NAME', 'CODE', 'APPLY_RULES'];
		foreach ($triggerFields as $field)
		{
			if (isset($values[$field]))
			{
				$resultValues[$field] = $values[$field];
			}
		}

		if (isset($values['ENTITY_STATUS']))
		{
			$resultValues['DOCUMENT_STATUS'] = $values['ENTITY_STATUS'];
		}
		$resultValues['DOCUMENT_TYPE'] = $this->getDocumentType();

		return $resultValues;
	}
}