<?php

namespace Bitrix\Crm\Search;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\Entity\Index;
use CCrmCompany;
use CCrmOwnerType;

final class CompanySearchContentBuilder extends SearchContentBuilder
{
	protected string $entityClassName = CCrmCompany::class;
	protected string $entityIndexTableClassName = Index\CompanyTable::class;
	protected string $entityIndexTable = 'b_crm_company_index';
	protected string $entityIndexTablePrimaryColumn = 'COMPANY_ID';

	public function getEntityTypeId(): int
	{
		return CCrmOwnerType::Company;
	}

	public function convertEntityFilterValues(array &$filter): void
	{
		$this->transferEntityFilterKeys(['FIND', 'PHONE'], $filter);
	}

	protected function prepareSearchMap(array $fields, array $options = null): SearchMap
	{
		$map = new SearchMap();
		$entityId = (int)($fields['ID'] ?? 0);
		if ($entityId <= 0)
		{
			return $map;
		}

		$isShortIndex = ($options['isShortIndex'] ?? false);
		if (!$isShortIndex)
		{
			$map->add($entityId);
		}

		$map = $this->addTitleToSearchMap(
			$entityId,
			$fields['TITLE'] ?? '',
			CCrmCompany::GetAutoTitleTemplate(),
			$map
		);

		if (isset($fields['ASSIGNED_BY_ID']) && !$isShortIndex)
		{
			$map->addUserByID($fields['ASSIGNED_BY_ID']);
		}

		$map = $this->addPhonesAndEmailsToSearchMap($entityId, $map);

		if (isset($fields['INDUSTRY']) && !$isShortIndex)
		{
			$map->addStatus('INDUSTRY', $fields['INDUSTRY']);
		}

		if (isset($fields['COMMENTS']) && !$isShortIndex)
		{
			$map->addHtml($fields['COMMENTS'], 1024);
		}

		//region UserFields
		if (!$isShortIndex)
		{
			$userFields = SearchEnvironment::getUserFields($entityId, $this->getUserFieldEntityId());
			foreach ($userFields as $userField)
			{
				$map->addUserField($userField);
			}
		}
		//endregion

		return $map;
	}

	protected function save(int $entityId, SearchMap $map): void
	{
		CompanyTable::update(
			$entityId,
			$this->prepareUpdateData(CompanyTable::getTableName(), $map->getString())
		);
	}
}
