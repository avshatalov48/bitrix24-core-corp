<?php

namespace Bitrix\Crm\Automation\Target;

class ContactTarget extends BaseTarget
{
	public function getEntityTypeId()
	{
		return \CCrmOwnerType::Contact;
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
			$entity = \CCrmContact::GetByID($id, false);
			if ($entity)
			{
				$this->setEntity($entity);
				$this->setDocumentId('CONTACT_' . $id);
			}
		}
	}

	public function getEntity()
	{
		if ($this->entity === null && $id = $this->getDocumentId())
		{
			$id = (int)str_replace('CONTACT_', '', $id);
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
