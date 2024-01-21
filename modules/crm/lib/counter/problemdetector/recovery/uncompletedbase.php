<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Recovery;

use Bitrix\Crm\Activity\UncompletedActivity;
use Bitrix\Crm\Counter\ProblemDetector\ProblemDetectorQueries;

abstract class UncompletedBase
{
	protected ProblemDetectorQueries $queries;

	protected Config $config;

	public function __construct()
	{
		$this->config = Config::getInstance();
		$this->queries = ProblemDetectorQueries::getInstance();
	}

	protected function fixByUncompletedIds(array $badRecordsIds): void
	{
		$badRecords = $this->queries->queryUncompletedFields($badRecordsIds);

		foreach ($badRecords as $item)
		{
			$bindings = [[
				'OWNER_ID' => $item['ENTITY_ID'],
				'OWNER_TYPE_ID' => $item['ENTITY_TYPE_ID']
			]];
			$responsibleIds = [$item['RESPONSIBLE_ID']];

			UncompletedActivity::synchronizeForBindingsAndResponsibles($bindings, $responsibleIds);
		}

	}
}
