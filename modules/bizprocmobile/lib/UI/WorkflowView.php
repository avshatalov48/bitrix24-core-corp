<?php

namespace Bitrix\BizprocMobile\UI;

use Bitrix\Bizproc\Workflow\Entity\WorkflowUserCommentTable;
use Bitrix\Bizproc\WorkflowInstanceTable;
use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;

class WorkflowView implements \JsonSerializable
{
	private array $workflow;
	private array $tasks;
	private int $userId;
	private bool $workflowIsCompleted;

	public function __construct(array $workflow, int $userId)
	{
		$this->workflow = $workflow;
		$this->userId = $userId;

		$this->tasks = \CBPViewHelper::getWorkflowTasks($workflow['ID'], true, true);

		$this->workflowIsCompleted = !WorkflowInstanceTable::exists($this->workflow['ID']);
		$this->newCommentsCounter = $this->getNewCommentCounter();
	}

	public function getFacesIds(): array
	{
		return array_merge(
			$this->getCompletedTaskUserIds(),
			$this->getRunningTaskUserIds(),
			$this->getDoneTaskUserIds(),
		);
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->workflow['ID'],
			'data' => $this->getWorkflow(),
		];
	}

	private function getWorkflow(): array
	{
		$myTasks = $this->getMyTasks();

		return [
			'id' => $this->workflow['ID'],
			'typeName' => $this->getTypeName(),
			'itemName' => $this->getName($myTasks),
			'itemTime' => $this->getTime($myTasks),
			'statusText' => $this->workflow['STATE_INFO']['TITLE'] ?? '',
			'faces' => $this->getFaces(),
			'tasks' => $myTasks,
			'authorId' => $this->workflow['STARTED_USER_INFO']['ID'],
			'newCommentsCounter' => $this->newCommentsCounter,
			'useWorkflowCounter' => class_exists('\Bitrix\Bizproc\Workflow\WorkflowUserCounters'),
		];
	}

	private function getName(array $myTasks): mixed
	{
		if ($myTasks)
		{
			return current($myTasks)['name'];
		}

		if ($this->isWorkflowAuthorView())
		{
			return $this->workflow['DOCUMENT_INFO']['NAME'] ?? '';
		}

		foreach (['RUNNING', 'COMPLETED'] as $taskState)
		{
			foreach ($this->tasks[$taskState] as $task)
			{
				if (in_array($this->userId, array_column($task['USERS'], 'USER_ID')))
				{
					return $task['~NAME'];
				}
			}
		}

		return $this->workflow['DOCUMENT_INFO']['NAME'] ?? '';
	}

	private function getTime(array $myTasks): ?string
	{
		if ($myTasks)
		{
			return $this->getDateTimeTimestamp(current($myTasks)['createdDate'] ?? null);
		}

		if ($this->isWorkflowAuthorView())
		{
			return $this->getDateTimeTimestamp($this->workflow['STARTED'] ?? null);
		}

		foreach (['RUNNING', 'COMPLETED'] as $taskState)
		{
			foreach ($this->tasks[$taskState] as $task)
			{
				foreach ($task['USERS'] as $taskUser)
				{
					if ((int)$taskUser['USER_ID'] === $this->userId)
					{
						return $this->getDateTimeTimestamp($taskUser['DATE_UPDATE'] ?? null);
					}
				}
			}
		}

		return $this->getDateTimeTimestamp($this->workflow['STARTED'] ?? null);
	}

	private function getFaces(): array
	{
		$completedTask = $this->getCompletedTask();
		$doneTask = $this->getDoneTask();

		return [
			'author' => $this->workflow['STARTED_USER_INFO']['ID'],
			'completedSuccess' => \CBPTaskStatus::isSuccess($completedTask['STATUS'] ?? 0),
			'completed' => $this->getCompletedTaskUserIds(),
			'running' => $this->getRunningTaskUserIds(),
			'done' => $this->getDoneTaskUserIds(),
			'doneSuccess' => \CBPTaskStatus::isSuccess($doneTask['STATUS'] ?? 0),
			'workflowIsCompleted' => $this->workflowIsCompleted,
			'completedTaskCount' => count($this->tasks['COMPLETED']),
			'time' => $this->calculateFacesTime(),
		];
	}

	private function calculateFacesTime(): array
	{
		$authorDuration = $this->workflow['META']['START_DURATION'] ?? null;

		$startWorkflowTimestamp = $this->getDateTimeTimestamp($this->workflow['STARTED']);
		$finishWorkflowTimestamp = (
			$this->workflowIsCompleted
				? $this->getDateTimeTimestamp($this->workflow['MODIFIED'])
				: (new DateTime())->getTimestamp()
		);

		$completedTasks = $this->tasks['COMPLETED'];
		$completedTask = (
			$this->workflowIsCompleted && count($completedTasks) > 1
				? next($completedTasks)
				: current($completedTasks)
		);
		$finishCompletedTaskTimestamp = $completedTask ? $this->getDateTimeTimestamp($completedTask['MODIFIED']) : null;

		$completedDuration = (
			$startWorkflowTimestamp && $finishCompletedTaskTimestamp
				? ($finishCompletedTaskTimestamp - $startWorkflowTimestamp)
				: null
		);

		$runningTask = $this->workflowIsCompleted ? false : current($this->tasks['RUNNING']);
		$startRunningTaskTimestamp = (
			$runningTask
				? $this->getDateTimeTimestamp($runningTask['CREATED_DATE'] ?? null)
				: null
		);
		if (!$startRunningTaskTimestamp)
		{
			$startRunningTaskTimestamp = $runningTask ? $this->getDateTimeTimestamp($runningTask['MODIFIED']) : null;
		}
		$runningTaskDuration = (
			$startRunningTaskTimestamp && $finishWorkflowTimestamp
				? ($finishWorkflowTimestamp - $startRunningTaskTimestamp)
				: null
		);

		$startRunningTimestamp = $finishCompletedTaskTimestamp ?? $startWorkflowTimestamp;

		$runningDuration =
			$startRunningTimestamp && $finishWorkflowTimestamp
				? ($finishWorkflowTimestamp - $startRunningTimestamp)
				: null
		;

		return [
			'author' => $authorDuration,
			'completed' => $completedDuration,
			'running' => $runningTaskDuration ?? $runningDuration,
			'done' => $runningDuration,
		];
	}

	private function getMyTasks(): array
	{
		$userId = $this->userId;

		$myTasks = array_filter(
			$this->tasks['RUNNING'],
			static function($task) use ($userId) {
				$waitingUsers = array_filter(
					$task['USERS'],
					static fn ($user) => ((int)$user['STATUS'] === \CBPTaskUserStatus::Waiting),
				);

				return in_array($userId, array_column($waitingUsers, 'USER_ID'));
			},
		);

		$preparedTasks = [];
		foreach (array_values($myTasks) as $task)
		{
			$controls = \CBPDocument::getTaskControls($task);

			$preparedTasks[] = [
				'id' => $task['ID'],
				'name' => $task['~NAME'],
				'activity' => $task['ACTIVITY'],
				'hash' => $this->getTaskHash($task),
				'isInline' => \CBPHelper::getBool($task['IS_INLINE']),
				'buttons' => $controls['BUTTONS'] ?? null,
				'createdDate' => $task['~CREATED_DATE'] ?? null,
			];
		}

		return $this->unescapeMyTasks($preparedTasks);
	}

	private function unescapeMyTasks(array $myTasks): array
	{
		foreach ($myTasks as &$task)
		{
			if (!empty($task['buttons']))
			{
				foreach ($task['buttons'] as &$button)
				{
					if (!empty($button['TEXT']))
					{
						$button['TEXT'] = htmlspecialcharsback($button['TEXT']);
					}
				}
			}
		}

		return $myTasks;
	}

	private function getTaskHash(array $task): string
	{
		$hashData = [
			'TEMPLATE_ID' => $this->workflow['WORKFLOW_TEMPLATE_ID'] ?? 0,
		];

		if (isset($task['ACTIVITY_NAME']))
		{
			$hashData['ACTIVITY_NAME'] = $task['ACTIVITY_NAME'];
		}

		if (isset($task['ACTIVITY']) && $task['ACTIVITY'] === 'HandleExternalEventActivity')
		{
			$hashData['TASK_ID'] = $task['ID'];
		}

		$parameters = $task['PARAMETERS'] ?? null;

		if (is_array($parameters))
		{
			if (isset($parameters['ShowComment']))
			{
				$hashData['ShowComment'] = $parameters['ShowComment'];
			}
			if (isset($parameters['REQUEST']))
			{
				$hashData['REQUEST'] = $parameters['REQUEST'];
				if (is_array($parameters['REQUEST']))
				{
					foreach ($parameters['REQUEST'] as $property)
					{
						if ($property['Type'] === 'file' || $property['Type'] === 'S:DiskFile')
						{
							$hashData['TASK_ID'] = $task['ID'];
							break;
						}
					}
				}
			}
		}

		return md5(Json::encode($hashData));
	}

	private function extractUserIds(array $users): array
	{
		return array_slice(
			array_column($users, 'USER_ID'),
			0,
			3,
		);
	}

	private function getTypeName(): mixed
	{
		if (
			$this->workflow['DOCUMENT_INFO']['COMPLEX_ID'][0] !== 'lists'
			&& !empty($this->workflow['TEMPLATE_NAME'])
		)
		{
			return $this->workflow['TEMPLATE_NAME'];
		}

		return $this->workflow['DOCUMENT_INFO']['TYPE_CAPTION'] ?? '';
	}

	private function isWorkflowAuthorView(): bool
	{
		return $this->workflow['STARTED_USER_INFO']['ID'] === $this->userId;
	}

	private function getDateTimeTimestamp($datetime)
	{
		if ($datetime instanceof DateTime)
		{
			return $datetime->getTimestamp();
		}

		if (is_string($datetime) && DateTime::isCorrect($datetime))
		{
			return DateTime::createFromUserTime($datetime)->getTimestamp();
		}

		return null;
	}

	private function getCompletedTask()
	{
		$completedTasks = $this->tasks['COMPLETED'];

		$completedTask = current($completedTasks);
		if ($this->workflowIsCompleted && count($completedTasks) > 1)
		{
			$completedTask = next($completedTasks);
		}

		return $completedTask;
	}

	private function getCompletedTaskUserIds(): array
	{
		$completedTask = $this->getCompletedTask();

		return $this->extractUserIds($completedTask['USERS'] ?? []);
	}

	private function getDoneTask()
	{
		$completedTasks = $this->tasks['COMPLETED'];

		$completedTask = current($completedTasks);
		$doneTask = [];
		if ($this->workflowIsCompleted && count($completedTasks) > 1)
		{
			$doneTask = $completedTask;
		}

		return $doneTask;
	}

	private function getDoneTaskUserIds(): array
	{
		$doneTask = $this->getDoneTask();

		return $this->extractUserIds($doneTask['USERS'] ?? []);
	}

	private function getRunningTask()
	{
		return current($this->tasks['RUNNING']);
	}

	private function getRunningTaskUserIds(): array
	{
		$runningTask = $this->getRunningTask();

		return $this->extractUserIds($runningTask['USERS'] ?? []);
	}

	private function getNewCommentCounter(): int
	{
		$row = WorkflowUserCommentTable::getList([
			'filter' => [
				'=WORKFLOW_ID' => $this->workflow['ID'],
				'=USER_ID' => $this->userId,
			],
			'select' => ['UNREAD_CNT'],
		])->fetch();

		return $row ? (int)$row['UNREAD_CNT'] : 0;
	}
}
