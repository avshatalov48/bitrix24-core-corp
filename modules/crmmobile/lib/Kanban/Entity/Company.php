<?php

namespace Bitrix\CrmMobile\Kanban\Entity;

class Company extends ListEntity
{
	protected const DEFAULT_SELECT_FIELD_NAMES = [
		'DATE_CREATE',
		'ID',
		'TITLE',
		'PHONE',
		'EMAIL',
		'ASSIGNED_BY_ID',
	];

	protected const FIELD_ALIASES = [
		'ASSIGNED_BY' => 'ASSIGNED_BY_ID',
		'CREATED_BY' => 'CREATED_BY_ID',
		'MODIFY_BY' => 'MODIFY_BY_ID',
	];

	public function getEntityType(): string
	{
		return \CCrmOwnerType::CompanyName;
	}

	protected function getEntityClass(): \CCrmCompany
	{
		return new \CCrmCompany();
	}

	protected function getClient(array $item, array $params = []): ?array
	{
		$client = $this->getSelfContactInfo($item, \CCrmOwnerType::CompanyName);
		if (empty($client))
		{
			return [];
		}

		$client['hidden'] = true;
		return $client;
	}

	protected function getFilterPresets(): array
	{
		return (new \Bitrix\Crm\Filter\Preset\Company())->getDefaultPresets();
	}

	protected function prepareEntityTypeFilter(array &$filter): void
	{
		$filter['=IS_MY_COMPANY'] = 'N';
	}
}
