<?php

namespace Bitrix\Crm\Automation\Target;

class CompanyTarget extends BaseTarget
{
	public function getEntityTypeId()
	{
		return \CCrmOwnerType::Company;
	}

	public function getEntityId()
	{
		$entity = $this->getEntity();
		return isset($entity['ID']) ? (int)$entity['ID'] : 0;
	}

	public function setEntityById($id)
	{
		$id = (int)$id;
		if ($id > 0)
		{
			$entity = \CCrmCompany::GetByID($id, false);
			if ($entity)
			{
				$this->setEntity($entity);
				$this->setDocumentId('COMPANY_' . $id);
			}
		}
	}

	public function getEntity()
	{
		if ($this->entity === null && $id = $this->getDocumentId())
		{
			$id = (int)str_replace('COMPANY_', '', $id);
			$this->setEntityById($id);
		}

		return parent::getEntity();
	}

	public function getEntityStatus()
	{
		return null;
	}

	public function getEntityStatuses()
	{
		return [];
	}

	public function getStatusInfos($categoryId = 0)
	{
		return [];
	}
}
