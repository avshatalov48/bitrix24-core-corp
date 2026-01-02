<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\Processor\Task\Scheduler;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;

class CorrectDatePlan
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): void
	{
		if (!$this->config->isCorrectDatePlanDependent())
		{
			return;
		}

		$shiftResult = $this->config->getRuntime()->getShiftResult();
		if (!$shiftResult)
		{
			$shiftResult = Scheduler::getInstance($this->config->getUserId())->processEntity($fullTaskData['ID'], $fields);
		}

		$saveResult = $shiftResult->save(['!ID' => $fullTaskData['ID']]);
		if ($saveResult->isSuccess())
		{
			$this->config->getRuntime()->setLegacyOperationResultData('SHIFT_RESULT', $shiftResult->exportData());
		}
	}
}
