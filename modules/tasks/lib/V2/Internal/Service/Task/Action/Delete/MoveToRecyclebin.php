<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete;

use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Trait\ConfigTrait;
use Bitrix\Tasks\Integration\Recyclebin\Task;
use Bitrix\Tasks\Internals\Log\LogFacade;

class MoveToRecyclebin
{
	use ConfigTrait;

	public function __invoke(array $fullTaskData): bool
	{
		try
		{
			if (!Loader::includeModule('recyclebin'))
			{
				$this->config->getRuntime()->setMovedToRecyclebin(false);
				return false;
			}

			$result = (bool)Task::OnBeforeTaskDelete((int)$fullTaskData['ID'], $fullTaskData);
			$this->config->getRuntime()->setMovedToRecyclebin($result);

			return $result;
		}
		catch (\Exception $exception)
		{
			LogFacade::logThrowable($exception);
			$this->config->getRuntime()->setMovedToRecyclebin(false);

			return false;
		}
	}
}