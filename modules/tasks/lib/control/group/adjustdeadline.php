<?php

namespace Bitrix\Tasks\Control\Group;

use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Util\Result;

class AdjustDeadline
{
	public function runBatch(int $userId, array $taskIds, array $adjust): array
	{
		$result = [];
		$registry = TaskRegistry::getInstance();
		$registry->load($taskIds, true);
		$control = new Task($userId);

		$termAdjust = $adjust['num'].' '.$adjust['type'];

		foreach ($taskIds as $id)
		{
			$addResult = new Result();
			$task = $registry->getObject($id, true);

			if (!$task)
			{
				continue;
			}

			$time = $task->getDeadline();

			$taskDeadline = $time ? $time->getTimestamp() : '';

			if (!$taskDeadline)
			{
				$addResult->addError(0, 'Some parameter is wrong.');
				$result[] = [
					$addResult,
					'taskId' => $id,
				];
				continue;
			}

			$deadline = DateTime::createFromTimestamp($taskDeadline);
			$newDeadline = $deadline->add($termAdjust);
			$control->update($id, [
				'DEADLINE' => $newDeadline,
			]);

			$result[] = [
				$addResult,
				'taskId' => $id,
			];
		}

		return $result;
	}
}
