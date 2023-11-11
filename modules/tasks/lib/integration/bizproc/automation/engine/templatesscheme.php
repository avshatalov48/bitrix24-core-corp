<?php

namespace Bitrix\Tasks\Integration\Bizproc\Automation\Engine;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Result;
use Bitrix\Socialnetwork\EO_Workgroup;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\Integration\Bizproc\Document\Task;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Kanban\StagesTable;
use Bitrix\Tasks\Scrum\Service\KanbanService;
use Bitrix\Tasks\Scrum\Service\SprintService;

class TemplatesScheme extends \Bitrix\Bizproc\Automation\Engine\TemplatesScheme
{
	private int $userId;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	public function build(): void
	{
		$originalWorkMode = StagesTable::getWorkMode();

		$this->addPersonalTasksTemplates();
		$this->addPlanTasksTemplates();

		if (Loader::includeModule('socialnetwork'))
		{
			$this->addProjectTasksTemplates();
		}

		StagesTable::setWorkMode($originalWorkMode);
	}

	public function addPersonalTasksTemplates(): void
	{
		foreach ($this->getPersonalTasksStages() as $stageId => $stageTitle)
		{
			$documentType = $this->createComplexDocumentType(Task::resolvePersonalTaskType($this->userId));

			$scope = new TemplateScope($documentType, null, (string)$stageId);
			$scope->setNames(null, $stageTitle);
			$scope->setProjectName(Loc::getMessage('TASKS_BP_AUTOMATION_ENGINE_TEMPLATES_SCHEME_USER_TASKS'));

			$this->addTemplate($scope);
		}
	}

	private function getPersonalTasksStages(): array
	{
		return [
			Status::PENDING => Loc::getMessage('TASKS_BP_AUTOMATION_ENGINE_TEMPLATES_SCHEME_PERSONAL_TASKS_STATUS_PENDING'),
			Status::IN_PROGRESS => Loc::getMessage('TASKS_BP_AUTOMATION_ENGINE_TEMPLATES_SCHEME_PERSONAL_TASKS_STATUS_IN_PROGRESS'),
			Status::SUPPOSEDLY_COMPLETED => Loc::getMessage('TASKS_BP_AUTOMATION_ENGINE_TEMPLATES_SCHEME_PERSONAL_TASKS_STATUS_SUPPOSEDLY_COMPLETED'),
			Status::COMPLETED => Loc::getMessage('TASKS_BP_AUTOMATION_ENGINE_TEMPLATES_SCHEME_PERSONAL_TASKS_STATUS_COMPLETED'),
			Status::DEFERRED => Loc::getMessage('TASKS_BP_AUTOMATION_ENGINE_TEMPLATES_SCHEME_PERSONAL_TASKS_STATUS_DEFERRED'),
		];
	}

	private function addPlanTasksTemplates(): void
	{
		$documentType = $this->createComplexDocumentType(Task::resolvePlanTaskType($this->userId));

		StagesTable::setWorkMode(StagesTable::WORK_MODE_USER);
		foreach (StagesTable::getStages($this->userId) as $statusId => $stage)
		{
			$scope = new TemplateScope($documentType, null, $statusId);
			$scope->setNames(null, $stage['TITLE'] ?? '');
			$scope->setProjectName(Loc::getMessage('TASKS_BP_AUTOMATION_ENGINE_TEMPLATES_SCHEME_PLAN_TASKS'));

			$this->addTemplate($scope);
		}
	}

	private function addProjectTasksTemplates(): void
	{
		$projectIterator = $this->getUserWorkgroups();

		while ($project = $projectIterator->fetchObject())
		{
			$documentType = $this->createComplexDocumentType($this->resolveProjectTaskType($project));

			foreach ($this->getProjectStages($project) as $stage)
			{
				$scope = new TemplateScope($documentType, null, $stage['ID']);
				$scope->setNames(null, $stage['TITLE'] ?? '');
				$scope->setProjectName($project->getName());

				$this->addTemplate($scope);
			}
		}
	}

	private function getUserWorkgroups(): Result
	{
		return WorkgroupTable::query()
			->setSelect(['ID', 'NAME', 'SCRUM_MASTER_ID'])
			->registerRuntimeField(
				new Reference(
					'USER_TO_GROUP_RELATION',
					UserToGroupTable::class,
					Join::on('this.ID', 'ref.GROUP_ID')->where('ref.USER_ID', $this->userId),
					['join_type' => Join::TYPE_LEFT],
				),
			)->exec();
	}

	private function resolveProjectTaskType(EO_Workgroup $project): string
	{
		if ($this->isScrumProject($project))
		{
			return Task::resolveScrumProjectTaskType($project->getId());
		}
		else
		{
			return Task::resolveProjectTaskType($project->getId());
		}
	}

	private function createComplexDocumentType(string $documentType): array
	{
		return [
			'tasks',
			Task::class,
			$documentType,
		];
	}

	private function getProjectStages(EO_Workgroup $project): array
	{
		$stages = [];

		if ($this->isScrumProject($project))
		{
			$sprintService = new SprintService();
			$kanbanService = new KanbanService();

			$sprint = $sprintService->getActiveSprintByGroupId($project->getId());
			StagesTable::setWorkMode(StagesTable::WORK_MODE_ACTIVE_SPRINT);

			$stages = $kanbanService->getStages($sprint->getId());
		}
		else
		{
			StagesTable::setWorkMode(StagesTable::WORK_MODE_GROUP);

			$stages = StagesTable::getStages($project->getId());
		}

		return $stages;
	}
	private function isScrumProject(EO_Workgroup $project): bool
	{
		return !is_null($project->getScrumMasterId()) && $project->getScrumMasterId() > 0;
	}
}