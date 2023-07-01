<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

namespace Bitrix\Rpa\Integration\Bizproc;

use Bitrix\Bizproc;
use Bitrix\Bizproc\Automation\Helper;
use Bitrix\Bizproc\Workflow\Entity\WorkflowInstanceTable;
use Bitrix\Bizproc\Workflow\Entity\WorkflowStateTable;
use Bitrix\Main;
use Bitrix\Rpa\Components\Base;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\Item;

class TaskManager
{
	public const TASKS_FILTER_FIELD = 'tasks';
	public const TASKS_FILTER_HAS_TASKS_VALUE = 'has_tasks';
	public const TASKS_FILTER_NO_TASKS_VALUE = 'no_tasks';

	public function __construct()
	{
		if (!Main\Loader::includeModule('bizproc'))
		{
			throw new Main\InvalidOperationException('Unresolved dependency. Bizproc module needed');
		}
	}

	public function onItemStageUpdate(Item $item, int $stageId, int $userId): bool
	{
		$tasks = $this->getIncompleteItemTasks($item, $userId);

		$errors = [];
		foreach ($tasks as $task)
		{
			\CBPDocument::SendExternalEvent(
				$task['WORKFLOW_ID'],
				$task['ACTIVITY_NAME'],
				[
					'USER_ID' => $userId,
					'onStageUpdate' => true,
					'stageId' => $stageId
				],
				$errors
			);
		}

		return (!empty($errors) && reset($errors)['code'] === \CBPRuntime::EXCEPTION_CODE_INSTANCE_TERMINATED);
	}

	public function getItemTaskParticipants(Item $item, int $taskUserStatus = null): array
	{
		$taskUserStatus = $taskUserStatus ?? \CBPTaskUserStatus::Waiting;

		$documentId = Document\Item::makeComplexId($item->getType()->getId(), $item->getId());
		$instanceIds = WorkflowInstanceTable::getIdsByDocument($documentId);
		$workflowId = reset($instanceIds);

		return $workflowId ? \CBPTaskService::getWorkflowParticipants($workflowId, $taskUserStatus) : [];
	}

	public function getItemFaces(int $typeId, int $itemId): array
	{
		$result = [
			'completed' => [],
			'running' => [],
			'all' => [],
		];

		$documentId = Document\Item::makeComplexId($typeId, $itemId);
		$workflowIds = WorkflowStateTable::getIdsByDocument($documentId);

		if (!$workflowIds)
		{
			return $result;
		}

		$ids = [];
		$taskIterator = \CBPTaskService::GetList(
			['MODIFIED' => 'DESC'],
			['@WORKFLOW_ID' => $workflowIds],
			false,
			['nTopCount' => 50],
			['ID']
		);

		while ($task = $taskIterator->fetch())
		{
			$ids[] = $task['ID'];
		}

		if (!$ids)
		{
			return $result;
		}

		$taskUsers = \CBPTaskService::getTaskUsers($ids);
		$taskUsers = array_values($taskUsers);
		$taskUsers = count($taskUsers) > 1 ? array_merge(...$taskUsers) : reset($taskUsers);

		if ($taskUsers)
		{
			foreach ($taskUsers as $user)
			{
				if ($user['STATUS'] === \CBPTaskUserStatus::Waiting)
				{
					$result['running'][] = (int)$user['USER_ID'];
				}
				else
				{
					$result['completed'][] = (int)$user['USER_ID'];
				}
			}
		}

		$result['completed'] = array_unique($result['completed']);
		$result['running'] = array_unique($result['running']);
		$result['all'] = array_unique(array_merge($result['completed'], $result['running']));

		return $result;
	}


	public function getIncompleteItemTasks(Item $item, int $userId = null): array
	{
		$itemId = $item->getId() ?: 0;
		$documentId = Document\Item::makeComplexId($item->getType()->getId(), $itemId);
		$instanceIds = WorkflowInstanceTable::getIdsByDocument($documentId);
		$workflowId = reset($instanceIds);

		$filter = [
			'WORKFLOW_ID' => $workflowId,
			'USER_STATUS' => \CBPTaskUserStatus::Waiting,
		];

		if ($userId)
		{
			$filter['USER_ID'] = $userId;
		}

		$tasksIterator = \CBPTaskService::GetList([], $filter, false, false,
			['ID', 'USER_ID', 'WORKFLOW_ID', 'ACTIVITY', 'ACTIVITY_NAME', 'NAME', 'DESCRIPTION', 'PARAMETERS']
		);

		$tasks = [];
		$taskUsers = [];

		while ($row = $tasksIterator->fetch())
		{
			if (!isset($taskUsers[$row['ID']]))
			{
				$taskUsers[$row['ID']] = [];
			}
			$taskUsers[$row['ID']][] = (int) $row['USER_ID'];
			$tasks[$row['ID']] = $row;
		}

		foreach ($taskUsers as $taskId => $users)
		{
			$tasks[$taskId]['USER_ID'] = $users[0];
			if (in_array($userId, $users))
			{
				$tasks[$taskId]['USER_ID'] = $userId;
			}
			$tasks[$taskId]['USERS'] = $users;
		}

		return array_values($tasks);
	}

	public function getTaskById(int $taskId): ?array
	{
		$task = \CBPTaskService::GetList(
			[], ['ID' => $taskId], false, false,
			['ID', 'USER_ID', 'WORKFLOW_ID', 'ACTIVITY', 'ACTIVITY_NAME', 'NAME', 'DESCRIPTION', 'PARAMETERS']
		)->fetch();
		if ($task)
		{
			$taskUsers = $this->getTaskUsers($taskId);
			$task['USERS'] = array_column($taskUsers, 'id');
			$task['INCOMPLETE_USERS'] = array_filter(array_map(function ($user) {
				return ($user['status'] === \CBPTaskUserStatus::Waiting) ? $user['id'] : null;
			}, $taskUsers));

			return $task;
		}

		return null;
	}

	public function getTaskUsers(int $taskId): array
	{
		$result = [];
		$taskUsers = \CBPTaskService::getTaskUsers($taskId)[$taskId];
		if ($taskUsers)
		{
			foreach ($taskUsers as $user)
			{
				$result[] = [
					'id' => (int) $user['USER_ID'],
					'status' => (int) $user['STATUS'],
				];
			}
		}
		return $result;
	}

	public function countTypeStageRobots(int $typeId, int $stageId): int
	{
		$documentType = Document\Item::makeComplexType($typeId);
		$template = new Bizproc\Automation\Engine\Template($documentType, $stageId);
		return count($template->getRobots());
	}

	public function getTypeStageTasks(int $typeId, int $stageId): array
	{
		$documentType = Document\Item::makeComplexType($typeId);
		$template = new Bizproc\Automation\Engine\Template($documentType, $stageId);
		$robots = $template->getRobots();

		$tasks = [];

		foreach ($robots as $robot)
		{
			if ($this->isTaskRobot($robot))
			{
				$robotData = $robot->toArray();
				$tasks[] = [
					'title' => $robotData['Properties']['Name'],
					'robotType' => $robotData['Type'],
					'robotName' => $robotData['Name'],
					'canAppendResponsibles' => !(
						$robotData['Type'] === 'RpaApproveActivity'
						&&
						$robotData['Properties']['ResponsibleType'] === 'heads'
					),
					'users' => $this->prepareUsers($documentType, $robotData['Properties']['Responsible'])
				];
			}
		}
		return $tasks;
	}

	public function getUserIncompleteTasksByType(array $typeIds = [], int $userId = null): array
	{
		if ($userId === null)
		{
			$userId = Main\Engine\CurrentUser::get()->getId();
		}

		//todo filter by type ids, not all instances for module
		$typeInstanceIds = [];
		$instanceIds = [];
		$moduleInstances = WorkflowInstanceTable::getList([
			'select' => [
				'ID', 'DOCUMENT_ID',
			],
			'filter' => [
				'=MODULE_ID' => Driver::MODULE_ID,
				'=ENTITY' => Document\Item::class,
			],
		]);
		while($moduleInstance = $moduleInstances->fetch())
		{
			$instanceTypeId = Document\Item::getDocumentTypeId($moduleInstance['DOCUMENT_ID']);
			if(empty($typeIds) || in_array($instanceTypeId, $typeIds))
			{
				$instanceIds[] = $moduleInstance['ID'];
				$typeInstanceIds[$moduleInstance['ID']] = $instanceTypeId;
			}
		}

		$tasksIterator = \CBPTaskService::GetList([], [
			'WORKFLOW_ID' => $instanceIds,
			'USER_ID' => $userId,
			'USER_STATUS' => \CBPTaskUserStatus::Waiting,
		]
		);

		$tasks = [];
		while ($row = $tasksIterator->fetch())
		{
			if (isset($typeInstanceIds[$row['WORKFLOW_ID']]))
			{
				$tasks[$typeInstanceIds[$row['WORKFLOW_ID']]][$row['ID']] = $row;
			}
		}
		return $tasks;
	}

	public function getUserIncompleteTasksForType(int $typeId, int $userId = null): array
	{
		$tasks = $this->getUserIncompleteTasksByType([$typeId], $userId);
		if(isset($tasks[$typeId]) && is_array($tasks[$typeId]))
		{
			return $tasks[$typeId];
		}

		return [];
	}

	public function getUserTotalIncompleteCounter(int $userId = null): int
	{
		if ($userId === null)
		{
			$userId = Main\Engine\CurrentUser::get()->getId();
		}

		$cnt = \CBPTaskService::getCounters($userId);
		$cnt = isset($cnt[Driver::MODULE_ID]) ? (int) $cnt[Driver::MODULE_ID]['*'] : 0;

		$currentCounter = (int)\CUserCounter::GetValue($userId, 'rpa_tasks', '**');
		if ($currentCounter !== $cnt)
		{
			\CUserCounter::Set($userId, 'rpa_tasks', $cnt, '**');
		}

		return $cnt;
	}

	public function getUserItemIncompleteCounter(Item $item, int $userId = null): int
	{
		if ($userId === null)
		{
			$userId = Main\Engine\CurrentUser::get()->getId();
		}

		//TODO: optimization
		$tasks = $this->getIncompleteItemTasks($item, $userId);
		return count($tasks);
	}

	public function onTaskPropertiesChanged(array $documentType, int $templateId, array $robotData): void
	{
		$workflowIds = WorkflowInstanceTable::getIdsByTemplateId($templateId);
		$tasksIterator = \CBPTaskService::GetList([], [
			'@WORKFLOW_ID' => $workflowIds,
			'ACTIVITY_NAME' => $robotData['Name'],
			'ACTIVITY' => $robotData['Type'],
			'STATUS' => 0
		], false, false, ['ID', 'PARAMETERS']);
		$props = $robotData['Properties'];
		while ($task = $tasksIterator->fetch())
		{
			$presentedUsers = \CBPTaskService::getTaskUserIds($task['ID']);
			$users = \CBPHelper::ExtractUsers($props['Responsible'], $task['PARAMETERS']['DOCUMENT_ID']);

			\CBPTaskService::Update($task['ID'], [
				'NAME' => $props['Name'],
				'DESCRIPTION' => $props['Description'],
				'USERS' => array_merge($presentedUsers, $users),
			]);
		}
	}

	public function getTimelineTasks(Item $item, $userId = null): array
	{
		$userId = $userId ?? (int) Main\Engine\CurrentUser::get()->getId();
		$fields = $this->getFieldsForTasks($item);

		return array_map(
			function($task) use ($userId, $fields)
			{
				$fieldsToSet = !empty($task['PARAMETERS']['FIELDS_TO_SET']) ?
					array_values(array_intersect_key($fields, array_flip($task['PARAMETERS']['FIELDS_TO_SET'])))
					: null;

				$taskUserId = (int) $task['USER_ID'];
				$isMine = in_array($userId, $task['USERS']);
				$participantJoint = 'or';
				if ($task['ACTIVITY'] === 'RpaApproveActivity')
				{
					if ($task['PARAMETERS']['APPROVE_TYPE'] == 'queue' || $task['PARAMETERS']['RESPONSIBLE_TYPE'] === 'heads')
					{
						$participantJoint = 'queue';
					}
					elseif ($task['PARAMETERS']['APPROVE_TYPE'] === 'all')
					{
						$participantJoint = 'and';
					}
				}

				$taskUsers = $this->getTaskUsers($task['ID']);

				return [
					'id' => $task['ID'],
					'title' => $task['NAME'],
					'description' => $task['DESCRIPTION'],
					'userId' => $taskUserId,
					'data' => [
						'participantJoint' => $participantJoint,
						'isMine' => $isMine,
						'controls' => $isMine ? \CBPDocument::getTaskControls($task) : null,
						'type' => $task['ACTIVITY'],
						'url' => Driver::getInstance()->getUrlManager()->getTaskIdUrl($task['ID']),
						'fieldsToShow' => null,
						'fieldsToSet' => $fieldsToSet,
						'users' => $taskUsers,
					],
					'itemClassName' => 'BX.Rpa.Timeline.Task',
					'users' => Base::getUsers(array_column($taskUsers, 'id')),
				];
			},
			$this->getIncompleteItemTasks($item)
		);
	}

	private function getFieldsForTasks(Item $item): array
	{
		$fields = [];
		$stage = $item->getStage();
		if($stage)
		{
			foreach ($stage->getUserFieldCollection() as $field)
			{
				$fields[$field->getName()] = $field->getTitle();
			}
		}

		return $fields;
	}

	private function isTaskRobot(Bizproc\Automation\Engine\Robot $robot): bool
	{
		return in_array($robot->getType(), [
			'RpaApproveActivity',
			'RpaMoveActivity',
			'RpaRequestActivity',
			'RpaReviewActivity',
		]);
	}

	private function prepareUsers(array $documentType, $users): array
	{
		return Helper::prepareUserSelectorEntities($documentType, $users);
	}
}
