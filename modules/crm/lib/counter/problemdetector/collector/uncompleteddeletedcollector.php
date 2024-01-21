<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Collector;

use Bitrix\Crm\Counter\ProblemDetector\Detector;
use Bitrix\Crm\Counter\ProblemDetector\Problem;
use Bitrix\Crm\Counter\ProblemDetector\ProblemDetectorQueries;

class UncompletedDeletedCollector implements Collector
{
	private ProblemDetectorQueries $queries;

	public function __construct()
	{
		$this->queries = ProblemDetectorQueries::getInstance();
	}

	public function collect(): Problem
	{
		$deletedIds = $this->queries->queryUncompletedDeletedIds(Collector::COLLECT_LIMIT);

		if (empty($deletedIds))
		{
			return Problem::makeEmptyProblem(Detector::PROBLEM_TYPE_UNCOMPLETED_DELETED);
		}

		$problemRecords = $this->queries->queryUncompletedFields($deletedIds);

		return new Problem(
			Detector::PROBLEM_TYPE_UNCOMPLETED_DELETED,
			count($deletedIds),
			$problemRecords,
			[]
		);
	}

}