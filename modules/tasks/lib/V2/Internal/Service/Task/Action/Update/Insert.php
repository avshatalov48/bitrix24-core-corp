<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Exception\TaskNotFoundException;
use Bitrix\Tasks\Control\Exception\TaskUpdateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\Control\Handler\TaskFieldHandler;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\TaskTrait;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\TaskTable;

class Insert
{
	use ConfigTrait;
	use TaskTrait;

	public function __invoke(array $fields, array $fullTaskData): TaskObject
	{
		$data = (new PrepareDBFields($this->config))($fields, $fullTaskData);

		$taskId = (int)$fullTaskData['ID'];
		$result = TaskTable::update($taskId, $data);

		if (!$result->isSuccess())
		{
			$messages = $result->getErrorMessages();
			$message = Loc::getMessage('TASKS_UNKNOWN_ADD_ERROR');
			if (!empty($messages))
			{
				$message = array_shift($messages);
			}

			throw new TaskUpdateException($message);
		}

		return $this->getTaskObject($taskId);
	}
}