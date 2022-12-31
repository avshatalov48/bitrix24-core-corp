<?php
namespace Bitrix\Tasks\Copy;

use Bitrix\Bizproc\Copy\Implement\Trigger as TriggerImplementer;
use Bitrix\Bizproc\Copy\Integration\Helper as BizprocHelper;
use Bitrix\Bizproc\Copy\Trigger as TriggerCopier;
use Bitrix\Bizproc\Copy\WorkflowTemplate as RobotsCopier;
use Bitrix\Forum\Copy\Implement\Comment as CommentImplementer;
use Bitrix\Forum\Copy\Implement\Topic as TopicImplementer;
use Bitrix\Main\Copy\Container;
use Bitrix\Main\Copy\ContainerCollection;
use Bitrix\Main\Copy\EntityCopier;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Tasks\Copy\CheckList as CheckListCopier;
use Bitrix\Tasks\Copy\Implement\Robots;
use Bitrix\Tasks\Copy\Implement\Stage as StageImplementer;
use Bitrix\Tasks\Copy\Implement\TaskCheckList as CheckListImplementer;
use Bitrix\Tasks\Copy\Implement\Task as TaskImplementer;
use Bitrix\Tasks\Copy\Implement\TaskParams;
use Bitrix\Tasks\Copy\Implement\Template as TemplateImplementer;
use Bitrix\Tasks\Copy\Stage as StageCopier;
use Bitrix\Tasks\Copy\Task as TaskCopier;
use Bitrix\Tasks\Integration\Bizproc\Document\Task as TaskDocumentType;
use Bitrix\Tasks\Kanban\StagesTable;

class TaskManager
{
	private $executiveUserId;
	private $taskIdsToCopy = [];
	private $targetGroupId = 0;

	private $mapIdsCopiedStages = [];

	private $projectTerm = [];

	private $markerChecklist = true;
	private $markerComment = true;

	/**
	 * @var Result
	 */
	private $result;
	private $mapIdsCopiedTasks = [];

	public function __construct($executiveUserId, array $taskIdsToCopy)
	{
		$this->executiveUserId = $executiveUserId;
		$this->taskIdsToCopy = $taskIdsToCopy;

		$this->result = new Result();
	}

	public function setTargetGroup($targetGroupId)
	{
		$this->targetGroupId = $targetGroupId;
	}

	/**
	 * In case you copy the task in several stages and the managers are initialized several times,
	 * in order to copy the stages of a kanban group, you need to forward the relation
	 * of identifiers that will be used by the stage copier.
	 * @param array $mapIdsCopiedStages Map ids.
	 */
	public function setMapIdsCopiedStages(array $mapIdsCopiedStages): void
	{
		$this->mapIdsCopiedStages = $mapIdsCopiedStages;
	}

	/**
	 * Setting the start date of a project to update deadline in tasks.
	 *
	 * @param array $projectTerm ["start_point" => "", "end_point" => ""].
	 */
	public function setProjectTerm(array $projectTerm)
	{
		$this->projectTerm = $projectTerm;
	}

	public function markChecklist($marker)
	{
		$this->markerChecklist = (bool) $marker;
	}

	public function markComment($marker)
	{
		$this->markerComment = (bool) $marker;
	}

	public function startCopy()
	{
		$containerCollection = $this->getContainerCollection($this->taskIdsToCopy);

		$taskImplementer = $this->getTaskImplementer();
		$taskCopier = $this->getTaskCopier($taskImplementer);

		$taskCopier->addEntityToCopy($this->getStageCopier($taskImplementer));

		if ($this->markerChecklist)
		{
			$checklistImplementer = $this->getChecklistImplementer();
			$taskCopier->addEntityToCopy($this->getChecklistCopier($checklistImplementer));
		}

		$this->result = $taskCopier->copy($containerCollection);
		$this->mapIdsCopiedTasks = $taskCopier->getMapIdsCopiedEntity();

		return $this->result;
	}

	public function copyKanbanStages($groupId, $copiedGroupId)
	{
		if ($result = StagesTable::copyView($groupId, $copiedGroupId))
		{
			$this->mapIdsCopiedStages = $result;
		}
		return $this->mapIdsCopiedStages;
	}

	public function copyGroupRobots($groupId, $copiedGroupId)
	{
		if (Loader::includeModule("bizproc"))
		{
			$projectDocumentType = TaskDocumentType::resolveProjectTaskType($groupId); // todo scrum project
			$currentDocumentType = ["tasks", TaskDocumentType::class, $projectDocumentType];
			$bizprocHelper = new BizprocHelper($currentDocumentType);
			$newDocumentType = ["tasks", TaskDocumentType::class,
				TaskDocumentType::resolveProjectTaskType($copiedGroupId)];
			$templateIdsToCopy = $bizprocHelper->getWorkflowTemplateIds();
			$triggerIds = $bizprocHelper->getTriggerIds();

			$triggerImplementer = $this->getTriggerImplementer($newDocumentType, $this->mapIdsCopiedStages);
			$triggerCopier = $this->getTriggerCopier($triggerImplementer);
			$triggerCopier->copy($this->getContainerCollection($triggerIds));

			$robotsImplementer = $this->getRobotsImplementer($newDocumentType, $this->mapIdsCopiedStages);
			$robotsCopier = $this->getRobotsCopier($robotsImplementer);
			$robotsCopier->copy($this->getContainerCollection($templateIdsToCopy));
		}
	}

	public function getMapIdsCopiedTasks()
	{
		return $this->mapIdsCopiedTasks;
	}

	private function getContainerCollection(array $idsToCopy)
	{
		$containerCollection = new ContainerCollection();

		foreach ($idsToCopy as $id)
		{
			$containerCollection[] = new Container($id);
		}

		return $containerCollection;
	}

	private function getTaskImplementer()
	{
		global $USER_FIELD_MANAGER;
		$taskImplementer = new TaskImplementer($this->executiveUserId);
		$taskImplementer->setUserFieldManager($USER_FIELD_MANAGER);
		$taskImplementer->setExecutiveUserId($this->executiveUserId);
		$taskImplementer->setTargetGroupId($this->targetGroupId);
		$taskImplementer->setProjectTerm($this->projectTerm);
		$taskImplementer->setTemplateCopier($this->getTemplateCopier());
		$taskImplementer->setParamsCopier($this->getParamsCopier());

		if ($this->markerComment && Loader::includeModule("forum"))
		{
			$taskImplementer->setCommentCopier($this->getCommentCopier());
		}

		return $taskImplementer;
	}

	private function getStageCopier($taskImplementer)
	{
		return new StageCopier(new StageImplementer($taskImplementer, $this->mapIdsCopiedStages));
	}

	private function getChecklistImplementer()
	{
		return new CheckListImplementer();
	}

	private function getTaskCopier(TaskImplementer $taskImplementer)
	{
		return new TaskCopier($taskImplementer);
	}

	private function getChecklistCopier($checklistImplementer)
	{
		return new CheckListCopier($checklistImplementer, $this->executiveUserId);
	}

	private function getCommentCopier()
	{
		return new EntityCopier($this->getCommentImplementer());
	}

	private function getTemplateCopier(): Template
	{
		return new Template($this->getTemplateImplementer());
	}

	private function getParamsCopier(): EntityCopier
	{
		return new EntityCopier(new TaskParams());
	}

	private function getCommentImplementer()
	{
		global $USER_FIELD_MANAGER;
		$commentImplementer = new CommentImplementer();
		$commentImplementer->setUserFieldManager($USER_FIELD_MANAGER);
		$commentImplementer->setExecutiveUserId($this->executiveUserId);

		return $commentImplementer;
	}

	private function getTemplateImplementer(): TemplateImplementer
	{
		global $USER_FIELD_MANAGER;

		$templateImplementer = new TemplateImplementer();

		$templateImplementer->setUserFieldManager($USER_FIELD_MANAGER);
		$templateImplementer->setExecutiveUserId($this->executiveUserId);

		return $templateImplementer;
	}

	private function getRobotsImplementer(array $documentType, array $mapIdsCopiedStages)
	{
		return new Robots($documentType, $mapIdsCopiedStages);
	}

	private function getRobotsCopier($robotsImplementer)
	{
		return new RobotsCopier($robotsImplementer);
	}

	private function getTriggerImplementer(array $documentType, array $mapIdsCopiedStages)
	{
		return new TriggerImplementer($documentType, $mapIdsCopiedStages);
	}

	private function getTriggerCopier($triggerImplementer)
	{
		return new TriggerCopier($triggerImplementer);
	}
}