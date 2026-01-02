<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Exception\TaskNotExistsException;
use Bitrix\Tasks\Control\Exception\TaskStopDeleteException;
use Bitrix\Tasks\Control\Exception\WrongTaskIdException;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\V2\Public\Command\Task\DeleteTaskCommand;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\AddCrmEvent;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\CleanCache;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Config\DeleteConfig;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\DeleteScenario;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\MoveToRecyclebin;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\PushHandler;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\RecountSort;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteChecklists;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteFavorite;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteFiles;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteLivefeedLogs;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteParameters;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteResults;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteSearchIndex;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteSort;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteTags;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteTemplateDependencies;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteTopics;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteUserFields;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteUserOptions;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\Relation\DeleteViews;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\RunBeforeDeleteEvent;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\RunDeleteEvent;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\RunIntegration;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\SendNotification;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\SoftDeleteRelations;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\StopTimer;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\SyncHandler;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\UnlinkTags;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\UpdateParents;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Delete\UpdateTemplates;
use Bitrix\Tasks\V2\Internal\Repository\TaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\CounterService;
use Bitrix\Tasks\V2\Internal\Repository\TaskStageRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\Esg\EgressInterface;

class DeleteService
{
	public function __construct(
		private readonly TaskRepositoryInterface $repository,
		private readonly EgressInterface $egressController,
		private readonly TaskStageRepositoryInterface $taskStageRepository,
		private readonly CounterService $counterService,
	)
	{
		
	}

	/**
	 * @throws TaskNotExistsException
	 * @throws TaskStopDeleteException
	 * @throws WrongTaskIdException
	 */
	public function delete(int $taskId, DeleteConfig $config): void
	{
		if ($taskId <= 0)
		{
			throw new WrongTaskIdException();
		}

		if (!$this->repository->getById($taskId))
		{
			throw new TaskNotExistsException();
		}

		$this->loadMessages();

		$compatibilityRepository = Container::getInstance()->getTaskCompatabilityRepository();

		$fullTaskData = $compatibilityRepository->getTaskData($taskId);

		$isCanceled = !(new RunBeforeDeleteEvent($config))($fullTaskData);
		if ($isCanceled)
		{
			throw new TaskStopDeleteException();
		}

		$taskBefore = $compatibilityRepository->getTaskObject($taskId);

		(new MoveToRecyclebin($config))($fullTaskData);

		$this->counterService->collect($taskId);

		(new AddCrmEvent($config))($fullTaskData);

		(new StopTimer())($fullTaskData);

		(new SoftDeleteRelations($config))($fullTaskData);

		if (!$config->getRuntime()->isMovedToRecyclebin())
		{
			(new DeleteFiles())($fullTaskData);
			(new DeleteTags())($fullTaskData);
			(new DeleteFavorite())($fullTaskData);
			(new DeleteSort())($fullTaskData);
			(new DeleteUserOptions())($fullTaskData);
			$this->taskStageRepository->deleteByTaskId($taskId);
			(new DeleteChecklists())($fullTaskData);
			(new DeleteResults($config))($fullTaskData);
			(new DeleteViews())($fullTaskData);
			(new DeleteParameters())($fullTaskData);
			(new DeleteSearchIndex())($fullTaskData);
			(new DeleteTemplateDependencies())($fullTaskData);
			(new DeleteTopics())($fullTaskData);
			(new DeleteUserFields())($fullTaskData);
			(new DeleteLivefeedLogs())($fullTaskData);
		}

		(new CleanCache())($fullTaskData);

		(new RecountSort())($fullTaskData);

		(new UpdateParents())($fullTaskData);

		(new UpdateTemplates())($fullTaskData);

		(new SyncHandler($config))($fullTaskData);

		(new PushHandler($config))($fullTaskData);

		(new RunDeleteEvent($config))($fullTaskData);

		$this->repository->delete($taskId, $config->getRuntime()->isMovedToRecyclebin());

		(new DeleteScenario())($fullTaskData);

		(new UnlinkTags($config))($fullTaskData);

		(new SendNotification($config))($taskBefore);

		$this->counterService->addEvent(EventDictionary::EVENT_AFTER_TASK_DELETE, $fullTaskData);

		(new RunIntegration($config))($fullTaskData);

		// notify external services about a deleted task
		$this->egressController->process(
			new DeleteTaskCommand(
				taskId: $taskId,
				config: $config,
			)
		);
	}
	
	private function loadMessages(): void
	{
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/tasks/lib/control/task.php');
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/tasks/lib/control/handler/taskfieldhandler.php');
	}
}
