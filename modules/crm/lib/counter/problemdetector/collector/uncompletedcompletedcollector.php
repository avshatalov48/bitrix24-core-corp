<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Collector;

use Bitrix\Crm\Counter\ProblemDetector\Detector;
use Bitrix\Crm\Counter\ProblemDetector\Problem;
use Bitrix\Crm\Counter\ProblemDetector\ProblemDetectorQueries;

class UncompletedCompletedCollector implements Collector
{

	private ProblemDetectorQueries $queries;

	public function __construct()
	{
		$this->queries = ProblemDetectorQueries::getInstance();
	}

	public function collect(): Problem
	{
		$completedIds = $this->queries->queryUncompletedCompletedIds(Collector::COLLECT_LIMIT);

		if (empty($completedIds))
		{
			return Problem::makeEmptyProblem(Detector::PROBLEM_TYPE_UNCOMPLETED_COMPLETED);
		}

		$problemRecords = $this->queries->queryUncompletedFields($completedIds);

		$activitiesIds = array_map(function (array $item) {
			return $item['ACTIVITY_ID'];
		}, $problemRecords);

		return new Problem(
			Detector::PROBLEM_TYPE_UNCOMPLETED_COMPLETED,
			count($completedIds),
			$problemRecords,
			$this->queries->getActivityFields($activitiesIds)
		);
	}

}
