<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2015 Bitrix
 *
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\Model\TaskModel;
use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Comments\Task\CommentPoster;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\Result\ResultManager;
use Bitrix\Tasks\Internals\UserOption;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Manager;
use Bitrix\Tasks\Util;

final class Task extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	/**
	 * Get a task
	 */
	public function get($id, array $parameters = array())
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			$mgrResult = Manager\Task::get($this->userId, $id, array(
				'ENTITY_SELECT' => $parameters[ 'ENTITY_SELECT' ],
				'PUBLIC_MODE' => true,
				'ERRORS' => $this->errors
			));

			if ($this->errors->checkNoFatals())
			{
				$result = array(
					'ID' => $id,
					'DATA' => $mgrResult[ 'DATA' ],
					'CAN' => $mgrResult[ 'CAN' ]
				);
			}
		}

		return $result;
	}

	/**
	 * @deprecated since tasks 21.200.0
	 *
	 * Get a list of tasks
	 *
	 * Access rights will be check into the Task
	 */
	public function find(array $parameters = array())
	{
		if (!array_key_exists('limit', $parameters) || intval($parameters[ 'limit' ] > 10))
		{
			$parameters['limit' ] = 10;
		}
		$selectIsEmpty = !array_key_exists('select', $parameters) || !count($parameters[ 'select' ]);

		$data = array();
		$result = \Bitrix\Tasks\Item\Task::find($parameters);

		if ($result->isSuccess())
		{
			/** @var Item $item */
			foreach ($result as $item)
			{
				// todo: in case of REST, ALL dates should be converted to the ISO string (write special exporter here, instead of Canonical)
				$data[] = $item->export($selectIsEmpty ? array() : '~'); // export ALL or only selected
			}
		}
		else
		{
			// clear DATA because we do not want error detail info sent to the client
			$this->errors->load($result->getErrors()->transform(array('DATA' => null)));
		}

		return array(
			'DATA' => $data,
		);
	}

	/**
	 * Get a list of tasks
	 * @deprecated
	 * @see \Bitrix\Tasks\Dispatcher\PublicAction\Task::find
	 *
	 * Access rights will be check into the CTasks::GetList()
	 */
	public function getList(array $order = array(), array $filter = array(), array $select = array(), array $parameters = array())
	{
		$result = array();

		$mgrResult = Manager\Task::getList($this->userId, array(
			'order' => $order,
			'legacyFilter' => $filter,
			'select' => $select,
		), array(
		   'PUBLIC_MODE' => true
		));

		$this->errors->load($mgrResult[ 'ERRORS' ]);

		if ($mgrResult[ 'ERRORS' ]->checkNoFatals())
		{
			$result = array(
				'DATA' => $mgrResult[ 'DATA' ],
				'CAN' => $mgrResult[ 'CAN' ]
			);
		}

		return $result;
	}

	/**
	 * Add a new task
	 */
	public function add(array $data, array $parameters = ['RETURN_DATA' => false])
	{
		$result = [];

		$newTask = TaskModel::createFromRequest($data);
		$oldTask = TaskModel::createNew($newTask->getGroupId());

		if (!(new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_SAVE, $oldTask, $newTask))
		{
			$this->addForbiddenError();

			return $result;
		}

		// todo: move to \Bitrix\Tasks\Item\Task
		$mgrResult = Manager\Task::add(
			$this->userId,
			$data,
			[
				'PUBLIC_MODE' => true,
				'ERRORS' => $this->errors,
				'RETURN_ENTITY' => ($parameters['RETURN_ENTITY'] ?? null),
			]
		);

		return [
			'ID' => $mgrResult['DATA']['ID'] ?? null,
			'DATA' => $mgrResult['DATA'] ?? null,
			'CAN' => $mgrResult['CAN'] ?? null,
		];
	}

	/**
	 * Update a task with some new data
	 */
	public function update($id, array $data, array $parameters = array())
	{
		$id = (int) $id;
		$result = [];

		$oldTask = TaskModel::createFromId($id);
		$newTask = clone $oldTask;

		if (
			isset($parameters['PLATFORM'])
			&& $parameters['PLATFORM'] === 'mobile'
		)
		{
			$data = $this->prepareMobileData($data, $id);
		}

		if (
			count($data) < 3
			&& count(array_intersect(array_keys($data), ['DEADLINE', 'END_DATE_PLAN', 'START_DATE_PLAN'])) === count($data)
		)
		{
			$isAccess = TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_DEADLINE, $id);
		}
		elseif (
			count($data) === 1
			&& array_key_exists('SE_RESPONSIBLE', $data)
		)
		{
			$members = $newTask->getMembers();
			$members[RoleDictionary::ROLE_RESPONSIBLE] = [];
			if (
				!empty($data['SE_RESPONSIBLE'])
				&& is_array($data['SE_RESPONSIBLE'])
			)
			{
				foreach ($data['SE_RESPONSIBLE'] as $responsible)
				{
					$members[RoleDictionary::ROLE_RESPONSIBLE][] = (int)$responsible['ID'];
				}
			}
			$newTask->setMembers($members);

			$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_CHANGE_RESPONSIBLE, $oldTask, $newTask);
		}
		elseif (
			count($data) === 1
			&& array_key_exists('SE_ACCOMPLICE', $data)
		)
		{
			$members = $newTask->getMembers();
			$members[RoleDictionary::ROLE_ACCOMPLICE] = [];
			if (
				!empty($data['SE_ACCOMPLICE'])
				&& is_array($data['SE_ACCOMPLICE'])
			)
			{
				foreach ($data['SE_ACCOMPLICE'] as $accomplice)
				{
					$members[RoleDictionary::ROLE_ACCOMPLICE][] = (int)$accomplice['ID'];
				}
			}
			$newTask->setMembers($members);

			$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES, $oldTask, $newTask);
		}
		elseif (
			count($data) === 1
			&& array_key_exists('SE_REMINDER', $data)
		)
		{
			$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_REMINDER, $oldTask, $data['SE_REMINDER']);
		}
		else
		{
			$newTask = TaskModel::createFromRequest($data);
			$isAccess = (new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_SAVE, $oldTask, $newTask);
		}

		if (!$isAccess)
		{
			$this->addForbiddenError();
			return $result;
		}

		if (!empty($data) && ($id = $this->checkTaskId($id)))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$mgrResult = Manager\Task::update(
				Util\User::getId(),
				$id,
				$data,
				[
					'PUBLIC_MODE' => true,
					'ERRORS' => $this->errors,
					'THROTTLE_MESSAGES' => ($parameters['THROTTLE_MESSAGES'] ?? null),
					// there also could be RETURN_CAN or RETURN_DATA, or both as RETURN_ENTITY
					'RETURN_ENTITY' => ($parameters['RETURN_ENTITY'] ?? null),
				]
			);

			$result['ID'] = $id;
			$result['DATA'] = $mgrResult['DATA'];
			$result['CAN'] = $mgrResult['CAN'];

			if (
				($parameters['RETURN_OPERATION_RESULT_DATA'] ?? null)
				&& $this->errors->checkNoFatals()
			)
			{
				$task = $mgrResult['TASK'];
				$result['OPERATION_RESULT'] = $task->getLastOperationResultData('UPDATE');
			}
		}

		return $result;
	}

	/**
	 * @param array $data
	 * @param int $taskId
	 * @return array
	 */
	private function prepareMobileData(array $data, int $taskId): array
	{
		$task = TaskRegistry::getInstance()->get($taskId, true);

		if (
			array_key_exists('DEADLINE', $data)
			&& (
				(empty($data['DEADLINE']) && is_null($task['DEADLINE']))
				|| ($task['DEADLINE'] && $data['DEADLINE'] === $task['DEADLINE']->toString())
			)
		)
		{
			unset($data['DEADLINE']);
		}

		if (array_key_exists('SE_RESPONSIBLE', $data))
		{
			$members = $task['MEMBER_LIST'];
			$responsibles = [];
			foreach ($members as $member)
			{
				if ($member['TYPE'] !== MemberTable::MEMBER_TYPE_RESPONSIBLE)
				{
					continue;
				}
				$responsibles[] = (int) $member['USER_ID'];
			}

			$dataResponsibles = [];
			foreach ($data['SE_RESPONSIBLE'] as $responsible)
			{
				$dataResponsibles[] = (int) $responsible['ID'];
			}

			if (empty(array_diff($responsibles, $dataResponsibles)))
			{
				unset($data['SE_RESPONSIBLE']);
			}
		}
		return $data;
	}

	/**
	 * Delete a task
	 *
	 * Access rights will be check into the CTaskItem
	 */
	public function delete($id, array $parameters = array())
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_REMOVE, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			$result['ID' ] = $id;

			// todo: move to \Bitrix\Tasks\Item\Task
			// this will ONLY delete tags, members, favorites, old depedences, old files, clear cache
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->delete();
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param $value
	 * @param array $parameters
	 * @return array
	 * @throws \CTaskAssertException
	 *
	 * Access rights will be check into the CTaskItem
	 */
	public function settaskcontrol($id, $value, array $parameters = [])
	{
		$result = [];

		$result['ID'] = $id;

		$task = \CTaskItem::getInstance($id, Util\User::getId());
		$task->update(['TASK_CONTROL' => $value]);

		return $result;
	}

	/**
	 * @param $id
	 * @param $newDeadline
	 * @param array $parameters
	 * @return array
	 * @throws \CTaskAssertException
	 */
	public function setdeadline($id, $newDeadline, array $parameters = array())
	{
		$result = array();

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_DEADLINE, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			$result['ID' ] = $id;

			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->update(array('DEADLINE' => $newDeadline));
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param $num
	 * @param $type
	 * @param array $parameters
	 * @return array
	 */
	public function substractDeadline($id, $num, $type, array $parameters = array())
	{
		$num *= -1;

		return $this->adjustDeadline($id, $num, $type, $parameters);
	}

	/**
	 * @param $id
	 * @param $num
	 * @param $type
	 * @param array $parameters
	 * @return array
	 * @throws \CTaskAssertException
	 */
	public function adjustDeadline($id, $num, $type, array $parameters = array())
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_DEADLINE, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			$result['ID' ] = $id;

			$task = \CTaskItem::getInstance($id, Util\User::getId());
			try
			{
				$arTask = $task->getData(false);
			}
			catch (\TasksException $e)
			{
				return [];
			}

			if (empty($arTask['DEADLINE']))
			{
				return $result;
			}

			$deadline = Util\Type\DateTime::createFromUserTime($arTask['DEADLINE']);
			$deadline = $deadline->add(($num < 0 ? '-' : '').abs($num).' '.$type);

			$task->update(array('DEADLINE' => $deadline));
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param array $parameters
	 * @return array
	 * @throws \CTaskAssertException
	 */
	public function addtofavorite($id, array $parameters = array())
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			$result['ID' ] = $id;

			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->addToFavorite();
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param array $parameters
	 * @return array
	 * @throws \CTaskAssertException
	 */
	public function removefromfavorite($id, array $parameters = array())
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			$result['ID' ] = $id;

			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->deleteFromFavorite();
		}

		return $result;
	}

	/**
	 * Delegates a task to a new responsible
	 */
	public function delegate($id, $userId)
	{
		$result = [];

		$oldTask = TaskModel::createFromId((int)$id);
		$newTask = clone $oldTask;
		$members = $newTask->getMembers();
		$members[RoleDictionary::ROLE_RESPONSIBLE] = [
			(int)$userId
		];
		$newTask->setMembers($members);

		if (!(new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_DELEGATE, $oldTask, $newTask))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			try
			{
				$task = \CTaskItem::getInstance($id, $this->userId);
				$task->delegate($userId);
			}
			catch (\TasksException $exception)
			{
				$result['ERRORS'][] = $exception->getMessageOrigin();
			}
		}

		return $result;
	}

	/**
	 * Check if a specified task is readable by the current user
	 */
	public function checkCanRead($id)
	{
		return [
			'READ' => TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$id)
		];
	}

	/**
	 * Start execution of a specified task
	 */
	public function start($id)
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_START, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->startExecution();
		}

		return $result;
	}

	/**
	 * Pause execution of a specified task
	 *
	 * Access rights will be check into the CTaskItem
	 */
	public function pause($id)
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_PAUSE, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->pauseExecution();
		}

		return $result;
	}

	/**
	 * Complete a specified task
	 */
	public function complete($id)
	{
		$id = (int)$id;
		$result = [];

		$task = TaskModel::createFromId((int)$id);
		if ($task->isClosed())
		{
			return $result;
		}

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_COMPLETE, $id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_COMPLETE_RESULT, $id))
		{
			$this->errors->add('RESULT_REQUIRED', Loc::getMessage('TASKS_ACTION_RESULT_REQUIRED'), false, ['ui' => 'notification']);
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->complete();
		}

		return $result;
	}

	/**
	 * Renew (switch to status "pending, await execution") a specified task
	 */
	public function renew($id)
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_RENEW, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->renew();
		}

		return $result;
	}

	/**
	 * Defer (put aside) a specified task
	 */
	public function defer($id)
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_DEFER, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->defer();
		}

		return $result;
	}

	/**
	 * Approve (confirm the result of) a specified task
	 */
	public function approve($id)
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_APPROVE, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->approve();
		}

		return $result;
	}

	/**
	 * Disapprove (reject the result of) a specified task
	 */
	public function disapprove($id)
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_DISAPPROVE, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, Util\User::getId());
			$task->disapprove();
		}

		return $result;
	}

	/**
	 * Become an auditor of a specified task
	 */
	public function enterAuditor($id)
	{
		$result = [];

		if (
			!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$id)
		)
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, $this->userId);
			$task->startWatch();
		}

		return $result;
	}

	/**
	 * Stop being an auditor of a specified task
	 */
	public function leaveAuditor($id)
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		if ($id = $this->checkTaskId($id))
		{
			// todo: move to \Bitrix\Tasks\Item\Task
			$task = \CTaskItem::getInstance($id, $this->userId);
			$task->stopWatch();
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param $auditorId
	 * @return array
	 * @throws \CTaskAssertException
	 */
	public function addAuditor($id, $auditorId)
	{
		$result = [];

		if (
			!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$id)
		)
		{
			$this->addForbiddenError();
			return $result;
		}

		$task = \CTaskItem::getInstance($id, $this->userId);
		try
		{
			$arTask = $task->getData(false);
		}
		catch (\TasksException $e)
		{
			return [];
		}
		$arTask['AUDITORS'][] = $auditorId;
		$task->update(array('AUDITORS' => $arTask['AUDITORS']));

		return $result;
	}

	/**
	 * @param $id
	 * @param $accompliceId
	 * @return array
	 * @throws \CTaskAssertException
	 */
	public function addAccomplice($id, $accompliceId)
	{
		$result = [];

		$oldTask = TaskModel::createFromId((int)$id);
		$newTask = clone $oldTask;
		$members = $newTask->getMembers();
		$members[RoleDictionary::ROLE_ACCOMPLICE][] = (int)$accompliceId;
		$newTask->setMembers($members);

		if (!(new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_CHANGE_ACCOMPLICES, $oldTask, $newTask))
		{
			$this->addForbiddenError();
			return $result;
		}

		$task = \CTaskItem::getInstance($id, $this->userId);
		try
		{
			$arTask = $task->getData(false);
		}
		catch (\TasksException $e)
		{
			return [];
		}

		$arTask['ACCOMPLICES'][] = $accompliceId;
		$task->update(array('ACCOMPLICES' => $arTask['ACCOMPLICES']));

		return $result;
	}

	/**
	 * @param $id
	 * @param $groupId
	 * @return array
	 * @throws \CTaskAssertException
	 */
	public function setGroup($id, $groupId)
	{
		$result = [];

		$oldTask = TaskModel::createFromId((int)$id);
		$newTask = clone $oldTask;
		$newTask->setGroupId((int)$groupId);

		if (!(new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_SAVE, $oldTask, $newTask))
		{
			$this->addForbiddenError();
			return $result;
		}

		$task = \CTaskItem::getInstance($id, Util\User::getId());
		$task->update(array('GROUP_ID' => $groupId));

		return $result;
	}

	/**
	 * @param $id
	 * @param $responsibleId
	 * @return array
	 * @throws \CTaskAssertException
	 */
	public function setResponsible($id, $responsibleId)
	{
		$result = [];

		$oldTask = TaskModel::createFromId((int)$id);
		$newTask = clone $oldTask;
		$members = $newTask->getMembers();
		$members[RoleDictionary::ROLE_RESPONSIBLE] = [
			(int)$responsibleId
		];
		$newTask->setMembers($members);

		if (!(new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_CHANGE_RESPONSIBLE, $oldTask, $newTask))
		{
			$this->addForbiddenError();
			return $result;
		}

		$task = \CTaskItem::getInstance($id, Util\User::getId());
		$task->update(array('RESPONSIBLE_ID' => $responsibleId));

		return $result;
	}

	/**
	 * @param $id
	 * @param $originatorId
	 * @return array
	 * @throws \CTaskAssertException
	 */
	public function setOriginator($id, $originatorId)
	{
		$result = [];

		$id = (int)$id;
		$originatorId = (int)$originatorId;
		if ($id <=0 || $originatorId <= 0)
		{
			$this->addForbiddenError();
			return $result;
		}

		$oldTask = TaskModel::createFromId($id);
		$newTask = clone $oldTask;

		$members = $newTask->getMembers();
		$members[RoleDictionary::ROLE_DIRECTOR] = [];
		$members[RoleDictionary::ROLE_DIRECTOR][] = $originatorId;
		$newTask->setMembers($members);

		if (!(new TaskAccessController($this->userId))->check(ActionDictionary::ACTION_TASK_CHANGE_DIRECTOR, $oldTask, $newTask))
		{
			$this->addForbiddenError();
			return $result;
		}

		$task = \CTaskItem::getInstance($id, Util\User::getId());
		$task->update(array('CREATED_BY' => $originatorId));

		return $result;
	}

	/**
	 * @param $id
	 * @return Util\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function mute($id)
	{
		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$id))
		{
			$this->addForbiddenError();

			$result = new Util\Result();
			$result->loadErrors($this->errors);
			return $result;
		}

		return UserOption::add($id, Util\User::getId(), UserOption\Option::MUTED);
	}

	public function unmute($id)
	{
		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$id))
		{
			$this->addForbiddenError();

			$result = new Util\Result();
			$result->loadErrors($this->errors);
			return $result;
		}

		return UserOption::delete($id, Util\User::getId(), UserOption\Option::MUTED);
	}

	public function pin($id, $groupId = 0)
	{
		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$id))
		{
			$this->addForbiddenError();
			$result = new Util\Result();
			$result->loadErrors($this->errors);
			return $result;
		}

		$option = UserOption\Option::PINNED;
		$groupId = (int)$groupId;
		if ($groupId)
		{
			$option = UserOption\Option::PINNED_IN_GROUP;
		}
		return UserOption::add($id, Util\User::getId(), $option);
	}

	public function unpin($id, $groupId = 0)
	{
		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$id))
		{
			$this->addForbiddenError();

			$result = new Util\Result();
			$result->loadErrors($this->errors);
			return $result;
		}

		$option = UserOption\Option::PINNED;
		$groupId = (int)$groupId;
		if ($groupId)
		{
			$option = UserOption\Option::PINNED_IN_GROUP;
		}
		return UserOption::delete($id, Util\User::getId(), $option);
	}

	public function ping($id): array
	{
		$result = [];

		if (!TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, (int)$id))
		{
			$this->addForbiddenError();
			return $result;
		}

		$userId = Util\User::getId();
		$task = \CTaskItem::getInstance($id, $userId);
		$taskData = $task->getData(false);

		if ($taskData)
		{
			$commentPoster = CommentPoster::getInstance($id, $userId);
			$commentPoster && $commentPoster->postCommentsOnTaskStatusPinged($taskData);

			\CTaskNotifications::sendPingStatusMessage($taskData, $userId);
		}

		return $result;
	}
}