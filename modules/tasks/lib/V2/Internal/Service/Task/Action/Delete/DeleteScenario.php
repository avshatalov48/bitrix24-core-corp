<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Tasks\Internals\Task\ScenarioTable;

class DeleteScenario
{
	public function __invoke(array $fullTaskData): void
	{
		ScenarioTable::delete((int)$fullTaskData['ID']);
	}
}