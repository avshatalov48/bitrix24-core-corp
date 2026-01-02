<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Main\Application;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\TaskTable;

class DeleteTask
{
	use ConfigTrait;

	public function __invoke(array $fullTaskData): void
	{
		if (!$this->config->getRuntime()->isMovedToRecyclebin())
		{
			TaskTable::delete($fullTaskData['ID']);
		}
		else
		{
			$sql = "DELETE FROM b_tasks WHERE ID = " . $fullTaskData['ID'];
			Application::getConnection()->query($sql);
		}
	}
}