<?php

namespace Bitrix\Intranet\Settings\Requisite;

use Bitrix\Crm\EntityAddress;

class AddressList
{
	private array $addresses = [];
	private int $companyTypeId;

	public function __construct(
		private CompanyList $companyList
	)
	{
		$this->companyTypeId = \CCrmOwnerType::Company;
	}

	private function load(): void
	{
		$entityAddress = new EntityAddress();
		$addressResult = $entityAddress->getList([
			'filter' => [
				'ANCHOR_ID' => $this->companyList->getIds(),
				'ANCHOR_TYPE_ID' => $this->companyTypeId,
			],
		]);

		while ($address = $addressResult->fetch())
		{
			$this->addresses[] = $address;
		}
	}
	
	public function toArray(): array
	{
		if (empty($this->addresses))
		{
			$this->load();
		}

		return $this->addresses;
	}
	
	public function getByCompanyId(int $companyId): array
	{
		if (empty($this->addresses))
		{
			$this->load();
		}
		$result = [];
		foreach ($this->addresses as $address)
		{
			if ((int)$address['ANCHOR_ID'] === $companyId)
			{
				$result[] = $address;
			}
		}

		return $result;
	}

}