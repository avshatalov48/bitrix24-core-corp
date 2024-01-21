<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Recovery;

use Bitrix\Crm\Activity\LightCounter\ActCounterLightTimeTable;
use Bitrix\Crm\Counter\ProblemDetector\Detector;
use Bitrix\Crm\Counter\ProblemDetector\ProblemDetectorQueries;

class LightCounterCompleted implements AsyncRecovery
{

	use AsyncTrait;

	private ProblemDetectorQueries $queries;

	protected Config $config;

	public function __construct()
	{
		$this->config = Config::getInstance();
		$this->queries = ProblemDetectorQueries::getInstance();
	}

	public function supportedType(): string
	{
		return Detector::PROBLEM_TYPE_LIGHTCOUNTER_COMPLETED;
	}

	public function fixStepByStep(): bool
	{
		$badRecordsIds = $this->queries->queryLightCounterCompletedIds($this->config->getLimit());

		if (empty($badRecordsIds))
		{
			return AsyncRecovery::ASYNC_DONE;
		}

		ActCounterLightTimeTable::deleteByIds($badRecordsIds);

		return $this->checkIfDone($badRecordsIds, $this->config->getLimit());
	}
}