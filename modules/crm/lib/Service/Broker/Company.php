<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\EO_Company;
use Bitrix\Crm\Service\Broker;

class Company extends Broker
{
	public function getTitle(int $id): ?string
	{
		/** @var EO_Company|null $company */
		$company = $this->getById($id);
		if (!$company)
		{
			return null;
		}

		return $company->getTitle();
	}

	protected function loadEntry(int $id): ?EO_Company
	{
		return CompanyTable::getById($id)->fetchObject();
	}

	protected function loadEntries(array $ids): array
	{
		$companyCollection = CompanyTable::getList([
			'filter' => ['@ID' => $ids],
		])->fetchCollection();

		$companies = [];
		foreach ($companyCollection as $company)
		{
			$companies[$company->getId()] = $company;
		}

		return $companies;
	}
}