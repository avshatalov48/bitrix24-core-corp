<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\EO_Company;
use Bitrix\Crm\Service\Broker;

/**
 * @method EO_Company|null getById(int $id)
 * @method EO_Company[] getBunchByIds(array $ids)
 */
class Company extends Broker
{
	protected ?string $eventEntityAdd = 'OnAfterCrmCompanyAdd';
	protected ?string $eventEntityUpdate = 'OnAfterCrmCompanyUpdate';
	protected ?string $eventEntityDelete = 'OnAfterCrmCompanyDelete';

	public function getTitle(int $id): ?string
	{
		return $this->getById($id)?->getTitle();
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
