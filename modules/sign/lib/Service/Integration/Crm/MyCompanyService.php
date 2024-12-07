<?php

namespace Bitrix\Sign\Service\Integration\Crm;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Requisite\DefaultRequisite;
use Bitrix\Main\Application;
use Bitrix\Sign\Connector\Crm\MyCompany;
use Bitrix\Sign\Item\Integration\Crm\MyCompanyCollection;

final class MyCompanyService
{
	public function listWithTaxIds(array $inIds = []): MyCompanyCollection
	{
		$companies = MyCompany::listItems(inIds: $inIds);
		$this->appendRequisites($companies);

		return $companies;
	}

	private function appendRequisites(MyCompanyCollection $myCompanies): void
	{
		if (!$myCompanies->count())
		{
			return;
		}

		if ($this->isTaxIdIsCompanyId())
		{
			foreach ($myCompanies as $company)
			{
				$company->taxId = (string)$company->id;
			}

			return;
		}

		foreach ($myCompanies as $company)
		{
			$defaultRequisite = new DefaultRequisite(
				new ItemIdentifier(\CCrmOwnerType::Company, $company->id)
			);
			$requisite = $defaultRequisite->get();
			$company->taxId = $requisite['RQ_INN'] ?? null;
		}
	}

	public function isTaxIdIsCompanyId(): bool
	{
		return Application::getInstance()->getLicense()->getRegion() !== 'ru';
	}
}