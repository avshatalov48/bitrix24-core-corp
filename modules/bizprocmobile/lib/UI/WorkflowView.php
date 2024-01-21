<?php

namespace Bitrix\BizprocMobile\UI;

use Bitrix\Bizproc\WorkflowInstanceTable;

class WorkflowView implements \JsonSerializable
{
	private array $workflow;
	private array $tasks;
	private int $userId;

	public function __construct(array $workflow, int $userId)
	{
		$this->workflow = $workflow;
		$this->userId = $userId;

		$this->tasks = \CBPViewHelper::getWorkflowTasks($workflow['ID'], true, true);
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
			'itemName' => $this->getItemName($myTasks),
			'statusText' => $this->workflow['STATE_INFO']['TITLE'] ?? '',
			'faces' => $this->getFaces(),
			'tasks' => $myTasks,
			'authorId' => $this->workflow['STARTED_USER_INFO']['ID'],
		];
	}

	private function getItemName(array $myTasks): mixed
	{
		if ($myTasks)
		{
			return current($myTasks)['name'];
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

	private function getFaces(): array
	{
		$completedTasks = array_reverse($this->tasks['COMPLETED']);
		$completedTask = current($completedTasks);
		$completedUsers = array_merge(...array_column($completedTasks, 'USERS'));

		$runningTask = current($this->tasks['RUNNING']);

		return [
			'author' => $this->workflow['STARTED_USER_INFO']['ID'],
			'completedSuccess' => \CBPTaskStatus::isSuccess($completedTask['STATUS'] ?? 0),
			'completed' => array_reverse($this->extractUserIds(($completedUsers))),
			'running' => $this->extractUserIds($runningTask['USERS'] ?? []),
			'workflowIsCompleted' => !WorkflowInstanceTable::exists($this->workflow['ID']),
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

		return $this->unescapeMyTasks(array_map(
			static function($task) {
				$controls = \CBPDocument::getTaskControls($task);

				return [
					'id' => $task['ID'],
					'name' => $task['~NAME'],
					'isInline' => \CBPHelper::getBool($task['IS_INLINE']),
					'buttons' => $controls['BUTTONS'] ?? null
				];
			},
			array_values($myTasks),
		));
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
}
