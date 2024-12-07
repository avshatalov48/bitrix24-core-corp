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
use Bitrix\Tasks\Copy\Implement\TaskParams;
use Bitrix\Tasks\Copy\Implement\Template as TemplateImplementer;
use Bitrix\Tasks\Copy\Stage as StageCopier;
use Bitrix\Tasks\Copy\Task as TaskCopier;
use Bitrix\Tasks\Integration\Bizproc\Document\Task as TaskDocumentType;
use Bitrix\Tasks\Kanban\StagesTable;

class TaskManager
{
	protected $executiveUserId;
	protected $taskIdsToCopy = [];
	protected $targetGroupId = 0;

	protected $mapIdsCopiedStages = [];

	protected $projectTerm = [];

	protected $markerChecklist = true;
	protected $markerComment = true;

	/**
	 * @var Result
	 */
	protected $result;
	protected $mapIdsCopiedTasks = [];

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

	protected function getContainerCollection(array $idsToCopy)
	{
		$containerCollection = new ContainerCollection();

		foreach ($idsToCopy as $id)
		{
			$containerCollection[] = new Container($id);
		}

		return $containerCollection;
	}

	protected function getTaskImplementer()
	{
		global $USER_FIELD_MANAGER;
		$class = $this->getImplementerClass();
		$taskImplementer = new $class($this->executiveUserId);
		$taskImplementer->setUserFieldManager($USER_FIELD_MANAGER);
		$taskImplementer->setExecutiveUserId($this->executiveUserId);
		$taskImplementer->setTargetGroupId($this->targetGroupId);
		$taskImplementer->setProjectTerm($this->projectTerm);
		$taskImplementer->setTemplateCopier($this->getTemplateCopier());
		$taskImplementer->setParamsCopier($this->getParamsCopier());

		if ($this->markerComment && Loader::includeModule("forum"))
		{
			$taskImplementer->setTopicCopier($this->getTopicCopier());
			$taskImplementer->setCommentCopier($this->getCommentCopier());
		}

		return $taskImplementer;
	}

	protected function getStageCopier($taskImplementer)
	{
		return new StageCopier(new StageImplementer($taskImplementer, $this->mapIdsCopiedStages));
	}

	protected function getChecklistImplementer()
	{
		return new CheckListImplementer();
	}

	protected function getTaskCopier(Implement\Task $taskImplementer)
	{
		return new TaskCopier($taskImplementer);
	}

	protected function getChecklistCopier($checklistImplementer)
	{
		return new CheckListCopier($checklistImplementer, $this->executiveUserId);
	}

	protected function getTopicCopier()
	{
		return new EntityCopier($this->getTopicImplementer());
	}

	protected function getCommentCopier()
	{
		return new EntityCopier($this->getCommentImplementer());
	}

	protected function getTemplateCopier(): Template
	{
		return new Template($this->getTemplateImplementer());
	}

	protected function getParamsCopier(): EntityCopier
	{
		return new EntityCopier(new TaskParams());
	}

	protected function getTopicImplementer()
	{
		global $USER_FIELD_MANAGER;

		$topicImplementer = new TopicImplementer();
		$topicImplementer->setUserFieldManager($USER_FIELD_MANAGER);
		$topicImplementer->setExecutiveUserId($this->executiveUserId);

		return $topicImplementer;
	}

	protected function getCommentImplementer()
	{
		global $USER_FIELD_MANAGER;
		$commentImplementer = new CommentImplementer();
		$commentImplementer->setUserFieldManager($USER_FIELD_MANAGER);
		$commentImplementer->setExecutiveUserId($this->executiveUserId);

		return $commentImplementer;
	}

	protected function getTemplateImplementer(): TemplateImplementer
	{
		global $USER_FIELD_MANAGER;

		$templateImplementer = new TemplateImplementer();

		$templateImplementer->setUserFieldManager($USER_FIELD_MANAGER);
		$templateImplementer->setExecutiveUserId($this->executiveUserId);

		return $templateImplementer;
	}

	protected function getRobotsImplementer(array $documentType, array $mapIdsCopiedStages)
	{
		return new Robots($documentType, $mapIdsCopiedStages);
	}

	protected function getRobotsCopier($robotsImplementer)
	{
		return new RobotsCopier($robotsImplementer);
	}

	protected function getTriggerImplementer(array $documentType, array $mapIdsCopiedStages)
	{
		return new TriggerImplementer($documentType, $mapIdsCopiedStages);
	}

	protected function getTriggerCopier($triggerImplementer)
	{
		return new TriggerCopier($triggerImplementer);
	}

	public function getImplementerClass(): string
	{
		return Implement\Task::class;
	}
}