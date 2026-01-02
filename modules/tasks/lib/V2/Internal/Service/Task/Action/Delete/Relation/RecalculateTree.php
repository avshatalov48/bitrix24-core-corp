<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait\ConfigTrait;
use Bitrix\Tasks\Processor\Task\Scheduler;

class RecalculateTree
{
	use ConfigTrait;

	public function __invoke(array $fullTaskData): void
	{
		if (empty($fullTaskData['PARENT_ID']))
		{
			return;
		}

		if (empty($fullTaskData['START_DATE_PLAN']))
		{
			return;
		}

		if (empty($fullTaskData['END_DATE_PLAN']))
		{
			return;
		}

		// we need to scan for parent bracket tasks change...
		$scheduler = Scheduler::getInstance($this->config->getUserId());
		// we could use MODE => DETACH here, but there we can act in more effective way by
		// re-calculating tree of PARENT_ID after removing link between ID and PARENT_ID
		// we also do not need to calculate detached tree
		// it is like DETACH_AFTER
		$shiftResult = $scheduler->processEntity($fullTaskData['PARENT_ID']);
		if ($shiftResult->isSuccess())
		{
			$shiftResult->save();
		}
	}
}