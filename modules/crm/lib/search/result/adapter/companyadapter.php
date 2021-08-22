<?php

namespace Bitrix\Crm\Search\Result\Adapter;

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
			['ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'LOGO', 'ORIGINATOR_ID']
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
		$typesList = $this->getTypesList();
		$industriesList = $this->getIndustriesList();

		$descriptions = [];
		if (isset($typesList[$item['COMPANY_TYPE']]))
		{
			$descriptions[] = $typesList[$item['COMPANY_TYPE']];
		}
		if (isset($industriesList[$item['INDUSTRY']]))
		{
			$descriptions[] = $industriesList[$item['INDUSTRY']];
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
