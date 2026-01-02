<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Exception\TaskUpdateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ApplicationErrorTrait;
use Bitrix\Tasks\Internals\Log\LogFacade;

class RunUpdateEvent
{
	use ConfigTrait;
	use ApplicationErrorTrait;

	public function __invoke(array $fields, array $sourceTaskData): array
	{
		$fields['META:PREV_FIELDS'] = $sourceTaskData;

		$eventTaskData = $this->config->getRuntime()->getEventTaskData();

		try
		{
			foreach (GetModuleEvents('tasks', 'OnTaskUpdate', true) as $event)
			{
				ExecuteModuleEventEx($event, [(int)$sourceTaskData['ID'], &$fields, &$eventTaskData]);
			}
		}
		catch (\Exception $exception)
		{
			LogFacade::logThrowable($exception);
			throw new TaskUpdateException(
				$this->getApplicationError(Loc::getMessage('TASKS_UNKNOWN_UPDATE_ERROR'))
			);
		}

		unset($fields['META:PREV_FIELDS']);

		$this->config->getRuntime()->setEventTaskData($eventTaskData);

		return $fields;
	}
}