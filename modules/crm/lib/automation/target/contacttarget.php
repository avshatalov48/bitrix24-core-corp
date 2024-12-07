<?php

namespace Bitrix\Crm\Automation\Target;

use Bitrix\Crm\ContactTable;

class ContactTarget extends BaseTarget
{
	public function getEntityTypeId()
	{
		return \CCrmOwnerType::Contact;
	}

	protected function getEntityIdByDocumentId(string $documentId): int
	{
		return (int)str_replace('CONTACT_', '', $documentId);
	}

	protected function getEntityFields(array $select): array
	{
		$id = $this->getEntityId();
		if (empty($id))
		{
			return [];
		}

		return ContactTable::query()
			->setSelect($select)
			->where('ID', $id)
			->fetch() ?: [];
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
