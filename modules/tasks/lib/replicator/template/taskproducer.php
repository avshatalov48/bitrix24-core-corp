<?php

namespace Bitrix\Tasks\Replicator\Template;

use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Control\Task;
use Bitrix\Tasks\Control\Template;
use Bitrix\Tasks\Integration\Disk\UserField;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Provider\TasksUFManager;
use Bitrix\Tasks\Replicator\Producer;
use Bitrix\Tasks\Replicator\Template\Service\StageService;
use Bitrix\Tasks\Replicator\Template\Repetition\Time\Service\ExecutionService;
use Bitrix\Tasks\Replicator\Template\Repository\TemplateRepository;
use Bitrix\Tasks\Replicator\Template\Service\CheckListService;
use Bitrix\Tasks\Replicator\Template\Service\CommentService;
use Bitrix\Tasks\Replicator\Template\Service\TemplateHistoryService;
use Bitrix\Tasks\Replicator\Template\Service\ScenarioService;
use Bitrix\Tasks\Replicator\Template\Service\SubTaskService;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;
use CUserTypeManager;
use Exception;

class TaskProducer implements Producer
{
	private TaskAccessController $accessController;
	private TemplateHistoryService $templateHistoryService;
	private TaskObject $task;
	private Result $currentResult;
	private array $fields;
	private int $userId;
	private int $parentId = 0;
	private int $perHitUserId = 0;

	public function __construct(private Repository $repository)
	{
		$this->init();
	}

	private function init(): void
	{
		$template = $this->repository->getTemplate();

		$this->userId = is_null($template) ? 0 : $template->getCreatedByMemberId();
		$this->accessController = new TaskAccessController($this->userId);
		$this->templateHistoryService = new TemplateHistoryService($this->repository);
		$this->fields = (new TemplateParameter($this->repository))->getData();
		$this->replacePerHitUserId();
	}

	public function produceTask(): Result
	{
		$this->currentResult = $this->checkCanCreateTask();
		if (!$this->currentResult->isSuccess())
		{
			return $this->currentResult;
		}

		$this->currentResult = $this->createTask();
		if (!$this->currentResult->isSuccess())
		{
			return $this->currentResult;
		}

		$results[] = $this->pinInStage();
		$results[] = $this->updateTemplateReplicationCounter();
		$results[] = $this->setTaskScenario();
		$results[] = $this->setCheckListToTask();
		$results[] = $this->clearTaskComments();
		$results[] = $this->createSubTasks();
		$results[] = $this->produceSubTemplates();

		$this->currentResult = $this->getCreationResult($results);

		$this->writeReplicationResultToTemplateHistory();
		$this->replacePerHitUserId(true);

		return $this->currentResult;
	}

	private function replacePerHitUserId(bool $switchBack = false): static
	{
		if ($switchBack)
		{
			User::setOccurAsId($this->perHitUserId);
			return $this;
		}
		$this->perHitUserId = (int)User::getOccurAsId();
		User::setOccurAsId($this->userId); // IDK what is what
		return $this;
	}

	private function buildTree(): array
	{
		$children = $this->repository->getTemplate()->getChildren();
		if (empty($children))
		{
			return [];
		}

		$walkQueue = [$this->repository->getTemplate()->getId()];
		$treeBundles = [];

		foreach ($children as $subTemplate)
		{
			$treeBundles[$subTemplate['BASE_TEMPLATE_ID']][] = $subTemplate['ID'];
		}

		$tree = $treeBundles;
		$met = [];
		while (!empty($walkQueue))
		{
			$topTemplate = array_shift($walkQueue);
			if (isset($met[$topTemplate])) // hey, i`ve met this guy before!
			{
				return [];
			}
			$met[$topTemplate] = true;

			if (is_array($treeBundles[$topTemplate] ?? null))
			{
				foreach ($treeBundles[$topTemplate] as $template)
				{
					$walkQueue[] = $template;
				}
			}
			unset($treeBundles[$topTemplate]);
		}

		return $tree;
	}

	private function setParentId(int $parentId): static
	{
		$this->parentId = $parentId;
		return $this;
	}

	private function getCreationResult(array $results): Result
	{
		$result = new Result();
		foreach ($results as $subResult)
		{
			/** @var Result $subResult */
			if (!$subResult->isSuccess())
			{
				$result->addErrors($subResult->getErrors());
			}
		}

		return $result;
	}

	private function addParentIdToFields(): static
	{
		if ($this->parentId > 0)
		{
			$this->fields['PARENT_ID'] = $this->parentId;
		}

		return $this;
	}

	private function overrideCreatedDate(): static
	{
		$executionService = new ExecutionService($this->repository);
		$this->fields['CREATED_DATE'] = new DateTime(
			UI::formatDateTime(
				MakeTimeStamp($executionService->getTemplateCurrentExecutionTime())
				- User::getTimeZoneOffset($this->fields['CREATED_BY'])
			)
		);

		return $this;
	}

	private function overrideChangedDate(): static
	{
		$this->fields['CHANGED_DATE'] = new DateTime();
		return $this;
	}

	private function filterFiles(): static
	{
		if (!Loader::includeModule('disk'))
		{
			return $this;
		}

		$filteredAttachmentIds = [];
		$templateFiles = $this->fields[UserField::getMainSysUFCode()] ?? [];
		$fileUf = (new CUserTypeManager())->GetUserFields(TasksUFManager::ENTITY_TYPE)[UserField::getMainSysUFCode()];
		foreach ($templateFiles as $fileId => $attachmentId)
		{
			$errors = FileUserType::checkFields($fileUf, $attachmentId, $this->userId);
			if (!empty($errors))
			{
				$this->writeFileErrorToTemplateHistory($fileId);
				continue;
			}

			$filteredAttachmentIds[] = $attachmentId;
		}
		$this->fields[UserField::getMainSysUFCode()] = $filteredAttachmentIds;

		return $this;
	}

	private function overrideActivityDate(): static
	{
		$this->fields['ACTIVITY_DATE'] = new DateTime();
		return $this;
	}

	private function setTaskScenario(): Result
	{
		$scenarioService = new ScenarioService($this->task);
		return $scenarioService->insert();
	}

	private function setCheckListToTask(): Result
	{
		$checkListService = new CheckListService($this->repository, $this->task, $this->userId);
		return $checkListService->copyToTask();
	}

	private function createSubTasks(): Result
	{
		$template = $this->repository->getTemplate();
		if ($template->getMultitask())
		{
			$subTaskService = new SubTaskService($this->repository, $this->task, $this->fields, $this->userId);
			$result = $subTaskService->add();
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return new Result();
	}

	private function clearTaskComments(): Result
	{
		$commentService = new CommentService($this->task, $this->userId);
		return $commentService->clear();
	}

	private function createTask(): Result
	{
		$result = new Result();

		$this
			->addParentIdToFields()
			->overrideCreatedDate()
			->overrideActivityDate()
			->overrideChangedDate()
			->filterFiles()
		;

		try
		{
			$this->task = (new Task($this->userId))->fromAgent()->add($this->fields);
		}
		catch (Exception $exception)
		{
			$result->addError(new Error($exception->getMessage()));

			$message = Loc::getMessage('TASKS_PRODUCER_TASK_WAS_NOT_CREATED');
			if ($message)
			{
				$this->templateHistoryService->write($message, $result);
			}
			return $result;
		}

		return $result;
	}

	private function produceSubTemplates(): Result
	{
		$template = $this->repository->getTemplate();
		$tree = $this->buildTree();
		if (!is_array($tree[$template->getId()] ?? null))
		{
			return new Result();
		}

		foreach ($tree[$template->getId()] as $templateId)
		{
			$producer = new static(new TemplateRepository($templateId));
			$result = $producer->setParentId($this->task->getId())->produceTask();
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return new Result();
	}

	private function checkCanCreateTask(): Result
	{
		$result = new Result();
		$model = TaskModel::createFromArray($this->fields);
		if (!$this->accessController->check(ActionDictionary::ACTION_TASK_SAVE, null, $model))
		{
			$result->addErrors($this->accessController->getErrors());

			$message = Loc::getMessage('TASKS_PRODUCER_TASK_WAS_NOT_CREATED');
			if ($message)
			{
				$this->templateHistoryService->write($message, $result);
			}
			return $result;
		}

		return $result;
	}

	private function writeReplicationResultToTemplateHistory(): void
	{
        $message =  Loc::getMessage('TASKS_PRODUCER_TASK_CREATED', [
            '#TASK_ID#' => $this->task->getId()
        ]);

		if (!$message)
		{
			return;
		}

		$this->templateHistoryService->write($message, $this->currentResult);
	}

	public function writeFileErrorToTemplateHistory(int $fileId): void
	{
		$message = Loc::getMessage('TASKS_PRODUCER_TASK_CREATED_WITH_ERRORS');
		if (!$message)
		{
			return;
		}

		$this->templateHistoryService->write(
			$message,
			(new Result())->addError(
				new Error(Loc::getMessage('TASKS_PRODUCER_FILE_NOT_FOUND') . ' (#' . $fileId . ')')
			)
		);
	}

	private function updateTemplateReplicationCounter(): Result
	{
		$result = new Result();
		$template = $this->repository->getTemplate();
		try
		{
			(new Template($this->userId))->update($template->getId(), [
				'TPARAM_REPLICATION_COUNT' => $template->getTparamReplicationCount() + 1,
			]);
		}
		catch (Exception $exception)
		{
			$result->addError(new Error($exception->getMessage()));
			return $result;
		}

		return $result;
	}

	private function pinInStage(): Result
	{
		$stageService = new StageService($this->repository, $this->task);
		return $stageService->insert();
	}
}
