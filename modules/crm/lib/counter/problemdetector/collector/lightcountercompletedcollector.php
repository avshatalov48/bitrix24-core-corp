<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Collector;

use Bitrix\Crm\Counter\ProblemDetector\Detector;
use Bitrix\Crm\Counter\ProblemDetector\Problem;
use Bitrix\Crm\Counter\ProblemDetector\ProblemDetectorQueries;

class LightCounterCompletedCollector implements Collector
{
	private ProblemDetectorQueries $queries;

	public function __construct()
	{
		$this->queries = ProblemDetectorQueries::getInstance();
	}

	public function collect(): Problem
	{
		$ids = $this->queries->queryLightCounterCompletedIds();

		if (empty($ids))
		{
			return Problem::makeEmptyProblem(Detector::PROBLEM_TYPE_LIGHTCOUNTER_COMPLETED);
		}

		return new Problem(
			Detector::PROBLEM_TYPE_COUNTABLE_COMPLETED,
			count($ids),
			$ids,
			$this->queries->getActivityFields($ids)
		);
	}
}
