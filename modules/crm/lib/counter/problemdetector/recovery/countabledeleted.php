<?php

namespace Bitrix\Crm\Counter\ProblemDetector\Recovery;


use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Crm\Counter\ProblemDetector\Detector;

class CountableDeleted extends CountableBase implements AsyncRecovery
{
	use AsyncTrait;

	public function supportedType(): string
	{
		return Detector::PROBLEM_TYPE_COUNTABLE_DELETED;
	}

	public function fixStepByStep(): bool
	{
		$badRecordsIds = $this->queries->queryCountableDeletedIds($this->config->getLimit());

		if (empty($badRecordsIds))
		{
			return AsyncRecovery::ASYNC_DONE;
		}

		$badRecords = $this->queries->queryCountableFields($badRecordsIds);

		EntityCountableActivityTable::deleteByIds($badRecordsIds);

		foreach ($badRecords as $item)
		{
			$this->resetCountableByField($item);
		}

		return $this->checkIfDone($badRecordsIds, $this->config->getLimit());
	}

}