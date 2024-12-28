<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\HumanResources\Config\Feature;
use Bitrix\HumanResources\Service;
use Bitrix\HumanResources\Item;

class HumanResourcesHcmLinkCompanyList extends \CBitrixComponent
{
	public function executeComponent(): void
	{
		if (!Feature::instance()->isHcmLinkAvailable())
		{
			ShowError('Feature is not available.');
			return;
		}

		if (!Service\Container::getHcmLinkAccessService()->canRead())
		{
			$this->includeComponentTemplate('not-available');
			return;
		}

		$this->prepareResult();
		$this->includeComponentTemplate();
	}

	private function prepareResult(): void
	{
		$this->arResult['COMPANIES'] = $this->getCompanies();
	}

	private function getCompanies(): array
	{
		$companyCollection = Service\Container::getHcmLinkCompanyRepository()->getList();

		$companyIds = $companyCollection->map(
			static fn(Item\HcmLink\Company $company) => $company->id,
		);

		$notMappedCountByCompanyId = Service\Container::getHcmLinkPersonRepository()
			->countNotMappedAndGroupByCompanyId($companyIds)
		;

		return array_values(
			$companyCollection->map(
				static fn(Item\HcmLink\Company $company) => [
					'id' => $company->id,
					'title' => $company->title,
					'notMappedCount' => $notMappedCountByCompanyId[$company->id] ?? 0,
				],
			),
		);
	}
}