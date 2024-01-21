<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Recovery;

use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Crm\Counter\EntityCounter;
use Bitrix\Crm\Counter\EntityCounterManager;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Counter\ProblemDetector\ProblemDetectorQueries;

abstract class CountableBase
{
	protected ProblemDetectorQueries $queries;

	protected Config $config;
	public function __construct()
	{
		$this->config = Config::getInstance();
		$this->queries = ProblemDetectorQueries::getInstance();
	}

	protected function resetCountableByField(array $item): void
	{
		$codes = EntityCounterManager::prepareCodes(
			(int)$item['ENTITY_TYPE_ID'],
			EntityCounterType::getAll(true),
			['ENTITY_ID' => (int)$item['ENTITY_ID']]
		);

		foreach ($codes as $code)
		{
			$responsible = EntityCountableActivityTable::getActivityResponsible($item);

			if ($responsible === null)
			{
				continue;
			}
			EntityCounter::resetByCode($code, $responsible);
		}
	}

}