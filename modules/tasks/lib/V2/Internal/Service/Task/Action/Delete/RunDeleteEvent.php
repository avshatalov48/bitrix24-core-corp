<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait\ConfigTrait;
use Bitrix\Tasks\Integration\Bizproc\Listener;
use Bitrix\Tasks\Scrum\Internal\ItemTable;

class RunDeleteEvent
{
	use ConfigTrait;

	public function __invoke(array $fullTaskData): void
	{
		$deleteEventParameters = [
			'FLOW_ID' => (int)$fullTaskData['FLOW_ID'],
			'USER_ID' => $this->config->getUserId(),
		];

		$taskId = (int)$fullTaskData['ID'];

		$parameters = array_merge($deleteEventParameters, $this->config->getByPassParameters());

		foreach (GetModuleEvents('tasks', 'OnTaskDelete', true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, [$taskId, $parameters]);
		}

		if (!$this->config->isSkipBP())
		{
			Listener::onTaskDelete($taskId);
		}

		ItemTable::deactivateBySourceId($taskId);
	}
}