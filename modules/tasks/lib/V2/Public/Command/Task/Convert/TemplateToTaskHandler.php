<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Convert;

use Bitrix\Tasks\Control\Exception\TemplateNotFoundException;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Service\AddTaskService;
use Bitrix\Tasks\V2\Public\Provider\TaskFromTemplateProvider;

class TemplateToTaskHandler
{
	public function __construct(
		private readonly AddTaskService $addTaskService,
        private readonly TaskFromTemplateProvider $provider,
	)
	{
	}

	public function __invoke(TemplateToTaskCommand $command): Entity\Task
	{
		$task = $this->provider->getTaskByTemplateId($command->templateId);
		if ($task === null)
		{
			throw new TemplateNotFoundException();
		}

		return $this->addTaskService->add($task, $command->config);
	}
}
