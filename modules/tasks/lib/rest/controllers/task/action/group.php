<?php

namespace Bitrix\Tasks\Rest\Controllers\Task\Action;

use Bitrix\Main\Engine\Action;
use	Bitrix\Main\Engine\Controller;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Access\AccessCacheLoader;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Util\Error\Collection;

use Bitrix\Tasks\Helper\Filter;

use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;

use Bitrix\Tasks\Control\Group\Mute;
use Bitrix\Tasks\Control\Group\UnMute;
use Bitrix\Tasks\Control\Group\Ping;
use Bitrix\Tasks\Control\Group\Complete;
use Bitrix\Tasks\Control\Group\SetDeadline;
use Bitrix\Tasks\Control\Group\AdjustDeadline;
use Bitrix\Tasks\Control\Group\SetTaskControl;
use Bitrix\Tasks\Control\Group\SetResponsible;
use Bitrix\Tasks\Control\Group\SetOriginator;
use Bitrix\Tasks\Control\Group\AddAuditor;
use Bitrix\Tasks\Control\Group\AddAccomplice;
use Bitrix\Tasks\Control\Group\Favorite;
use Bitrix\Tasks\Control\Group\SetGroup;
use Bitrix\Tasks\Control\Group\SetFlow;
use Bitrix\Tasks\Control\Group\Delete;

use Bitrix\Tasks\Grid\Task;

class Group extends Controller
{
	private const STATUS_COMPLETED = 'COMPLETED';
	private const STATUS_PROGRESS = 'PROGRESS';
	private const STATUS_ERROR = 'ERROR';
	private const GROUP_ACTIONS_READ_ACCESS = [
		Task\GroupAction::ACTION_UNMUTE,
		Task\GroupAction::ACTION_MUTE,
		Task\GroupAction::ACTION_PING,
		Task\GroupAction::ACTION_ADD_FAVORITE,
		Task\GroupAction::ACTION_REMOVE_FAVORITE,
	];
	private const DEADLINE_EXTENSION_LIMIT = 999;

	private int $totalItems = 0;
	private int $processedItems = 0;
	private bool $isProcessCompleted = false;
	private int $userId;
	private Collection $errors;
	private array $actionNotAvailable;
	public const DEADLINE_EXTENSION_TYPE = ['day', 'week', 'month'];

	protected function processBeforeAction(Action $action): bool
	{
		$this->userId = (int)$this->getCurrentUser()->getId();
		$this->errors = new Collection();

		return parent::processBeforeAction($action);
	}

	public function getTotalCountTasksAction(array $data): array
	{
		if (!array_key_exists('groupId', $data))
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
			return $this->preformProcessAnswer();
		}

		$filter = Filter::getInstance($this->userId, (int)$data['groupId']);

		if (!$filter)
		{
			$this->addForbiddenError();
		}

		$filter = $filter->process();
		unset($filter['ONLY_ROOT_TASKS']);

		$list = new TaskList();

		$queryTotalCount = (new TaskQuery($this->userId))
			->skipAccessCheck()
			->setWhere($filter);

		$totalCount = $list->getCount($queryTotalCount);

		$this->setTotalItems($totalCount);
		$this->setProcessDone();

		return $this->preformProcessAnswer();
	}

	public function unMuteAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_UNMUTE);
		$unMute = new UnMute();
		$result = $unMute->runBatch($this->userId, $accessedTaskIds);

		$this->checkErrors($result);

		return $this->processAnswer(count($taskIds));
	}

	public function muteAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_MUTE);

		$mute = new Mute();
		$result = $mute->runBatch($this->userId, $accessedTaskIds);

		$this->checkErrors($result);

		return $this->processAnswer(count($taskIds));
	}

	public function pingAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_PING);

		$ping = new Ping();
		$ping->runBatch($this->userId, $accessedTaskIds);

		return $this->processAnswer(count($taskIds));
	}

	public function completeAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_COMPLETE);

		$complete = new Complete();
		$complete->runBatch($this->userId, $accessedTaskIds);

		return $this->processAnswer(count($taskIds));
	}

	public function setDeadlineAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);
		$deadline = $data['setDeadline'] ?? '';
		$isCorrectDeadline = DateTime::isCorrect($deadline);

		if (!$isCorrectDeadline)
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_DEADLINE_CANNOT_BE_EMPTY'));
			return $this->preformProcessAnswer();
		}

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_SET_DEADLINE);

		$setDeadline = new SetDeadline();
		$setDeadline->runBatch($this->userId, $accessedTaskIds, $deadline);

		return $this->processAnswer(count($taskIds));
	}

	public function adjustDeadlineAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);
		$adjust['type'] = $data['type'] ?? '';
		$adjust['num'] = (int)$data['num'];

		if (!$adjust['num'] || !$adjust['type'] || !in_array($adjust['type'], self::DEADLINE_EXTENSION_TYPE))
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_DAYS_NUM_INVALID_TEXT'));
			return $this->preformProcessAnswer();
		}

		if ($adjust['num'] > self::DEADLINE_EXTENSION_LIMIT)
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_DAYS_NUM_INVALID_TEXT_NOT_CORRECT'));
			return $this->preformProcessAnswer();
		}

		if (array_key_exists('substract', $data))
		{
			$adjust['num'] *= -1;
		}

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_ADJUST_DEADLINE);

		$adjustDeadline = new AdjustDeadline();
		$result = $adjustDeadline->runBatch($this->userId, $accessedTaskIds, $adjust);

		$this->checkErrors($result);

		return $this->processAnswer(count($taskIds));
	}

	public function substractDeadlineAction(array $data): array
	{
		$data['substract'] = true;

		return $this->adjustDeadlineAction($data);
	}

	public function setTaskControlAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);
		$state = $data['taskControlState'] ?? [];

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_SET_TASK_CONTROL);

		$setTaskControl = new SetTaskControl();
		$setTaskControl->runBatch($this->userId, $accessedTaskIds, $state);

		return $this->processAnswer(count($taskIds));
	}

	public function setResponsibleAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);
		$responsibleId = (int)$data['responsibleId'];

		if (!$responsibleId)
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_USER_RESPONSIBLE_CANNOT_BE_EMPTY'));
			return $this->preformProcessAnswer();
		}

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_SET_RESPONSIBLE, $responsibleId);

		$setResponsible = new SetResponsible();
		$setResponsible->runBatch($this->userId, $accessedTaskIds, $responsibleId);

		return $this->processAnswer(count($taskIds));
	}

	public function setOriginatorAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);
		$originatorId = (int)$data['originatorId'];

		if (!$originatorId)
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_USER_ORIGINATOR_CANNOT_BE_EMPTY'));
			return $this->preformProcessAnswer();
		}

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_SET_ORIGINATOR, $originatorId);

		$setOriginator = new SetOriginator();
		$setOriginator->runBatch($this->userId, $accessedTaskIds, $originatorId);

		return $this->processAnswer(count($taskIds));
	}

	public function addAuditorAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);
		$auditorId = (int)$data['auditorId'];

		if (!$auditorId)
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_USER_AUDITOR_CANNOT_BE_EMPTY'));
			return $this->preformProcessAnswer();
		}

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_ADD_AUDITOR, $auditorId);

		$addAuditor = new AddAuditor();
		$addAuditor->runBatch($this->userId, $accessedTaskIds, $auditorId);

		return $this->processAnswer(count($taskIds));
	}

	public function addAccompliceAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);
		$accompliceId = (int)$data['accompliceId'];

		if (!$accompliceId)
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_USER_ACCOMPLICE_CANNOT_BE_EMPTY'));
			return $this->preformProcessAnswer();
		}

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_ADD_ACCOMPLICE, $accompliceId);

		$addAccomplice = new AddAccomplice();
		$addAccomplice->runBatch($this->userId, $accessedTaskIds, $accompliceId);

		return $this->processAnswer(count($taskIds));
	}

	public function addToFavoriteAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_ADD_FAVORITE);

		$addToFavorite = new Favorite();
		$addToFavorite->add($this->userId, $accessedTaskIds);

		return $this->processAnswer(count($taskIds));
	}

	public function removeFromFavoriteAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_REMOVE_FAVORITE);

		$removeFromFavorite = new Favorite();
		$removeFromFavorite->remove($this->userId, $accessedTaskIds);

		return $this->processAnswer(count($taskIds));
	}

	public function setGroupAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);
		$groupId = (int)$data['specifyGroupId'];

		if (!$groupId)
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_GROUP_CANNOT_BE_EMPTY'));
			return $this->preformProcessAnswer();
		}
		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_SET_GROUP, $groupId);

		$setGroup = new SetGroup();
		$setGroup->runBatch($this->userId, $accessedTaskIds, $groupId);

		return $this->processAnswer(count($taskIds));
	}

	public function setFlowAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);
		$flowId = (int)$data['flowId'];

		if (!$flowId)
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_FLOW_CANNOT_BE_EMPTY'));
			return $this->preformProcessAnswer();
		}

		$flow = (new FlowProvider())->getFlow($flowId, ['*', 'OPTIONS']);
		if (!$flow->isActive())
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_FLOW_OFF'));
			return $this->preformProcessAnswer();
		}

		if (!$this->flowAccessCheck($flowId))
		{
			return $this->preformProcessAnswer();
		}

		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_SET_FLOW);

		$setFlow = new SetFlow();
		$setFlow->runBatch($this->userId, $accessedTaskIds, $flowId);

		return $this->processAnswer(count($taskIds));
	}

	public function deleteAction(array $data): array
	{
		$taskIds = $this->checkTaskParams($data);
		$accessedTaskIds = $this->tasksAccessCheck($taskIds, Task\GroupAction::ACTION_DELETE);

		$delete = new Delete();
		$delete->runBatch($this->userId, $accessedTaskIds);

		return $this->processAnswer(count($taskIds));
	}

	public function hasErrors(): bool
	{
		if ($this->errors)
		{
			return $this->errors->checkHasErrors();
		}

		return false;
	}

	private function checkTaskParams(array $data): array
	{
		if (!isset($data['groupId']))
		{
			$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
			return $this->preformProcessAnswer();
		}

		$taskIds = $data['selectedIds'] ?? [];

		if (isset($data['processedItems']))
		{
			$this->setProcessedItems((int)$data['processedItems']);
		}

		if (isset($data['totalItems']))
		{
			$this->setTotalItems((int)$data['totalItems']);
		}
		else
		{
			$this->setTotalItems(count($taskIds));
		}

		if (empty($taskIds))
		{
			$taskIds = $this->getTaskIds($data);
		}

		return $taskIds;
	}

	private function getTaskIds(array $data): array
	{
		$taskIds = [];

		$filter = Filter::getInstance($this->userId, $data['groupId'])->process();
		unset($filter['ONLY_ROOT_TASKS']);

		$select = [
			'ID',
		];

		$query = (new TaskQuery($this->userId))
			->setBehalfUser($this->userId)
			->setSelect($select)
			->setWhere($filter)
			->setLimit($data['nPageSize'])
			->setOffset($this->processedItems);

		$list = new TaskList();
		$tasks = $list->getList($query);

		foreach ($tasks as $item)
		{
			$taskIds[] = $item['ID'];
		}

		return $taskIds;
	}

	private function tasksAccessCheck(array $ids, string $action, int $differentUserId = null): array
	{
		(new AccessCacheLoader())->preload($this->userId, $ids);
		$result = [];

		if (in_array($action, self::GROUP_ACTIONS_READ_ACCESS))
		{
			$actionError = [];
			foreach ($ids as $id)
			{
				if (TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$id))
				{
					$result[] = $id;
				}
				else
				{
					$actionError[] = $id;
				}
			}
			if ($actionError) {
				$taskIdsError = join(', ', $actionError);
				$this->errors->addWarning(
					'RESULT_REQUIRED',
					Loc::getMessage(
						'TASKS_ACTION_NOT_ALLOWED_IN_TASKS',
						['#TASK_LIST#' => $taskIdsError]
					),
				);
			}
		}

		if ($action === Task\GroupAction::ACTION_ADD_AUDITOR)
		{
			$actionError = [];
			foreach ($ids as $id)
			{
				if (TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_ADD_AUDITORS, (int)$id, $differentUserId))
				{
					$result[] = $id;
				}
				else
				{
					$actionError[] = $id;
				}
			}
			if ($actionError) {
				$taskIdsError = join(', ', $actionError);
				$this->errors->addWarning(
					'RESULT_REQUIRED',
					Loc::getMessage(
						'TASKS_ACTION_NOT_ALLOWED_IN_TASKS',
						['#TASK_LIST#' => $taskIdsError]
					),
				);
			}
		}

		if ($action === Task\GroupAction::ACTION_SET_TASK_CONTROL)
		{
			foreach ($ids as $id)
			{
				$oldTask = TaskModel::createFromId((int)$id);

				$newTask = clone $oldTask;
				$members = $newTask->getMembers();
				$newTask->setMembers($members);
				if ((new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_SAVE, $oldTask, $newTask))
				{
					$result[] = $id;
				}
				else
				{
					$this->actionNotAvailable[] = $id;
				}
			}
		}

		if ($action === Task\GroupAction::ACTION_COMPLETE)
		{
			$actionErrorCompliteResult = [];
			foreach ($ids as $id)
			{
				$task = TaskModel::createFromId((int)$id);
				if ($task->isClosed())
				{
					continue;
				}
				elseif (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_COMPLETE, $id))
				{
					$this->actionNotAvailable[] = $id;
				}
				elseif (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_COMPLETE_RESULT, $id))
				{
					$actionErrorCompliteResult[] = $id;
				}
				else
				{
					$result[] = $id;
				}
			}
			if ($actionErrorCompliteResult) {
				$taskIdsError = join(', ', $actionErrorCompliteResult);
				$this->errors->addWarning(
					'RESULT_REQUIRED',
					Loc::getMessage(
						'TASKS_ACTION_RESULT_REQUIRED',
						['#TASK_LIST#' => $taskIdsError]
					),
				);
			}
		}

		if ($action === Task\GroupAction::ACTION_SET_DEADLINE
			|| $action === Task\GroupAction::ACTION_ADJUST_DEADLINE
			)
		{
			foreach ($ids as $id)
			{
				if (TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_DEADLINE, (int)$id))
				{
					$result[] = $id;
				}
				else
				{
					$this->actionNotAvailable[] = $id;
				}
			}
		}

		if ($action === Task\GroupAction::ACTION_SET_RESPONSIBLE)
		{
			$taskAccessController = new TaskAccessController($this->userId);

			foreach ($ids as $id)
			{
				$oldTask = TaskModel::createFromId((int)$id);
				$newTask = clone $oldTask;
				$members = $newTask->getMembers();
				$members[RoleDictionary::ROLE_RESPONSIBLE] = [
					(int)$differentUserId
				];
				$newTask->setMembers($members);

				if ($taskAccessController->check(ActionDictionary::ACTION_TASK_CHANGE_RESPONSIBLE, $oldTask, $newTask))
				{
					$result[] = $id;
				}
				else
				{
					$this->actionNotAvailable[] = $id;
				}
			}
		}

		if ($action === Task\GroupAction::ACTION_SET_ORIGINATOR)
		{
			$taskAccessController = new TaskAccessController($this->userId);

			foreach ($ids as $id)
			{
				$id = (int)$id;
				$originatorId = (int)$differentUserId;
				if ($id <=0 || $originatorId <= 0)
				{
					$this->addForbiddenError();
				}

				$oldTask = TaskModel::createFromId($id);
				$newTask = clone $oldTask;
				$members = $newTask->getMembers();
				$members[RoleDictionary::ROLE_DIRECTOR] = [
					$originatorId
				];
				$newTask->setMembers($members);

				if ($taskAccessController->check(ActionDictionary::ACTION_TASK_CHANGE_DIRECTOR, $oldTask, $newTask))
				{
					$result[] = $id;
				}
				else
				{
					$this->actionNotAvailable[] = $id;
				}
			}
		}

		if ($action === Task\GroupAction::ACTION_ADD_ACCOMPLICE)
		{
			$taskAccessController = new TaskAccessController($this->userId);

			foreach ($ids as $id)
			{
				$id = (int)$id;
				$accompliceId = (int)$differentUserId;
				if ($id <=0 || $accompliceId <= 0)
				{
					$this->addForbiddenError();
				}

				$oldTask = TaskModel::createFromId($id);
				$newTask = clone $oldTask;
				$members = $newTask->getMembers();
				$members[RoleDictionary::ROLE_ACCOMPLICE] = [
					$accompliceId
				];
				$newTask->setMembers($members);

				if ($taskAccessController->check(ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES, $oldTask, $newTask))
				{
					$result[] = $id;
				}
				else
				{
					$this->actionNotAvailable[] = $id;
				}
			}
		}

		if ($action === Task\GroupAction::ACTION_SET_GROUP)
		{
			$taskAccessController = new TaskAccessController($this->userId);

			foreach ($ids as $id)
			{
				$id = (int)$id;
				$groupId = (int)$differentUserId;
				if ($id <=0 || $groupId <= 0)
				{
					$this->addForbiddenError();
				}

				$oldTask = TaskModel::createFromId($id);
				$newTask = clone $oldTask;
				$newTask->setGroupId($groupId);

				if ($taskAccessController->check(ActionDictionary::ACTION_TASK_SAVE, $oldTask, $newTask))
				{
					$result[] = $id;
				}
				else
				{
					$this->actionNotAvailable[] = $id;
				}
			}
		}

		if ($action === Task\GroupAction::ACTION_SET_FLOW)
		{
			$taskAccessController = new TaskAccessController($this->userId);
			$taskIdsError = [];
			foreach ($ids as $id)
			{
				if ($taskAccessController->can($this->userId, ActionDictionary::ACTION_TASK_EDIT, $id))
				{
					$result[] = $id;
				}
				else
				{
					$taskIdsError[] = $id;
				}
			}

			if (!empty($taskIdsError))
			{
				$taskIdsError = join(', ', $taskIdsError);
				$this->errors->addWarning(
					'ACTION_NOT_ALLOWED.RESTRICTED',
					Loc::getMessage(
						'TASKS_ACTION_NOT_ALLOWED_IN_TASKS',
						['#TASK_LIST#' => $taskIdsError]
					)
				);
			}
		}

		if ($action === Task\GroupAction::ACTION_DELETE)
		{
			foreach ($ids as $id)
			{
				if (TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_REMOVE, (int)$id))
				{
					$result[] = $id;
				}
				else
				{
					$this->actionNotAvailable[] = $id;
				}
			}
		}

		return $result;
	}

	private function flowAccessCheck(int $flowId): bool
	{
		if (FlowAccessController::can($this->userId, FlowAction::READ, $flowId))
		{
			return true;
		}
		else
		{
			$this->addForbiddenError();
		}

		return false;
	}

	private function preformProcessAnswer(array $actionResult = []): array
	{
		if (!empty($this->actionNotAvailable))
		{
			$taskIdsError = join(', ', $this->actionNotAvailable);
			$this->errors->addWarning(
				'ACTION_NOT_ALLOWED.RESTRICTED',
				Loc::getMessage(
					'TASKS_ACTION_NOT_ALLOWED_IN_TASKS',
					['#TASK_LIST#' => $taskIdsError]
				)
			);
		}

		$warning = $this->errors->filter(['TYPE' => Error::TYPE_WARNING]);
		$warningText = $warning ? implode(' <br><br> ', $warning->getMessages()) : '';
		$errors = $this->errors->filter(['TYPE' => Error::TYPE_FATAL])->getMessages();
		$errorsText = $errors ? implode(', ', $errors) : '';

		if ($this->totalItems > 0)
		{
			$actionResult['TOTAL_ITEMS'] = $this->totalItems;
			$actionResult['PROCESSED_ITEMS'] = $this->processedItems;
			$actionResult['ERRORS'] = $errorsText;
			$actionResult['WARNING_TEXT'] = $warningText;
			$actionResult['STATUS'] = $this->getStatus();
		}

		return $actionResult;
	}

	private function processAnswer(int $ProcessedItems): array
	{
		$this->incrementProcessedItems($ProcessedItems);

		if ($this->totalItems === $this->processedItems)
		{
			$this->setProcessDone();
		}

		return $this->preformProcessAnswer();
	}

	private function getStatus(): string
	{
		$status = self::STATUS_PROGRESS;

		if ($this->hasErrors())
		{
			$status = self::STATUS_ERROR;
		}
		elseif ($this->hasProcessCompleted())
		{
			$status = self::STATUS_COMPLETED;
		}

		return $status;
	}

	private function setTotalItems(int $totalItems): void
	{
		$this->totalItems = $totalItems;
	}

	private function setProcessedItems(int $processedItems): void
	{
		$this->processedItems = $processedItems;
	}

	private function incrementProcessedItems(int $incrementItems): void
	{
		$this->processedItems += $incrementItems;
	}

	private function setProcessDone(bool $done = true): void
	{
		$this->isProcessCompleted = $done;
	}

	private function hasProcessCompleted(): bool
	{
		return $this->isProcessCompleted;
	}

	private function checkErrors(array $results): void
	{
		$errorsList = [];

		foreach ($results as $result )
		{
			$errors = $result[0]->getErrors();
			$taskId = $result['taskId'];

			foreach ($errors as $error)
			{
				$errorsList[$error->getMessage()][] = $taskId;
			}
		}

		if (!empty($errorsList))
		{
			foreach ($errorsList as $error => $taskIds)
			{
				$taskIdsError = join(', ', $taskIds);
				$this->errors->add(
					'ACTION_NOT_ALLOWED.RESTRICTED',
					Loc::getMessage(
						'TASKS_ACTION_NOT_ALLOWED_IN_TASKS',
						['#TASK_LIST#' => $taskIdsError]
					)
				);
			}
		}
	}

	protected function addForbiddenError()
	{
		$this->errors->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
	}
}
