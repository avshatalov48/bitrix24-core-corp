<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\Control\Handler\TaskFieldHandler;
use Bitrix\Tasks\Processor\Task\Scheduler;

class PrepareDatePlan implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		if (!$this->config->isNeedCorrectDatePlan())
		{
			return $fields;
		}

		$fieldHandler = new TaskFieldHandler($this->config->getUserId(), $fields, $fullTaskData);

		$parentChanged = $fieldHandler->isParentChanged();
		$datesChanged = $fieldHandler->isDatesChanged();
		$followDatesChanged = $fieldHandler->isFollowDates();

		$taskId = (int)$fullTaskData['ID'];

		if ($parentChanged)
		{
			// task was attached previously, and now it is being unattached or reattached to smth else
			// then we need to recalculate its previous parent...
			$scheduler = Scheduler::getInstance($this->config->getUserId());
			$shiftResultPrev = $scheduler->processEntity(
				$taskId,
				$fullTaskData,
				[
					'MODE' => 'BEFORE_DETACH',
				]
			);
			if ($shiftResultPrev->isSuccess())
			{
				$shiftResultPrev->save(['!ID' => $taskId]);
			}
		}
		else
		{
			if (array_key_exists('PARENT_ID', $fields))
			{
				unset($fields['PARENT_ID']);
			}
		}

		// when updating end or start date plan, we need to be sure the time is correct
		if (
			$parentChanged
			|| $datesChanged
			|| $followDatesChanged
		)
		{
			$scheduler = Scheduler::getInstance($this->config->getUserId());
			$shiftResult = $scheduler->processEntity(
				$taskId,
				$fields,
				[
					'MODE' => $parentChanged ? 'BEFORE_ATTACH' : '',
				]
			);

			$this->config->getRuntime()->setShiftResult($shiftResult);
			if (!$shiftResult->isSuccess())
			{
				return $fields;
			}

			$shiftData = $shiftResult->getImpactById($taskId);
			if ($shiftData)
			{
				$startValue = $shiftData['START_DATE_PLAN'];
				$fields['START_DATE_PLAN'] = $startValue ?? 0;

				$endValue = $shiftData['END_DATE_PLAN'];
				$fields['END_DATE_PLAN'] = $endValue ?? 0;

				$fields['DURATION_PLAN_SECONDS'] = $shiftData['DURATION_PLAN_SECONDS'];

				$this->config->getRuntime()->setLegacyOperationResultData('SHIFT_RESULT', $shiftResult->getData());
			}
		}

		if (
			isset($fields['END_DATE_PLAN'])
			&& empty($fields['END_DATE_PLAN'])
		)
		{
			$fields['DURATION_PLAN'] = 0;
		}

		$pipeline = new PreparePipeline($this->config, [
			PrepareDurationPlan::class,
		]);

		return $pipeline($fields, $fullTaskData);
	}
}