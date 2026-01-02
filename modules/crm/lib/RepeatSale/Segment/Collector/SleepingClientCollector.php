<?php

namespace Bitrix\Crm\RepeatSale\Segment\Collector;

use Bitrix\Crm\Activity\LastCommunication\LastCommunicationAvailabilityChecker;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Model\LastCommunicationTable;
use Bitrix\Crm\Service\Communication\Utils\Common;
use Bitrix\Main\Type\Date;
use CCrmOwnerType;

final class SleepingClientCollector extends BaseCollector
{
	protected function getCompanyIds(array $filter): array
	{
		if (!$this->isLastCommunicationFieldEnabled())
		{
			return [];
		}

		$oneYearAgo = (new Date())->add('-1 year');
		$oneMonthAgo = (new Date())->add('-1 month');

		$activeCompaniesQuery = LastCommunicationTable::query()
			->setSelect(['ENTITY_ID'])
			->where('ENTITY_TYPE_ID', CCrmOwnerType::Company)
			->where('TYPE', LastCommunicationTable::ENUM_LAST_TIME)
			->where('LAST_COMMUNICATION_TIME', '>=', $oneYearAgo)
			->where('LAST_COMMUNICATION_TIME', '<', $oneMonthAgo)
			->where('ENTITY_ID', '>', $filter['>ID'] ?? 0)
			->setOrder(['ENTITY_ID' => 'ASC'])
			->setLimit($this->limit)
			->setDistinct()
		;

		$activeCompanyIds = array_column($activeCompaniesQuery->exec()->fetchAll(), 'ENTITY_ID');

		if (empty($activeCompanyIds))
		{
			return [];
		}

		$activeDealsResult = DealTable::query()
			->setSelect(['COMPANY_ID'])
			->whereIn('COMPANY_ID', $activeCompanyIds)
			->setDistinct()
		;
		$activeDealsCompanyIds = array_column($activeDealsResult->exec()->fetchAll(), 'COMPANY_ID');

		return array_diff($activeCompanyIds, $activeDealsCompanyIds);
	}

	protected function getContactIds(array $filter): array
	{
		if (!$this->isLastCommunicationFieldEnabled())
		{
			return [];
		}

		$oneYearAgo = (new Date())->add('-1 year');
		$oneMonthAgo = (new Date())->add('-1 month');

		$activeContactsQuery = LastCommunicationTable::query()
			->setSelect(['ENTITY_ID'])
			->where('ENTITY_TYPE_ID', CCrmOwnerType::Contact)
			->where('TYPE', LastCommunicationTable::ENUM_LAST_TIME)
			->where('LAST_COMMUNICATION_TIME', '>=', $oneYearAgo)
			->where('LAST_COMMUNICATION_TIME', '<', $oneMonthAgo)
			->where('ENTITY_ID', '>', $filter['>ID'] ?? 0)
			->setOrder(['ENTITY_ID' => 'ASC'])
			->setLimit($this->limit)
			->setDistinct()
		;
		$activeContactIds = array_column($activeContactsQuery->exec()->fetchAll(), 'ENTITY_ID');

		if (empty($activeContactIds))
		{
			return [];
		}

		$activeContactsResult = DealContactTable::query()
			->addSelect('CONTACT_ID')
			->whereIn('CONTACT_ID', $activeContactIds)
			->setDistinct()
		;

		$activeDealsContactIds = array_column($activeContactsResult->exec()->fetchAll(), 'CONTACT_ID');

		return array_diff($activeContactIds, $activeDealsContactIds);
	}

	protected function getNextItemsMinId(int $entityTypeId, array $filter): ?int
	{
		if (!$this->isLastCommunicationFieldEnabled())
		{
			return false;
		}

		if (!Common::isClientEntityTypeId($entityTypeId))
		{
			return null;
		}

		$minId = ($filter['>ID'] ?? 0) + $this->limit;

		$query = LastCommunicationTable::query()
			->setSelect(['ENTITY_ID'])
			->where('ENTITY_TYPE_ID', $entityTypeId)
			->where('ENTITY_ID', '>', $minId)
			->where('TYPE', LastCommunicationTable::ENUM_LAST_TIME)
			->setOrder(['ENTITY_ID' => 'ASC'])
			->setLimit(1)
		;

		$result = $query->exec()->fetch();

		return is_array($result) ? (int)$result['ENTITY_ID'] : null;
	}

	private function isLastCommunicationFieldEnabled(): bool
	{
		return LastCommunicationAvailabilityChecker::getInstance()->isEnabled();
	}
}
