<?php

namespace Bitrix\Tasks\Replication\Template\Common;

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
use Bitrix\Tasks\Replication\ProducerInterface;
use Bitrix\Tasks\Replication\Repository\TemplateRepository;
use Bitrix\Tasks\Replication\RepositoryInterface;
use Bitrix\Tasks\Replication\Template\Common\Parameter\TemplateParameter;
use Bitrix\Tasks\Replication\Template\Common\Service\ChecklistService;
use Bitrix\Tasks\Replication\Template\Common\Service\CommentService;
use Bitrix\Tasks\Replication\Template\Common\Service\DefaultHistoryService;
use Bitrix\Tasks\Replication\Template\Common\Service\ScenarioService;
use Bitrix\Tasks\Replication\Template\Common\Service\SubTaskService;
use Bitrix\Tasks\Replication\Template\Repetition\Time\Service\ExecutionService;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;
use CUserTypeManager;
use Exception;

class TemplateTaskProducer implements ProducerInterface
{
	protected TaskAccessController $accessController;
	protected HistoryServiceInterface $templateHistoryService;
	protected TaskObject $task;
	protected Result $currentResult;
	protected ?\Bitrix\Main\Type\DateTime $createdDate = null;

	protected array $fields;
	protected int $userId;
	protected int $parentTaskId = 0;
	protected int $perHitUserId = 0;

	public function __construct(protected RepositoryInterface $repository)
	{
		$this->init();
	}

	public function setParentTaskId(int $taskId): static
	{
		$this->parentTaskId = $taskId;

		return $this;
	}

	public function setCreatedDate(\Bitrix\Main\Type\DateTime $createdDate): static
	{
		$this->createdDate = $createdDate;

		return $this;
	}

	protected function init(): void
	{
		$template = $this->repository->getEntity();

		$this->userId = is_null($template) ? 0 : $template->getCreatedBy();
		$this->accessController = new TaskAccessController($this->userId);
		$this->fields = (new TemplateParameter($this->repository))->getData();
		$this->initHistoryService();
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

	protected function replacePerHitUserId(bool $switchBack = false): static
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

	protected function buildTree(): array
	{
		$children = $this->repository->getEntity()->getChildren();
		if (empty($children))
		{
			return [];
		}

		$walkQueue = [$this->repository->getEntity()->getId()];
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

	protected function getCreationResult(array $results): Result
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

	protected function addParentIdToFields(): static
	{
		if ($this->parentTaskId > 0)
		{
			$this->fields['PARENT_ID'] = $this->parentTaskId;
		}

		return $this;
	}

	protected function overrideCreatedDate(): static
	{
		if (!is_null($this->createdDate))
		{
			$this->fields['CREATED_DATE'] = $this->createdDate;

			return $this;
		}

		$executionService = new ExecutionService($this->repository);
		$this->fields['CREATED_DATE'] = new DateTime(
			UI::formatDateTime(
				MakeTimeStamp($executionService->getTemplateCurrentExecutionTime())
			// - User::getTimeZoneOffset($this->fields['CREATED_BY'])
			)
		);

		return $this;
	}

	protected function overrideChangedDate(): static
	{
		$this->fields['CHANGED_DATE'] = new DateTime();

		return $this;
	}

	protected function filterFiles(): static
	{
		if (!Loader::includeModule('disk'))
		{
			return $this;
		}

		$filteredAttachmentIds = [];
		$templateFiles = $this->fields[UserField::getMainSysUFCode()] ?? [];
		$templateFiles = $templateFiles === false ? [] : $templateFiles;
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

	protected function overrideActivityDate(): static
	{
		$this->fields['ACTIVITY_DATE'] = new DateTime();

		return $this;
	}

	protected function setTaskScenario(): Result
	{
		$scenarioService = new ScenarioService($this->task);

		return $scenarioService->insert();
	}

	protected function setCheckListToTask(): Result
	{
		$checkListService = new ChecklistService($this->repository, $this->task, $this->userId);

		return $checkListService->copyToTask();
	}

	protected function createSubTasks(): Result
	{
		$template = $this->repository->getEntity();
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

	protected function clearTaskComments(): Result
	{
		$commentService = new CommentService($this->task, $this->userId);

		return $commentService->clear();
	}

	protected function createTask(): Result
	{
		$result = new Result();

		$this->addParentIdToFields()->overrideCreatedDate()->overrideActivityDate()->overrideChangedDate()->filterFiles(
			);

		try
		{
			$this->task = (new Task($this->userId))->fromAgent()->add($this->fields);
		}
		catch (Exception $exception)
		{
			$result->addError(new Error($exception->getMessage()));

			$message = Loc::getMessage('TASKS_REGULAR_TEMPLATE_TASK_PRODUCER_TASK_WAS_NOT_CREATED');
			if ($message)
			{
				$this->templateHistoryService->write($message, $result);
			}

			return $result;
		}

		return $result;
	}

	protected function produceSubTemplates(): Result
	{
		$template = $this->repository->getEntity();
		$tree = $this->buildTree();
		if (!is_array($tree[$template->getId()] ?? null))
		{
			return new Result();
		}

		foreach ($tree[$template->getId()] as $templateId)
		{
			$producer = new static(new TemplateRepository($templateId));
			$result = $producer->setParentTaskId($this->task->getId())->setCreatedDate($this->task->getCreatedDate())
			                   ->produceTask();
			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return new Result();
	}

	protected function checkCanCreateTask(): Result
	{
		$result = new Result();
		$model = TaskModel::createFromArray($this->fields);
		if (!$this->accessController->check(ActionDictionary::ACTION_TASK_SAVE, null, $model))
		{
			$result->addErrors($this->accessController->getErrorCollection()->toArray());

			$message = Loc::getMessage('TASKS_REGULAR_TEMPLATE_TASK_PRODUCER_TASK_WAS_NOT_CREATED');
			if ($message)
			{
				$this->templateHistoryService->write($message, $result);
			}

			return $result;
		}

		return $result;
	}

	protected function writeReplicationResultToTemplateHistory(): void
	{
		$message = Loc::getMessage('TASKS_REGULAR_TEMPLATE_TASK_PRODUCER_TASK_CREATED', [
			'#TASK_ID#' => $this->task->getId(),
		]);

		if (!$message)
		{
			return;
		}

		$this->templateHistoryService->write($message, $this->currentResult);
	}

	public function writeFileErrorToTemplateHistory(int $fileId): void
	{
		$message = Loc::getMessage('TASKS_REGULAR_TEMPLATE_TASK_PRODUCER_TASK_CREATED_WITH_ERRORS');
		if (!$message)
		{
			return;
		}

		$this->templateHistoryService->write(
			$message,
			(new Result())->addError(
				new Error(
					Loc::getMessage('TASKS_REGULAR_TEMPLATE_TASK_PRODUCER_FILE_NOT_FOUND') . ' (#' . $fileId . ')'
				)
			)
		);
	}

	protected function updateTemplateReplicationCounter(): Result
	{
		$result = new Result();
		$template = $this->repository->getEntity();
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

	protected function initHistoryService(): void
	{
		$this->templateHistoryService = new DefaultHistoryService();
	}
}