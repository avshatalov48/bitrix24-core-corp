<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Recovery;

use Bitrix\Crm\Counter\ProblemDetector\Detector;

class UncompletedCompleted extends UncompletedBase implements AsyncRecovery
{
	use AsyncTrait;

	public function supportedType(): string
	{
		return Detector::PROBLEM_TYPE_UNCOMPLETED_COMPLETED;
	}

	public function fixStepByStep(): bool
	{
		$badRecordsIds = $this->queries->queryUncompletedCompletedIds($this->config->getLimit());

		if (empty($badRecordsIds))
		{
			return AsyncRecovery::ASYNC_DONE;
		}

		$this->fixByUncompletedIds($badRecordsIds);

		return $this->checkIfDone($badRecordsIds, $this->config->getLimit());
	}

}