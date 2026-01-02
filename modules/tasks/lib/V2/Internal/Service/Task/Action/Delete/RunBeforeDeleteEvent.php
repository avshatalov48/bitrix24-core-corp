<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait\ConfigTrait;

class RunBeforeDeleteEvent
{
	use ConfigTrait;

	public function __invoke(array $fullTaskData): bool
	{
		foreach (GetModuleEvents('tasks', 'OnBeforeTaskDelete', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, [(int)$fullTaskData['ID'], $fullTaskData, $this->config->getByPassParameters()]) === false)
			{
				return false;
			}
		}
		return true;
	}
}