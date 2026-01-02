<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\Internals\Helper\Task\Dependence;
use Bitrix\Tasks\Processor\Task\Scheduler;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use CTasks;

class AddParent
{
	use ConfigTrait;

	public function __invoke(array $fields): void
	{
		$taskId = $fields['ID'];

		$parentId = 0;
		if (array_key_exists('PARENT_ID', $fields))
		{
			$parentId = (int)$fields['PARENT_ID'];
		}

		// backward compatibility with PARENT_ID
		if ($parentId)
		{
			Dependence::attachNew($taskId, $parentId);
		}

		$shiftResult = $this->config->getRuntime()->getShiftResult();
		if (!$shiftResult)
		{
			return;
		}

		if ($parentId)
		{
			$childrenCountDbResult = CTasks::GetChildrenCount([], $parentId);
			$fetchedChildrenCount = $childrenCountDbResult->Fetch();
			$childrenCount = (int)($fetchedChildrenCount ? $fetchedChildrenCount['CNT'] : 0);

			if ($childrenCount === 1)
			{
				$scheduler = Scheduler::getInstance($this->config->getUserId());
				$shiftResult = $scheduler->processEntity(
					0,
					$fields,
					['MODE' => 'BEFORE_ATTACH'],
				);
			}
		}

		$shiftResult->save(['!ID' => 0]);
	}
}
