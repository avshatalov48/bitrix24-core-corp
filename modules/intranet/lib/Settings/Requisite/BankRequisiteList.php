<?php

namespace Bitrix\Intranet\Settings\Requisite;

use Bitrix\Crm\EntityBankDetail;

class BankRequisiteList
{
	private int $requisiteTypeId;
	private array $bankRequisites = [];

	public function __construct(
		private RequisiteList $requisiteList,
	)
	{
		$this->requisiteTypeId = \CCrmOwnerType::Requisite;
	}

	private function load(): void
	{
		$bankDetail = EntityBankDetail::getSingleInstance();
		$bankDetailResult = $bankDetail->getList(
			array(
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
				'filter' => [
					'ENTITY_ID' => $this->requisiteList->getIds(),
					'ENTITY_TYPE_ID' => $this->requisiteTypeId
				],
			)
		);

		while ($bank = $bankDetailResult->fetch())
		{
			$this->bankRequisites[] = $bank;
		}
	}

	public function toArray(): array
	{
		if (empty($this->bankRequisites))
		{
			$this->load();
		}

		return $this->bankRequisites;
	}

	public function getByRequisiteId(int $requisiteId): array
	{
		if (empty($this->bankRequisites))
		{
			$this->load();
		}

		foreach ($this->bankRequisites as $bankRequisite)
		{
			if ((int)$bankRequisite['ENTITY_ID'] === $requisiteId)
			{
				return $bankRequisite;
			}
		}

		return [];
	}
}