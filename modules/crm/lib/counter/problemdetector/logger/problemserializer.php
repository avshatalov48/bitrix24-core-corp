<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Logger;

use Bitrix\Crm\Counter\ProblemDetector\Problem;
use Bitrix\Crm\Counter\ProblemDetector\ProblemList;
use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Type\Date;

class ProblemSerializer
{
	use Singleton;

	public function makeProviderSummary(ProblemList $problemList): array
	{
		$activityIds = [];
		$result = [];
		foreach ($problemList->getProblems() as $problem)
		{
			foreach ($problem->activities() as $activity)
			{
				$key = $activity['PROVIDER_ID'] . '__' . $activity['PROVIDER_TYPE_ID'];
				$actId = $activity['ID'];

				if (in_array($actId, $activityIds))
				{
					continue;
				}

				if (!isset($result[$key]))
				{
					$result[$key] = 0;
				}

				$result[$key] = $result[$key] + 1;
				$activityIds[] = $activity['ID'];
			}
		}

		return $result;
	}

	public function serializeProblems(Problem $problem): array
	{
		return [
			'type' => $problem->type(),
			'problemCount' => $problem->problemCount(),
			'payload' => [
				'badRecords' => $this->prepareTableData($problem->records()),
				'activities' => $this->prepareTableData($problem->activities()),
				'extra' => $problem->extra()
			]
		];
	}

	private function prepareTableData(array $rawActivities): array
	{
		$activities = [];
		foreach ($rawActivities as $record)
		{
			if (is_array($record))
			{
				$this->dateTimeToString($record);
				$activities[] = array_values($record);
			}
		}

		return $activities;
	}

	private function dateTimeToString(array &$row): void
	{
		foreach ($row as $key => &$val) {
			if ($val instanceof Date)
			{
				$row[$key] = $val->toString();
			}
		}
	}


}
