<?php

namespace Bitrix\Intranet\Settings\Requisite;

use Bitrix\Crm\Integration\Landing\RequisitesLanding;
use Bitrix\Main\Loader;

class CompanyList
{
	private array $company = [];
	private array $companyIds = [];
	private ?LandingList $landingList = null;
	private ?RequisiteList $requisiteList = null;

	public function __construct(
		private array $filter,
		private array $sorting,
		private array $select = [],
		private array $requisiteSelect = [],
		private int $limit = 50
	)
	{
	}

	private function load(): void
	{
		$companyResult = \CCrmCompany::GetList($this->sorting, $this->filter, $this->select, $this->limit);

		while ($company = $companyResult->GetNext())
		{
			$this->company[] = $company;
			$this->companyIds[] = $company['ID'];
		}
	}

	public function toArray(): array
	{
		if (empty($this->company))
		{
			$this->load();
		}

		return $this->company;
	}

	public function getIds(): array
	{
		if (empty($this->companyIds))
		{
			$this->load();
		}

		return $this->companyIds;
	}

	public function getRequisiteList(): RequisiteList
	{
		if (!$this->requisiteList)
		{
			$this->requisiteList = new RequisiteList($this, new AddressList($this), $this->requisiteSelect);
		}

		return $this->requisiteList;
	}

	public function getLandingList(): LandingList
	{
		if (!$this->landingList)
		{
			$this->landingList = new LandingList($this);
		}

		return $this->landingList;
	}
}