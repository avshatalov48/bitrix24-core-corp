<?php

namespace Bitrix\Intranet\Settings\Requisite;

use Bitrix\Crm\Integration\Landing\RequisitesLanding;
use Bitrix\Main\Loader;

class LandingList
{
	private array $landings = [];

	public function __construct(
		private CompanyList $companyList
	)
	{}

	private function load(): void
	{
		Loader::includeModule('crm');
		foreach ($this->companyList->toArray() as $company)
		{
			$companyId = (int)($company['ID'] ?? null);
			$requisiteList = $this->companyList->getRequisiteList();
			$bankRequisiteList = $requisiteList->getBankRequisiteList();
			$requisite = $requisiteList->getByCompanyId($companyId);
			$bank = $bankRequisiteList->getByRequisiteId((int)($requisite['ID'] ?? null));
			$this->landings[$companyId] = new RequisitesLanding($companyId, (int)($requisite['ID'] ?? null), (int)($bank['ID'] ?? null));
		}
	}

	public function toArray(): array
	{
		if (empty($this->landings))
		{
			$this->load();
		}

		return $this->landings;
	}
}