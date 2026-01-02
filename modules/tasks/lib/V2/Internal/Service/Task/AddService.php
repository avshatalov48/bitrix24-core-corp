<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Exception\TaskAddException;
use Bitrix\Tasks\Control\Exception\TaskNotExistsException;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddParent;
use Bitrix\Tasks\V2\Public\Command\Task\AddTaskCommand;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\FormV2Feature;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddRelatedTasks;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddFavorite;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddHistoryLog;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddLastActivity;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddLegacyFiles;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddMembers;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddParameters;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddScenario;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddSearchIndex;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddSync;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddTags;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddUserOptions;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\AddWebDavFiles;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\CleanCache;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Config\AddConfig;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Pin;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\PostComment;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\RunAddEvent;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\RunIntegration;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\SendAnalytics;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\SendNotification;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\SendPush;
use Bitrix\Tasks\V2\Internal\Repository\ChatRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\CounterService;
use Bitrix\Tasks\V2\Internal\Service\Esg\EgressInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\Prepare\Add\EntityFieldService;

class AddService
{
	public function __construct(
		private readonly TaskRepositoryInterface $repository,
		private readonly EgressInterface $egressController,
		private readonly ChatRepositoryInterface $chatRepository,
		private readonly CounterService $counterService,
	)
	{
		
	}

	/**
	 * @throws TaskNotExistsException
	 * @throws TaskAddException
	 */
	public function add(Entity\Task $task, AddConfig $config): array
	{
		$this->loadMessages();

		$source = $task->source;

		[$task, $fields] = (new EntityFieldService())->prepare($task, $config);

		$id = $this->repository->save($task);

		$fields['ID'] = $id;

		(new AddScenario($config))($fields);

		(new AddFavorite($config))($fields);

		(new AddMembers($config))($fields);

		(new AddParameters($config))($fields);

		(new AddLegacyFiles($config))($fields);

		(new AddTags($config))($fields);

		(new AddWebDavFiles($config))($fields);

		(new SendNotification($config))($fields);

		(new AddUserOptions($config))($fields);

		$this->counterService->addEvent(EventDictionary::EVENT_AFTER_TASK_ADD, $fields);

		(new AddSync($config))($fields);

		(new AddHistoryLog($config))($fields);

		$fields = (new RunAddEvent($config))($fields);

		$compatibilityRepository = Container::getInstance()->getTaskCompatabilityRepository();

		$fullTaskData = $compatibilityRepository->getTaskData($fields['ID']);

		(new AddSearchIndex())($fullTaskData);

		(new CleanCache($config))($fullTaskData);

		(new AddLastActivity())($fields);

		(new PostComment($config))($fields, $fullTaskData);

		(new SendPush($config))($fields, $fullTaskData);

		(new AddRelatedTasks($config))($fields);

		(new AddParent($config))($fields);

		(new Pin($config))($fields);

		(new RunIntegration($config))($fields, $source);

		(new SendAnalytics($config))($fields);

		// get task object with prepopulated data
		$task = $this->repository->getById($id);

		if ($task === null)
		{
			throw new TaskAddException();
		}

		$isMiniFormOn = FormV2Feature::isOn('miniform');
		$isFullFormOn = FormV2Feature::isOn('', $task->group?->id);

		if ($isMiniFormOn && !$isFullFormOn)
		{
			return [$task, $fields];
		}

		// notify external services about newly created task
		$createdTask = $this->egressController->processAddTaskCommand(
			command: new AddTaskCommand(
				task: $task,
				config: $config,
			)
		);

		$this->chatRepository->save($createdTask->chatId, $createdTask->id);

		return [$createdTask, $fields];
	}

	private function loadMessages(): void
	{
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/tasks/lib/control/task.php');
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/tasks/lib/control/handler/taskfieldhandler.php');
	}
}
