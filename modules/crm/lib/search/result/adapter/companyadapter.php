<?php

namespace Bitrix\Crm\Search\Result\Adapter;

use Bitrix\Crm\Item;

class CompanyAdapter extends \Bitrix\Crm\Search\Result\Adapter
{
	protected function loadItemsByIds(array $ids): array
	{
		$result = [];
		$companies = \CCrmCompany::GetListEx(
			[],
			['@ID' => $ids, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'LOGO', 'ORIGINATOR_ID', 'CATEGORY_ID']
		);
		while ($company = $companies->Fetch())
		{
			$result[] = $company;
		}

		return $result;
	}

	protected function getEntityTypeId(): int
	{
		return \CCrmOwnerType::Company;
	}

	protected function prepareTitle(array $item): string
	{
		return $item['TITLE'];
	}

	protected function prepareSubTitle(array $item): string
	{
		$descriptions = [];

		$this->addCategoryLabelToSubtitle((int)($item['CATEGORY_ID'] ?? 0), $descriptions);

		if (
			!$this->category
			|| !in_array(Item::FIELD_NAME_TYPE_ID, $this->category->getDisabledFieldNames(), true)
		)
		{
			$typesList = $this->getTypesList();

			if (isset($typesList[$item['COMPANY_TYPE']]))
			{
				$descriptions[] = $typesList[$item['COMPANY_TYPE']];
			}
		}

		if (
			!$this->category
			|| !in_array(Item\Company::FIELD_NAME_INDUSTRY, $this->category->getDisabledFieldNames(), true)
		)
		{
			$industriesList = $this->getIndustriesList();

			if (isset($industriesList[$item['INDUSTRY']]))
			{
				$descriptions[] = $industriesList[$item['INDUSTRY']];
			}
		}

		return implode(', ', $descriptions);
	}

	protected function areMultifieldsSupported(): bool
	{
		return true;
	}

	private function getTypesList(): array
	{
		static $typesList;
		if ($typesList === null)
		{
			$typesList = \CCrmStatus::GetStatusList('COMPANY_TYPE');
		}

		return $typesList;
	}

	private function getIndustriesList(): array
	{
		static $industriesList;
		if ($industriesList === null)
		{
			$industriesList = \CCrmStatus::GetStatusList('INDUSTRY');
		}

		return $industriesList;
	}
}
