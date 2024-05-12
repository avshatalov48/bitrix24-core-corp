<?php

namespace Bitrix\Intranet\Settings\Requisite;

use Bitrix\Crm\EntityRequisite;

class RequisiteList
{
	private int $companyTypeId;
	private array $requisites = [];
	private array $requisiteIds = [];
	private ?BankRequisiteList $bankRequisiteList = null;
	private ?PresetList $presetList = null;

	public function __construct(
		private CompanyList $companyList,
		private ?AddressList $addressList = null,
		private array $select = []
	)
	{
		$this->companyTypeId = \CCrmOwnerType::Company;
	}

	private function load(): void
	{
		$entityRequisite = new EntityRequisite();
		$requisiteResult = $entityRequisite->getList([
			'filter' => [
				'ENTITY_TYPE_ID' => $this->companyTypeId,
				'ENTITY_ID' => $this->companyList->getIds(),
			],
			'select' => !empty($this->select) ? $this->select : ['*']
		]);

		while ($req = $requisiteResult->fetch())
		{
			$this->requisiteIds[] = $req['ID'];
			if ($this->addressList)
			{
				$req[EntityRequisite::ADDRESS] = $this->addressList->getByCompanyId($req['ENTITY_ID']);
			}
			$this->requisites[$req['ID']] = $req;
		}
	}

	public function toArray(): array
	{
		if (empty($this->requisites))
		{
			$this->load();
		}

		return $this->requisites;
	}

	public function getIds(): array
	{
		if (empty($this->requisiteIds))
		{
			$this->load();
		}

		return $this->requisiteIds;
	}

	public function getOneRequisitePerCompany(bool $withBankRequisite = true): array
	{
		if (empty($this->requisites))
		{
			$this->load();
		}
		$result = [];
		foreach ($this->requisites as $requisite)
		{
			if (isset($result[(int)$requisite['ENTITY_ID']]))
			{
				continue;
			}
			if ($withBankRequisite)
			{
				$requisite = array_merge($this->getBankRequisiteList()->getByRequisiteId($requisite['ID']), $requisite);
			}
			$result[(int)$requisite['ENTITY_ID']] = $requisite;
		}

		return $result;
	}

	public function getByCompanyId(int $companyId): ?array
	{
		return $this->getOneRequisitePerCompany(false)[$companyId] ?? null;
	}

	public function getBankRequisiteList(): BankRequisiteList
	{
		if (!$this->bankRequisiteList)
		{
			$this->bankRequisiteList = new BankRequisiteList($this);
		}

		return $this->bankRequisiteList;
	}

	public function getPresetList(): PresetList
	{
		if (!$this->presetList)
		{
			$this->presetList = new PresetList($this);
		}

		return $this->presetList;
	}
}