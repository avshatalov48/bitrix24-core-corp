<?
namespace Bitrix\Tasks\Copy\Implement;

use Bitrix\Forum\Copy\Implement\Comment as CommentImplementer;
use Bitrix\Main\Copy\Container;
use Bitrix\Main\Copy\ContainerCollection;
use Bitrix\Main\Copy\EntityCopier;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\Dictionary;
use Bitrix\Tasks\Copy\Task as TaskCopier;
use Bitrix\Tasks\Integration\Forum\Task\Comment;
use Bitrix\Tasks\Util\Type\DateTime as TasksDateTime;

Loc::loadMessages(__FILE__);

/**
 * The layer fow work with tasks.
 *
 * @package Bitrix\Tasks\Copy\Entity
 */
class Task extends Base
{
	const TASK_COPY_ERROR = "TASK_COPY_ERROR";

	const DESC_FORMAT_RAW = \CTaskItem::DESCR_FORMAT_RAW;
	const DESC_FORMAT_HTML = \CTaskItem::DESCR_FORMAT_HTML;
	const DESC_FORMAT_PLAIN_TEXT = \CTaskItem::DESCR_FORMAT_PLAIN_TEXT;

	/**
	 * @var TaskCopier|null
	 */
	private $taskCopier = null;
	/**
	 * @var EntityCopier|null
	 */
	private $topicCopier = null;

	private $targetGroupId;

	protected $executiveUserId;

	private $projectTerm = [];

	protected $ufEntityObject = "TASKS_TASK";
	protected $ufDiskFileField = "UF_TASK_WEBDAV_FILES";

	public function __construct($executiveUserId)
	{
		parent::__construct();

		$this->executiveUserId = $executiveUserId;
	}

	/**
	 * To copy child tasks needs task copier.
	 *
	 * @param TaskCopier $taskCopier Task copier.
	 */
	public function setTaskCopier(TaskCopier $taskCopier): void
	{
		$this->taskCopier = $taskCopier;
	}

	/**
	 * @param EntityCopier|null $topicCopier
	 */
	public function setTopicCopier(EntityCopier $topicCopier): void
	{
		$this->topicCopier = $topicCopier;
	}

	/**
	 * Writes the ID of the target group to copy the task to the specified group.
	 *
	 * @param int $targetGroupId
	 */
	public function setTargetGroupId(int $targetGroupId): void
	{
		$this->targetGroupId = $targetGroupId;
	}

	/**
	 * Setting the start date of a project or a group to update deadline in tasks.
	 *
	 * @param array $projectTerm ["start_point" => "", "end_point" => ""].
	 */
	public function setProjectTerm(array $projectTerm)
	{
		$projectTerm["project"] = $projectTerm["project"] ?: false;
		$projectTerm["old_start_point"] = $projectTerm["old_start_point"] ?: "";
		$projectTerm["start_point"] = $projectTerm["start_point"] ?: "";
		$projectTerm["end_point"] = $projectTerm["end_point"] ?: "";
		$this->projectTerm = $projectTerm;
	}

	/**
	 * Returns CTaskItem object.
	 *
	 * @param integer $taskId Task id.
	 * @return \CTaskItem
	 */
	public function getTaskItemObject($taskId)
	{
		return \CTaskItem::getInstance($taskId, $this->executiveUserId);
	}

	/**
	 * Creates task and return task id.
	 *
	 * @param Container $container
	 * @param array $fields The task fields.
	 * @return bool|int
	 */
	public function add(Container $container, array $fields)
	{
		try
		{
			$task = \CTaskItem::add($fields, $this->executiveUserId);
			return $task->getId();
		}
		catch(\Exception $exception)
		{
			$this->result->addError(new Error("Task hasn't been added", self::TASK_COPY_ERROR));
			return false;
		}
	}

	/**
	 * Updates task.
	 *
	 * @param integer $taskId Task Id.
	 * @param array $fields The task fields.
	 * @return bool
	 */
	public function update($taskId, array $fields)
	{
		try
		{
			$task = $this->getTaskItemObject($taskId);
			$task->update($fields);
			return true;
		}
		catch (\Exception $exception)
		{
			$this->result->addError(new Error("Task hasn't been updated", self::TASK_COPY_ERROR));
			return false;
		}
	}

	/**
	 * Returns query object.
	 *
	 * @param array $order
	 * @param array $filter
	 * @param array $select
	 * @return bool|\CDBResult
	 */
	public function getList($order = [], $filter = [], $select = [])
	{
		try
		{
			return \CTasks::getList($order, $filter, $select, ["USER_ID" => $this->executiveUserId]);
		}
		catch(\Exception $exception)
		{
			$this->result->addError(new Error("Failed to get data to copy task", self::TASK_COPY_ERROR));
			return false;
		}
	}

	/**
	 * Returns task fields.
	 *
	 * @param Container $container
	 * @param int $entityId
	 * @return array $fields
	 */
	public function getFields(Container $container, $entityId)
	{
		try
		{
			$task = $this->getTaskItemObject($entityId);

			$fields = $task->getData(false, ["select" => ["*", "UF_*"]]);

			return (is_array($fields) ? $fields : []);
		}
		catch (\Exception $exception)
		{
			return [];
		}
	}

	/**
	 * Preparing data before creating a new entity.
	 *
	 * @param Container $container
	 * @param array $fields List entity fields.
	 * @return array $fields
	 */
	public function prepareFieldsToCopy(Container $container, array $fields)
	{
		$dictionary = $container->getDictionary();
		$dictionary["FORUM_TOPIC_ID"] = $fields["FORUM_TOPIC_ID"];
		$container->setDictionary($dictionary);

		if ($this->targetGroupId)
		{
			$fields["GROUP_ID"] = $this->targetGroupId;
		}

		if (!empty($container->getParentId()))
		{
			$fields["PARENT_ID"] = $container->getParentId();
		}

		if ($this->projectTerm)
		{
			$isProject = (!empty($this->projectTerm["project"]));

			if ($isProject)
			{
				if (!empty($fields["DEADLINE"]))
				{
					$fields["DEADLINE"] = $this->getProjectDeadline($fields["DEADLINE"], $fields["CREATED_DATE"]);
				}
				if (!empty($fields["START_DATE_PLAN"]))
				{
					$fields["START_DATE_PLAN"] = $this->getProjectDatePlan(
						$fields["START_DATE_PLAN"], $fields["CREATED_DATE"]);
				}
				if (!empty($fields["END_DATE_PLAN"]))
				{
					$fields["END_DATE_PLAN"] = $this->getProjectDatePlan(
						$fields["END_DATE_PLAN"], $fields["CREATED_DATE"]);
				}
			}
			else
			{
				if (!empty($fields["DEADLINE"]))
				{
					$fields["DEADLINE"] = $this->getGroupDeadline($fields["DEADLINE"], $fields["CREATED_DATE"]);
				}
				if (!empty($fields["START_DATE_PLAN"]))
				{
					$fields["START_DATE_PLAN"] = $this->getGroupDatePlan(
						$fields["START_DATE_PLAN"], $fields["CREATED_DATE"]);
				}
				if (!empty($fields["END_DATE_PLAN"]))
				{
					$fields["END_DATE_PLAN"] = $this->getGroupDatePlan(
						$fields["END_DATE_PLAN"], $fields["CREATED_DATE"]);
				}
			}
		}

		$fields = $this->cleanDataToCopy($fields);

		return $fields;
	}

	/**
	 * Starts copying children entities.
	 *
	 * @param Container $container
	 * @param int $entityId Task id.
	 * @param int $copiedEntityId Copied task id.
	 * @return Result
	 */
	public function copyChildren(Container $container, $entityId, $copiedEntityId)
	{
		$this->copyUfFields($entityId, $copiedEntityId, $this->ufEntityObject);

		$results = [];

		$results[] = $this->copyChildTasks($entityId, $copiedEntityId);

		$results[] = $this->copyComments($container, $entityId, $copiedEntityId);

		return $this->getResult($results);
	}

	private function copyChildTasks($taskId, $copiedTaskId)
	{
		if (!$this->taskCopier)
		{
			return new Result();
		}

		$filter = ["PARENT_ID" => $taskId];
		$groupId = $this->getGroupIdByTaskId($taskId);
		if ($groupId)
		{
			$filter["GROUP_ID"] = $groupId;
		}

		$containerCollection = new ContainerCollection();

		$queryObject = $this->getList([], $filter, ["ID"]);
		while ($task = $queryObject->fetch())
		{
			$container = new Container($task["ID"]);
			$container->setParentId($copiedTaskId);
			$containerCollection[] = $container;
		}

		if (!$containerCollection->isEmpty())
		{
			return $this->taskCopier->copy($containerCollection);
		}

		return new Result();
	}

	/**
	 * Returns task description.
	 *
	 * @param integer $taskId Task id.
	 * @param integer $format Description format.
	 * @return array
	 */
	protected function getText($taskId, $format = self::DESC_FORMAT_RAW)
	{
		try
		{
			$task = $this->getTaskItemObject($taskId);
			$description = $task->getDescription($format);

			return (is_string($description) ? ["DESCRIPTION", $description] : ["DESCRIPTION", ""]);
		}
		catch(\Exception $exception)
		{
			return ["DESCRIPTION", ""];
		}
	}

	private function cleanDataToCopy($fields)
	{
		$fields = $this->cleanPrimary($fields);
		$fields = $this->cleanDate($fields);
		$fields = $this->cleanStatus($fields);
		$fields = $this->cleanForumData($fields);
		$fields = $this->cleanSystemUfData($fields);
		return $fields;
	}

	private function cleanPrimary($fields)
	{
		unset($fields["ID"]);
		unset($fields["GUID"]);
		return $fields;
	}

	private function cleanDate($fields)
	{
		unset($fields["CREATED_DATE"]);
		unset($fields["CHANGED_DATE"]);
		unset($fields["VIEWED_DATE"]);
		return $fields;
	}

	private function cleanStatus($fields)
	{
		unset($fields["STATUS"]);
		unset($fields["STATUS_CHANGED_BY"]);
		unset($fields["STATUS_CHANGED_DATE"]);
		return $fields;
	}

	private function cleanForumData($fields)
	{
		unset($fields["FORUM_TOPIC_ID"]);
		return $fields;
	}

	private function cleanSystemUfData(array $fields): array
	{
		unset($fields["UF_TASK_WEBDAV_FILES"]);
		return $fields;
	}

	private function copyComments(Container $container, int $taskId, int $copiedTaskId)
	{
		if (!$this->topicCopier)
		{
			return new Result();
		}

		$dictionary = $container->getDictionary();
		if (empty($dictionary["FORUM_TOPIC_ID"]))
		{
			return new Result();
		}

		$topicId = $dictionary["FORUM_TOPIC_ID"];
		$containerCollection = new ContainerCollection();
		$container = new Container($topicId);
		$dictionary = new Dictionary(["XML_ID" => "TASK_".$copiedTaskId]);
		$container->setDictionary($dictionary);
		$containerCollection[] = $container;

		if (!$containerCollection->isEmpty())
		{
			$result = $this->topicCopier->copy($containerCollection);

			if (!$result->getErrors())
			{
				$mapIdsCopiedTopics = $this->topicCopier->getMapIdsCopiedEntity();
				if ($taskId && array_key_exists($topicId, $mapIdsCopiedTopics))
				{
					$copiedTopicId = $mapIdsCopiedTopics[$topicId];
					$this->update($copiedTaskId, ["FORUM_TOPIC_ID" => $copiedTopicId]);

					$this->addSocnetLog($copiedTaskId, $copiedTopicId, $result);
				}
			}

			return $result;
		}

		return new Result();
	}

	private function getProjectDeadline($currentDeadline, $taskCreatedDate)
	{
		$deadline = $this->getRecountedProjectDeadline($currentDeadline, $taskCreatedDate);

		$deadline = ($this->isCorrectProjectDeadline($deadline) ? $deadline : "");

		return $deadline;
	}

	private function getGroupDeadline($currentDeadline, $taskCreatedDate)
	{
		$deadline = $this->getRecountedGroupDeadline($currentDeadline, $taskCreatedDate);

		return $deadline;
	}

	private function getProjectDatePlan($currentDatePlan, $taskCreatedDate)
	{
		try
		{
			$datePlan = $this->getRecountedProjectDeadline($currentDatePlan, $taskCreatedDate);

			$startPoint = $this->projectTerm["start_point"];
			$endPoint = $this->projectTerm["end_point"];

			$projectStartDate = TasksDateTime::createFrom($startPoint);
			$projectFinishDate = TasksDateTime::createFrom($endPoint);

			$datePlanTime = TasksDateTime::createFrom($datePlan);
			$startPointTime = TasksDateTime::createFrom($startPoint);
			if ($datePlanTime->getTimestamp() < $startPointTime->getTimestamp())
			{
				$phpDateTimeFormat = DateTime::convertFormatToPhp(FORMAT_DATETIME);
				$datePlan = $startPointTime->format($phpDateTimeFormat);
			}

			if ($projectFinishDate)
			{
				$projectFinishDate->addSecond(86399);

				$datePlanTime = TasksDateTime::createFrom($datePlan);
				$endPointTime = TasksDateTime::createFrom($endPoint);
				$endPointTime->add("PT86399S");
				if ($datePlanTime->getTimestamp() > $endPointTime->getTimestamp())
				{
					$phpDateFormat = DateTime::convertFormatToPhp(FORMAT_DATE);
					$datePlan = $endPointTime->format($phpDateFormat);
				}
			}

			$datePlanTime = TasksDateTime::createFrom($datePlan);
			if ($datePlanTime && !$datePlanTime->checkInRange($projectStartDate, $projectFinishDate))
			{
				return "";
			}
			else
			{
				return $datePlan;
			}
		}
		catch (\Exception $exception)
		{
			$this->result->addError(new Error($exception->getMessage(), self::TASK_COPY_ERROR));
			return "";
		}
	}

	private function getGroupDatePlan($currentDatePlan, $taskCreatedDate)
	{
		return $this->getRecountedGroupDeadline($currentDatePlan, $taskCreatedDate);
	}

	private function getRecountedProjectDeadline($currentFieldDate, $taskCreatedDate)
	{
		try
		{
			$projectTerm = $this->projectTerm;

			$startPoint = $projectTerm["start_point"];
			$startPointTime = TasksDateTime::createFrom($startPoint);

			$oldStartPoint = ($projectTerm["old_start_point"] ? $projectTerm["old_start_point"] : $taskCreatedDate);
			$oldStartPointTime = TasksDateTime::createFrom($oldStartPoint);

			$currentFieldDateTime = TasksDateTime::createFrom($currentFieldDate);

			$startPointTime->add("PT".($currentFieldDateTime->getTimestamp()-$oldStartPointTime->getTimestamp())."S");

			$phpDateTimeFormat = DateTime::convertFormatToPhp(FORMAT_DATETIME);
			$deadline = $startPointTime->format($phpDateTimeFormat);

			$endPoint = $projectTerm["end_point"];
			if ($endPoint)
			{
				$deadlineTime = TasksDateTime::createFrom($deadline);
				$endPointTime = TasksDateTime::createFrom($endPoint);
				$endPointTime->add("PT86399S");
				if ($deadlineTime->getTimestamp() > $endPointTime->getTimestamp())
				{
					$phpDateFormat = DateTime::convertFormatToPhp(FORMAT_DATE);
					$deadline = $endPointTime->format($phpDateFormat);
				}
			}

			return $deadline;
		}
		catch (\Exception $exception)
		{
			$this->result->addError(new Error($exception->getMessage(), self::TASK_COPY_ERROR));
			return "";
		}
	}

	private function isCorrectProjectDeadline($deadline)
	{
		try
		{
			$projectStartDate = TasksDateTime::createFrom($this->projectTerm["start_point"]);
			$projectFinishDate = TasksDateTime::createFrom($this->projectTerm["end_point"]);
			if ($projectFinishDate)
			{
				$projectFinishDate->addSecond(86399);
			}

			$deadlineTime = TasksDateTime::createFrom($deadline);

			return ($deadlineTime && $deadlineTime->checkInRange($projectStartDate, $projectFinishDate));
		}
		catch (\Exception $exception)
		{
			$this->result->addError(new Error($exception->getMessage(), self::TASK_COPY_ERROR));
			return false;
		}
	}

	private function getRecountedGroupDeadline($currentDeadline, $taskCreatedDate)
	{
		try
		{
			$startPoint = $this->projectTerm["start_point"];
			$startPointTime = TasksDateTime::createFrom($startPoint);

			$createdDate = TasksDateTime::createFrom($taskCreatedDate);
			$currentDeadlineTime = TasksDateTime::createFrom($currentDeadline);
			$startPointTime->add("PT".($currentDeadlineTime->getTimestamp()-$createdDate->getTimestamp())."S");

			$phpDateTimeFormat = DateTime::convertFormatToPhp(FORMAT_DATETIME);
			return $startPointTime->format($phpDateTimeFormat);
		}
		catch (\Exception $exception)
		{
			$this->result->addError(new Error($exception->getMessage(), self::TASK_COPY_ERROR));
			return "";
		}
	}

	private function getGroupIdByTaskId($taskId)
	{
		$queryObject = $this->getList([], ["ID" => $taskId], ["GROUP_ID"]);
		if ($task = $queryObject->fetch())
		{
			return $task["GROUP_ID"];
		}

		return null;
	}

	private function addSocnetLog($copiedTaskId, $copiedTopicId, Result $result): void
	{
		if (!Loader::includeModule("forum"))
		{
			return;
		}

		$mapIdsCopiedComments = $this->topicCopier->getMapIdsByImplementer(
			CommentImplementer::class, $result->getData());
		foreach ($mapIdsCopiedComments as $copiedMessageId)
		{
			if ($copiedMessageId)
			{
				$message = \CForumMessage::getByIDEx($copiedMessageId);
				if ($message["NEW_TOPIC"] != "Y")
				{
					Comment::onAfterAdd("TK", $copiedTaskId, [
						"TOPIC_ID" => $copiedTopicId,
						"MESSAGE_ID" => $copiedMessageId
					]);
				}
			}
		}
	}
}