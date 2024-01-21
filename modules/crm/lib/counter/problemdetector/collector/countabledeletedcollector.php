<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Collector;

use Bitrix\Crm\Counter\ProblemDetector\Detector;
use Bitrix\Crm\Counter\ProblemDetector\Problem;
use Bitrix\Crm\Counter\ProblemDetector\ProblemDetectorQueries;

class CountableDeletedCollector implements Collector
{
	private ProblemDetectorQueries $queries;

	public function __construct()
	{
		$this->queries = ProblemDetectorQueries::getInstance();
	}

	public function collect(): Problem
	{
		$deletedIds = $this->queries->queryCountableDeletedIds(Collector::COLLECT_LIMIT);

		if (empty($deletedIds))
		{
			return Problem::makeEmptyProblem(Detector::PROBLEM_TYPE_COUNTABLE_DELETED);
		}

		$problemRecords = $this->queries->queryCountableFields($deletedIds);

		return new Problem(
			Detector::PROBLEM_TYPE_COUNTABLE_DELETED,
			count($deletedIds),
			$problemRecords,
			[]
		);
	}
}