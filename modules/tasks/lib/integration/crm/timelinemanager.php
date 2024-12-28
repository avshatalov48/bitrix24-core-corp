<?php

namespace Bitrix\Tasks\Integration\CRM;

use Bitrix\Forum\EO_Message;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\EventsController;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskAccompliceAdded;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskAdded;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskAllCommentViewed;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskAuditorAdded;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskBindingsUpdated;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskChecklistAdded;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskChecklistChanged;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskCommentAdded;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskCommentDeleted;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskCompleted;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskDatePlanUpdated;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskDeadLineChanged;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskDeleted;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskDescriptionChanged;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskDisapproved;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskExpired;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskFilesUpdated;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskGroupChanged;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskPingSent;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskPriorityChanged;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskRenew;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskResponsibleChanged;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskResultAdded;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskStatusChanged;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskTitleUpdated;
use Bitrix\Tasks\Integration\CRM\Timeline\Event\OnTaskViewed;
use Bitrix\Tasks\Integration\CRM\Timeline\TaskRepository;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\TaskObject;

class TimeLineManager
{
	use StorageTrait;

	private int $userId;
	private int $taskId;
	private bool $isAvailable = true;
	private TaskRepository $taskRepository;
	private EventsController $eventsController;

	public function __construct(int $taskId, int $userId = 0)
	{
		if (!Loader::includeModule('crm'))
		{
			$this->isAvailable = false;
			return;
		}

		$this->userId = $userId;
		$this->taskId = $taskId;
		$this->taskRepository = new TaskRepository($this->taskId, $this->userId);
		$this->eventsController = EventsController::getInstance();
	}

	public function setUserId(int $userId): static
	{
		$this->userId = $userId;
		return $this;
	}

	public function onTaskCreated(bool $restored = false): self
	{
		if (!$this->isAvailable())
		{
			return $this;
		}

		if ($this->taskRepository->getBindings()->isEmpty())
		{
			return $this;
		}

		if ($this->taskRepository->getTask() === null)
		{
			return $this;
		}

		$this->eventsController->addEvent(new OnTaskAdded($this->taskRepository->getTask(), $this->userId, $restored));

		if ($this->taskRepository->getTask()->isExpired())
		{
			return $this->onTaskExpired();
		}

		return $this;
	}

	public function onTaskExpired(): self
	{
		if (!$this->isAvailable())
		{
			return $this;
		}

		$this->eventsController->addEvent(new OnTaskExpired($this->taskRepository->getTask(), $this->userId));

		return $this;
	}

	public function onTaskUpdated(TaskObject $taskBeforeUpdate): self
	{
		if (!$this->isAvailable())
		{
			return $this;
		}

		if ($this->taskRepository->getTask() === null)
		{
			return $this;
		}

		if (empty($taskBeforeUpdate->getCachedCrmFields()) && empty($this->taskRepository->getTask()->getCrmFields()))
		{
			return $this;
		}

		if (!empty($taskBeforeUpdate->getCachedCrmFields()) && empty($this->taskRepository->getTask()->getCrmFields()))
		{
			$this->eventsController->addEvent(new OnTaskBindingsUpdated($this->taskRepository->getTask(), $this->userId));
			return $this;
		}

		// deadline changed
		$currDeadLine = $this->taskRepository->getTask()->getDeadline() ? $this->taskRepository->getTask()->getDeadline()->toString() : null;
		$prevDeadLine = $taskBeforeUpdate->getDeadline() ? $taskBeforeUpdate->getDeadline()->toString() : null;
		if ($currDeadLine !== $prevDeadLine)
		{
			$this->eventsController->addEvent(new OnTaskDeadLineChanged($this->taskRepository->getTask(), $taskBeforeUpdate, $this->userId));
		}

		// status changed
		$currentStatus = (int)$this->taskRepository->getTask()->getRealStatus();
		$previousStatus = (int)$taskBeforeUpdate->getStatus();
		if (
			$currentStatus !== $previousStatus
			&& $currentStatus > 0
			&& $previousStatus > 0
		)
		{
			$this->eventsController->addEvent(
				new OnTaskStatusChanged($this->taskRepository->getTask(), $currentStatus, $previousStatus, $this->userId)
			);
		}

		if ($currentStatus !== $previousStatus && (int)$currentStatus === Status::COMPLETED)
		{
			$this->eventsController->addEvent(new OnTaskCompleted($this->taskRepository->getTask(), $this->userId));
		}

		if ($currentStatus !== $previousStatus && (int)$previousStatus === Status::COMPLETED)
		{
			$this->eventsController->addEvent(new OnTaskRenew($this->taskRepository->getTask(), $this->userId));
		}

		// task disapproved
		if (
			(int)$previousStatus === Status::SUPPOSEDLY_COMPLETED
			&& (int)$currentStatus === Status::PENDING
		)
		{
			$this->eventsController->addEvent(
				new OnTaskDisapproved($this->taskRepository->getTask(), $currentStatus, $previousStatus, $this->userId)
			);
		}

		//responsible changed
		$currentResponsibleId = $this->taskRepository->getTask()->getResponsibleId();
		$previousResponsibleId = $taskBeforeUpdate->getResponsibleId();
		if ($currentResponsibleId !== $previousResponsibleId)
		{
			$this->eventsController->addEvent(new OnTaskResponsibleChanged($this->taskRepository->getTask(), $this->userId));
		}

		//accomplice added
		$currentAccompliceMembersIds = $this->taskRepository->getTask()->getAccompliceMembersIds();
		$previousAccompliceMembersIds = $taskBeforeUpdate->getAccompliceMembersIds();
		if ($this->isMemberAdded($currentAccompliceMembersIds, $previousAccompliceMembersIds))
		{
			$this->eventsController->addEvent(new OnTaskAccompliceAdded($this->taskRepository->getTask(), $this->userId));
		}

		//auditor added
		$currentAuditorMembersIds = $this->taskRepository->getTask()->getAuditorMembersIds();
		$previousAuditorMembersIds = $taskBeforeUpdate->getAuditorMembersIds();
		if ($this->isMemberAdded($currentAuditorMembersIds, $previousAuditorMembersIds))
		{
			$this->eventsController->addEvent(new OnTaskAuditorAdded($this->taskRepository->getTask(), $this->userId));
		}

		//added to project
		$currentGroupId = $this->taskRepository->getTask()->getGroupId();
		$previousGroupId = $taskBeforeUpdate->getGroupId();
		if ($currentGroupId !== $previousGroupId && $currentGroupId !== 0)
		{
			$this->eventsController->addEvent(new OnTaskGroupChanged($this->taskRepository->getTask(), $this->userId));
		}

		// description changed
		if ($this->taskRepository->getTask()->getDescription() !== $taskBeforeUpdate->getDescription())
		{
			$this->eventsController->addEvent(new OnTaskDescriptionChanged($this->taskRepository->getTask(), $this->userId));
		}

		if ($this->isArrayFieldChanged($this->taskRepository->getTask()->getFileFields(), $taskBeforeUpdate->getFileFields()))
		{
			$this->eventsController->addEvent(new OnTaskFilesUpdated($this->taskRepository->getTask(), $this->userId));
		}

		if ($this->isArrayFieldChanged($taskBeforeUpdate->getCachedCrmFields(), $this->taskRepository->getTask()->getCrmFields()))
		{
			$this->eventsController->addEvent(new OnTaskBindingsUpdated($this->taskRepository->getTask(), $this->userId));
		}

		if ($this->taskRepository->getTask()->getTitle() !== $taskBeforeUpdate->getTitle())
		{
			$this->eventsController->addEvent(new OnTaskTitleUpdated($this->taskRepository->getTask(), $this->userId));
		}

		if (
			$this->isDateTimeFieldChanged($this->taskRepository->getTask()->getStartDatePlan(), $taskBeforeUpdate->getStartDatePlan())
			|| $this->isDateTimeFieldChanged($this->taskRepository->getTask()->getEndDatePlan(), $taskBeforeUpdate->getEndDatePlan())
		)
		{
			$this->eventsController->addEvent(new OnTaskDatePlanUpdated($this->taskRepository->getTask(), $this->userId));
		}

		if ((int)$this->taskRepository->getTask()->getPriority() !== (int)$taskBeforeUpdate->getPriority())
		{
			$this->eventsController->addEvent(new OnTaskPriorityChanged($this->taskRepository->getTask(), $this->userId));
		}

		return $this;
	}

	public function onTaskDeleted(): self
	{
		if (!$this->isAvailable())
		{
			return $this;
		}

		$this->eventsController->addEvent(new OnTaskDeleted($this->taskRepository->getTask(), $this->userId));

		return $this;
	}

	public function onTaskChecklistAdded(): self
	{
		if (!$this->isAvailable())
		{
			return $this;
		}

		$this->eventsController->addEvent(new OnTaskChecklistAdded($this->taskRepository->getTask(), $this->userId));

		return $this;
	}

	public function onTaskChecklistChanged(): self
	{
		if (!$this->isAvailable())
		{
			return $this;
		}

		$this->eventsController->addEvent(new OnTaskChecklistChanged($this->taskRepository->getTask(), $this->userId));

		return $this;
	}

	public function onTaskResultAdded(): self
	{
		if (!$this->isAvailable())
		{
			return $this;
		}

		$this->eventsController->addEvent(new OnTaskResultAdded($this->taskRepository->getTask(), $this->userId));

		return $this;
	}

	public function onTaskViewed(): self
	{
		if (!$this->isAvailable())
		{
			return $this;
		}

		if ($this->taskRepository->getTask() === null)
		{
			return $this;
		}

		if (
			(int)$this->taskRepository->getTask()->getStatus() !== Status::COMPLETED
			&& $this->userId !== $this->taskRepository->getTask()->getCreatedBy()
			&& $this->userId === $this->taskRepository->getTask()->getResponsibleId()
		)
		{
			$this->eventsController->addEvent(new OnTaskViewed($this->taskRepository->getTask(), $this->userId));
		}

		return $this;
	}

	public function onTaskPingSent(): self
	{
		if (!$this->isAvailable())
		{
			return $this;
		}

		$this->eventsController->addEvent(new OnTaskPingSent($this->taskRepository->getTask(), $this->userId));

		return $this;
	}

	public function onTaskCommentAdd(?EO_Message $message): self
	{
		if (!$this->isAvailable())
		{
			return $this;
		}

		if (
			is_null($message)
			|| is_null($this->taskRepository->getTask())
			|| ResultTable::isResult($message->getId(), $this->taskRepository->getTask()?->getId())
		)
		{
			return $this;
		}

		$this->eventsController->addEvent(
			new OnTaskCommentAdded($this->taskRepository->getTask(),
				$this->userId,
				$message->getId(),
				$message->getPostDate(),
				$this->userId,
			)
		);

		return $this;
	}

	public function onTaskAllCommentViewed(): self
	{
		if (!$this->isAvailable())
		{
			return $this;
		}

		if ($this->taskRepository->getTask() === null)
		{
			return $this;
		}

		if (empty($this->taskRepository->getTask()->getCrmFields()))
		{
			return $this;
		}

		$this->eventsController->addEvent(new OnTaskAllCommentViewed($this->taskRepository->getTask(), $this->userId));

		return $this;
	}

	public function onTaskCommentDeleted(array $fileIds = []): self
	{
		if (!$this->isAvailable())
		{
			return $this;
		}

		$this->eventsController->addEvent(new OnTaskCommentDeleted($this->taskRepository->getTask(), $this->userId, $fileIds));

		return $this;
	}

	public function onTaskFilesUpdated(): static
	{
		if (!$this->isAvailable())
		{
			return $this;
		}

		if ($this->taskRepository->getTask() === null)
		{
			return $this;
		}

		$this->eventsController->addEvent(new OnTaskFilesUpdated($this->taskRepository->getTask(), $this->userId));

		return $this;
	}

	public function save(): void
	{
		if (!$this->isAvailable())
		{
			return;
		}

		if (!$this->taskRepository->getTask())
		{
			return;
		}

		$this->eventsController->pushEvents($this->taskRepository);
	}


	private function isAvailable(): bool
	{
		return $this->isAvailable === true;
	}

	public static function isNewIntegrationEnabled(): bool
	{
		return true;
	}

	private function isMemberAdded(array $currentMembers, array $previousMembers): bool
	{
		foreach ($currentMembers as $currentMember)
		{
			if (!in_array($currentMember, $previousMembers, true))
			{
				return true;
			}
		}

		return false;
	}

	private function isArrayFieldChanged(array $a, array $b): bool
	{
		return (
			!empty(array_diff($a, $b))
			|| !empty(array_diff($b, $a))
		);
	}

	private function isDateTimeFieldChanged(?DateTime $a, ?DateTime $b): bool
	{
		if (is_null($a) && is_null($b))
		{
			return false;
		}

		if (is_null($a) && !is_null($b))
		{
			return true;
		}

		if (!is_null($a) && is_null($b))
		{
			return true;
		}

		return ($a->toString() !== $b->toString());
	}
}