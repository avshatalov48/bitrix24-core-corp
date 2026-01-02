<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Exception\TaskUpdateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ApplicationErrorTrait;

class RunBeforeUpdateEvent
{
	use ConfigTrait;
	use ApplicationErrorTrait;

	/**
	 * @throws TaskUpdateException
	 */
	public function __invoke(array $fields, array $fullTaskData): array
	{
		$eventTaskData = $fullTaskData;

		foreach (GetModuleEvents('tasks', 'OnBeforeTaskUpdate', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, [(int)$fullTaskData['ID'], &$fields, &$eventTaskData]) === false)
			{
				$message = $this->getApplicationError(Loc::getMessage('TASKS_UNKNOWN_UPDATE_ERROR'));
				throw new TaskUpdateException($message);
			}
		}

		$this->config->getRuntime()->setEventTaskData($eventTaskData);

		return $fields;
	}
}