<?php

namespace Bitrix\Crm\RepeatSale\Segment\Collector;

use Bitrix\Crm\DealTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Communication\Utils\Common;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\Date;

class LostClientCollector extends BaseCollector
{
	protected function getCompanyIds(array $filter): array
	{
		$query = $this->getCompaniesWithTwoAndMoreDealsQuery($filter);
		$companyIds = $this->getCompanyWithTwoAndMoreDealsIds($query);

		if (empty($companyIds))
		{
			return [];
		}

		$query = $this->getCompaniesWithActiveDealsQuery($companyIds);
		$companyWithActiveDealsIds = $this->getCompanyWithActiveDealsIds($query);

		return array_diff($companyIds, $companyWithActiveDealsIds);
	}

	private function getCompaniesWithTwoAndMoreDealsQuery(array $filter): Query
	{
		$oneMonthAgo = (new Date())->add('-1 month');

		return DealTable::query()
			->addSelect('COMPANY_ID')
			->where('COMPANY_ID', '>', $filter['>ID'] ?? 0)
			->where('CLOSEDATE', '<=', $oneMonthAgo)
			->where('STAGE_SEMANTIC_ID', '!=', PhaseSemantics::PROCESS)
			->registerRuntimeField(
				'CNT',
				new ExpressionField('COUNT_COMPANY_ID', 'COUNT(\'COMPANY_ID\')')
			)
			->having('CNT',  '>=', 2)
			->setOrder(['COMPANY_ID' => 'ASC'])
			->setLimit($this->limit)
		;
	}

	protected function getCompanyWithTwoAndMoreDealsIds(Query $query): array
	{
		return array_column($query->exec()->fetchAll(), 'COMPANY_ID');
	}

	private function getCompaniesWithActiveDealsQuery(array $companyIds): Query
	{
		return DealTable::query()
			->addSelect('COMPANY_ID')
			->where(Query::filter()
				->where(Query::filter()->whereIn('COMPANY_ID', $companyIds))
				->where(Query::filter()
					->logic('OR')
					->where('STAGE_SEMANTIC_ID', PhaseSemantics::PROCESS)
					->where('SOURCE_ID', 'REPEAT_SALE')
				)
			)
		;
	}

	protected function getCompanyWithActiveDealsIds(Query $query): array
	{
		return array_column($query->exec()->fetchAll(), 'COMPANY_ID');
	}

	// @todo maybe need use b_crm_deal_contact for more accurate results
	protected function getContactIds(array $filter): array
	{
		$query = $this->getContactsWithTwoAndMoreDealsQuery($filter);
		$contactIds = $this->getContactWithTwoAndMoreDealsIds($query);

		if (empty($contactIds))
		{
			return [];
		}

		$query = $this->getContactsWithActiveDealsQuery($contactIds);
		$contactWithActiveDealsIds = $this->getContactWithActiveDealsIds($query);

		return array_diff($contactIds, $contactWithActiveDealsIds);
	}

	private function getContactsWithTwoAndMoreDealsQuery(array $filter): Query
	{
		$oneMonthAgo = (new Date())->add('-1 month');

		return DealTable::query()
			->addSelect('CONTACT_ID')
			->where('CONTACT_ID', '>', $filter['>ID'] ?? 0)
			->where('CLOSEDATE', '<=', $oneMonthAgo)
			->where('STAGE_SEMANTIC_ID', '!=', PhaseSemantics::PROCESS)
			->registerRuntimeField(
				'CNT',
				new ExpressionField('COUNT_CONTACT_ID', 'COUNT(\'CONTACT_ID\')')
			)
			->having('CNT',  '>=', 2)
			->setOrder(['CONTACT_ID' => 'ASC'])
			->setLimit($this->limit)
		;
	}

	protected function getContactWithTwoAndMoreDealsIds(Query $query): array
	{
		return array_column($query->exec()->fetchAll(), 'CONTACT_ID');
	}

	private function getContactsWithActiveDealsQuery(array $contactIds): Query
	{
		return DealTable::query()
			->addSelect('CONTACT_ID')
			->where(Query::filter()
				->where(Query::filter()->whereIn('CONTACT_ID', $contactIds))
				->where(Query::filter()
					->logic('OR')
					->where('STAGE_SEMANTIC_ID', PhaseSemantics::PROCESS)
					->where('SOURCE_ID', 'REPEAT_SALE')
				)
			)
		;
	}

	protected function getContactWithActiveDealsIds(Query $query): array
	{
		return array_column($query->exec()->fetchAll(), 'CONTACT_ID');
	}

	protected function getNextItemsMinId(int $entityTypeId, array $filter): ?int
	{
		if (!Common::isClientEntityTypeId($entityTypeId))
		{
			return null;
		}

		$oneMonthAgo = (new Date())->add('-1 month');
		$minId = ($filter['>ID'] ?? 0) + $this->limit;
		$fieldName = $entityTypeId === \CCrmOwnerType::Contact ? 'CONTACT_ID' : 'COMPANY_ID';

		$query = DealTable::query()
			->setSelect([$fieldName])
			->where($fieldName, '>', $minId)
			->where('CLOSEDATE', '<=', $oneMonthAgo)
			->where('STAGE_SEMANTIC_ID', '!=', PhaseSemantics::PROCESS)
			->registerRuntimeField(
				'CNT',
				new ExpressionField('COUNT_ITEM_ID', 'COUNT(\'' . $fieldName . '\')')
			)
			->having('CNT',  '>=', 2)
			->setOrder([$fieldName => 'ASC'])
			->setLimit(1)
		;

		$result = $query->exec()->fetch();

		return is_array($result) ? (int)$result[$fieldName] : null;
	}
}
