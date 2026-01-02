<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Processor\Task\Scheduler;

class PrepareDatePlan implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		if (!$this->config->isNeedCorrectDatePlan())
		{
			return $fields;
		}

		$startDatePlan = (string)($fields['START_DATE_PLAN'] ?? '');
		$endDatePlan = (string)($fields['END_DATE_PLAN'] ?? '');
		if (empty($startDatePlan) && empty($endDatePlan))
		{
			return $fields;
		}

		$scheduler = Scheduler::getInstance($this->config->getUserId());
		$shiftResult = $scheduler->processEntity(
			0,
			$fields,
			[
				'MODE' => 'BEFORE_ATTACH',
			]
		);

		$this->config->getRuntime()->setShiftResult($shiftResult);

		if ($shiftResult->isSuccess())
		{
			$shiftData = $shiftResult->getImpactById(0);
			if ($shiftData)
			{
				$fields['START_DATE_PLAN'] = $shiftData['START_DATE_PLAN'];
				$fields['END_DATE_PLAN'] = $shiftData['END_DATE_PLAN'];
				$fields['DURATION_PLAN_SECONDS'] = $shiftData['DURATION_PLAN_SECONDS'];
			}
		}

		$pipeline = new PreparePipeline($this->config, [
			PrepareDurationPlan::class
		]);

		return $pipeline($fields);
	}
}