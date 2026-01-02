<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Im\V2\Service\Locator;
use Bitrix\Im\V2\Service\Messenger;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\TaskTrait;
use Bitrix\Tasks\Integration\Bizproc\Listener;
use Bitrix\Tasks\Integration\CRM\TimeLineManager;
use Bitrix\Tasks\Internals\TaskObject;

class RunIntegration
{
	use TaskTrait;
	use ConfigTrait;

	public function __invoke(array $fields, TaskObject $taskBeforeUpdate): void
	{
		$application = Application::getInstance();

		(new TimeLineManager($taskBeforeUpdate->getId(), $this->config->getUserId()))
			->onTaskUpdated($taskBeforeUpdate)
			->save();

		$runtime = $this->config->getRuntime();
		if (!$this->config->isSkipBP())
		{
			Listener::onTaskUpdate($taskBeforeUpdate->getId(), $fields, $runtime->getEventTaskData());
		}

		if (Loader::includeModule('im'))
		{
			$task = $this->getTaskObject($taskBeforeUpdate->getId());
			$application
			&& $application->addBackgroundJob(
				function () use ($task) {
					Locator::getMessenger()->updateTask($task);
				}
			);
		}
	}
}