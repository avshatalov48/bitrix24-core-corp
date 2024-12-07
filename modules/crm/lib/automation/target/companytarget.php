<?php

namespace Bitrix\Crm\Automation\Target;

use Bitrix\Crm\CompanyTable;

class CompanyTarget extends BaseTarget
{
	public function getEntityTypeId()
	{
		return \CCrmOwnerType::Company;
	}

	protected function getEntityIdByDocumentId(string $documentId): int
	{
		return (int)str_replace('COMPANY_', '', $documentId);
	}

	protected function getEntityFields(array $select): array
	{
		$id = $this->getEntityId();
		if (empty($id))
		{
			return [];
		}

		return CompanyTable::query()
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
