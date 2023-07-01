<?php

namespace Bitrix\Tasks\Control\Handler;

use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\Emoji;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\Intranet\Department;
use Bitrix\Tasks\Internals\Helper\Task\Dependence;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Type\DateTime;

class TaskFieldHandler
{
	private $fields = [];
	private $taskId;
	private $taskData;
	private $userId;

	public function __construct(int $userId, array $fields, array $taskData = null)
	{
		$this->userId = $userId;
		$this->fields = $fields;
		$this->taskData = $taskData;

		$this->setTaskId();
	}

	/**
	 * @return $this
	 */
	public function prepareId(): self
	{
		unset($this->fields['ID']);
		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareGuid(): self
	{
		if ($this->taskId)
		{
			return $this;
		}

		$this->fields['GUID'] = Util::generateUUID();

		$res = TaskTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=GUID' => $this->fields['GUID'],
			],
			'limit' => 1
		]);
		$task = $res->fetch();

		if ($task)
		{
			throw new TaskFieldValidateException(Loc::getMessage('ERROR_TASKS_GUID_NON_UNIQUE'));
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareSiteId(): self
	{
		if (
			!isset($this->fields['SITE_ID'])
			&& !$this->taskId
		)
		{
			$this->fields['SITE_ID'] = SITE_ID;
		}
		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareIntegration(): self
	{
		if ($this->taskId)
		{
			return $this;
		}

		if (!array_key_exists('IM_CHAT_ID', $this->fields))
		{
			$this->fields['IM_CHAT_ID'] = 0;
		}

		if (!array_key_exists('IM_MESSAGE_ID', $this->fields))
		{
			$this->fields['IM_MESSAGE_ID'] = 0;
		}

		$this->fields['IM_CHAT_ID'] = (int) $this->fields['IM_CHAT_ID'];
		$this->fields['IM_MESSAGE_ID'] = (int) $this->fields['IM_MESSAGE_ID'];

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareGroupId(): self
	{
		if (
			!isset($this->fields['GROUP_ID'])
			&& !$this->taskId
		)
		{
			$this->fields['GROUP_ID'] = 0;
		}

		if (array_key_exists('GROUP_ID', $this->fields))
		{
			$this->fields['GROUP_ID'] = (int) $this->fields['GROUP_ID'];
		}

		if (
			$this->taskId
			&& isset($this->fields['GROUP_ID'])
			&& $this->fields['GROUP_ID']
			&& $this->fields['GROUP_ID'] !== (int) $this->taskData['GROUP_ID']
		)
		{
			$this->fields['STAGE_ID'] = 0;
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareDurationPlanFields(): self
	{
		$type = '';
		if (array_key_exists('DURATION_TYPE', $this->fields))
		{
			$type = (string) $this->fields['DURATION_TYPE'];
		}

		if (
			$this->taskId
			&& empty($type)
		)
		{
			$type = $this->taskData['DURATION_TYPE'];
		}

		$durationPlan = false;
		if (isset($this->fields['DURATION_PLAN_SECONDS']))
		{
			$durationPlan = $this->fields['DURATION_PLAN_SECONDS'];
		}
		elseif (isset($this->fields['DURATION_PLAN']))
		{
			$durationPlan = $this->convertDurationToSeconds((int) $this->fields['DURATION_PLAN'], $type);
		}

		if ($durationPlan !== false)
		{
			$this->fields['DURATION_PLAN'] = $durationPlan;
			unset($this->fields['DURATION_PLAN_SECONDS']);
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareCreatedBy(): self
	{
		if (
			!$this->taskId
			&& (
				!isset($this->fields['CREATED_BY'])
				|| !$this->fields['CREATED_BY']
			)
		)
		{
			$this->fields['CREATED_BY'] = $this->userId;
		}
		if (array_key_exists('CREATED_BY', $this->fields))
		{
			$this->fields['CREATED_BY'] = (int) $this->fields['CREATED_BY'];
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareChangedBy(): self
	{
		if ($this->taskId)
		{
			if (!isset($this->fields['CHANGED_BY']))
			{
				$this->fields['CHANGED_BY'] = $this->userId;
			}
			if (!isset($this->fields['CHANGED_DATE']))
			{
				$this->fields['CHANGED_DATE'] = \Bitrix\Tasks\UI::formatDateTime(Util\User::getTime());
			}

			return $this;
		}

		$nowDateTimeString = \Bitrix\Tasks\UI::formatDateTime(Util\User::getTime());

		$this->fields['ACTIVITY_DATE'] = $nowDateTimeString;

		if (isset($fields['CHANGED_BY']))
		{
			return $this;
		}

		$this->fields['CHANGED_BY'] = $this->fields['CREATED_BY'];

		if (!isset($this->fields['CHANGED_DATE']))
		{
			$this->fields['CHANGED_DATE'] = $nowDateTimeString;
		}

		return $this;
	}

	/**
	 * @return $this
	 * @throws TaskFieldValidateException
	 */
	public function prepareTitle(): self
	{
		if (
			$this->taskId
			&& !array_key_exists('TITLE', $this->fields)
		)
		{
			return $this;
		}

		$title = trim((string) $this->fields['TITLE']);
		if ($title === '')
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_TITLE'));
		}

		$title = Emoji::encode(mb_substr($title, 0, 250));
		$this->fields['TITLE'] = $title;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareDescription(): self
	{
		if (
			array_key_exists('DESCRIPTION', $this->fields)
			&& $this->fields['DESCRIPTION'] !== ''
		)
		{
			$this->fields['DESCRIPTION'] = Emoji::encode($this->fields['DESCRIPTION']);
		}

		return $this;
	}

	/**
	 * @return $this
	 * @throws TaskFieldValidateException
	 */
	public function prepareStatus(): self
	{
		if (!array_key_exists('STATUS', $this->fields))
		{
			return $this;
		}
		$this->fields['STATUS'] = (int) $this->fields['STATUS'];

		if ($this->fields['STATUS'] === \CTasks::STATE_NEW)
		{
			$this->fields['STATUS'] = \CTasks::STATE_PENDING;
		}

		$validValues = [
			\CTasks::STATE_PENDING,
			\CTasks::STATE_IN_PROGRESS,
			\CTasks::STATE_SUPPOSEDLY_COMPLETED,
			\CTasks::STATE_COMPLETED,
			\CTasks::STATE_DEFERRED,
		];

		if (!in_array($this->fields['STATUS'], $validValues))
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_INCORRECT_STATUS'));
		}

		$nowDateTimeString = \Bitrix\Tasks\UI::formatDateTime(Util\User::getTime());

		if (!isset($this->fields['STATUS_CHANGED_DATE']))
		{
			$this->fields['STATUS_CHANGED_DATE'] = $nowDateTimeString;
		}

		if (
			!$this->taskId
			|| (int) $this->taskData['STATUS'] === $this->fields['STATUS']
		)
		{
			return $this;
		}

		if (!array_key_exists('STATUS_CHANGED_BY', $this->fields))
		{
			$this->fields['STATUS_CHANGED_BY'] = $this->userId;
		}
		if (!array_key_exists('STATUS_CHANGED_DATE', $this->fields))
		{
			$this->fields['STATUS_CHANGED_DATE'] = $nowDateTimeString;
		}

		if (
			$this->fields['STATUS'] === \CTasks::STATE_COMPLETED
			|| $this->fields['STATUS'] === \CTasks::STATE_SUPPOSEDLY_COMPLETED
		)
		{
			$this->fields['CLOSED_BY'] = $this->userId;
			$this->fields['CLOSED_DATE'] = $nowDateTimeString;
		}
		else
		{
			$this->fields['CLOSED_BY'] = false;
			$this->fields['CLOSED_DATE'] = false;

			if (
				$this->fields['STATUS'] === \CTasks::STATE_IN_PROGRESS
				&& !array_key_exists('DATE_START', $this->fields)
			)
			{
				$this->fields['DATE_START'] = $nowDateTimeString;
			}
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function preparePriority(): self
	{
		$validValues = [
			\CTasks::PRIORITY_LOW,
			\CTasks::PRIORITY_AVERAGE,
			\CTasks::PRIORITY_HIGH,
		];

		if (
			$this->taskId
			&& !array_key_exists('PRIORITY', $this->fields)
		)
		{
			return $this;
		}

		if (
			!$this->taskId
			&& !array_key_exists('PRIORITY', $this->fields)
		)
		{
			$this->fields['PRIORITY'] = \CTasks::PRIORITY_AVERAGE;
		}

		$this->fields['PRIORITY'] = (int) $this->fields['PRIORITY'];
		if (!in_array($this->fields['PRIORITY'], $validValues))
		{
			$this->fields['PRIORITY'] = \CTasks::PRIORITY_AVERAGE;
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareMark(): self
	{
		$validValues = [
			\CTasks::MARK_NEGATIVE,
			\CTasks::MARK_POSITIVE,
			'',
		];

		if (
			array_key_exists('MARK', $this->fields)
			&& !in_array($this->fields['MARK'], $validValues)
		)
		{
			unset($this->fields['MARK']);
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareFlags(): self
	{
		$flags = [
			'ALLOW_CHANGE_DEADLINE',
			'TASK_CONTROL',
			'ADD_IN_REPORT',
			'MATCH_WORK_TIME',
			'REPLICATE',
		];

		foreach ($flags as $flag)
		{
			if (
				$this->taskId
				&& !array_key_exists($flag, $this->fields)
			)
			{
				continue;
			}

			if (
				!array_key_exists($flag, $this->fields)
				|| $this->fields[$flag] !== 'Y'
			)
			{
				$this->fields[$flag] = false;
			}
			else
			{
				$this->fields[$flag] = true;
			}
		}

		return $this;
	}

	/**
	 * @return $this
	 * @throws TaskFieldValidateException
	 */
	public function prepareParents(): self
	{
		if (!array_key_exists('PARENT_ID', $this->fields))
		{
			return $this;
		}

		$parentId = (int)$this->fields['PARENT_ID'];
		if (!$parentId)
		{
			$this->fields['PARENT_ID'] = false;

			return $this;
		}
		$this->fields['PARENT_ID'] = $parentId;

		$parentTask = (TaskRegistry::getInstance())->getObject($parentId);
		if (
			!$parentTask
			|| $parentTask->isDeleted()
			|| !TaskAccessController::can($this->userId, ActionDictionary::ACTION_TASK_READ, $parentId)
		)
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_PARENT_ID'));
		}

		if (!$this->taskId)
		{
			return $this;
		}

		if (ProjectDependenceTable::checkLinkExists($this->taskId, $parentId, ['BIDIRECTIONAL' => true]))
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_IS_LINKED_SET_PARENT'));
		}

		$result = Dependence::canAttach($this->taskId, $parentId);
		if (!$result->isSuccess() && ($errors = $result->getErrors()))
		{
			$messages = $errors->getMessages();
			throw new TaskFieldValidateException(array_shift($messages));
		}

		return $this;
	}

	/**
	 * @return $this
	 * @throws TaskFieldValidateException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function prepareDates(): self
	{
		$this->checkDatesInProject();

		$startDate = ($this->fields['START_DATE_PLAN'] ?? null);
		$endDate = ($this->fields['END_DATE_PLAN'] ?? null);

		// you are not allowed to clear up END_DATE_PLAN while the task is linked
		if (
			$this->taskId
			&& isset($endDate)
			&& (string)$endDate === ''
			&& ProjectDependenceTable::checkItemLinked($this->taskId)
		)
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_IS_LINKED_END_DATE_PLAN_REMOVE'));
		}

		if (
			isset($startDate, $endDate)
			&& $startDate !== ''
			&& $endDate !== ''
		)
		{
			$startDateTs = MakeTimeStamp($startDate);
			$endDateTs = MakeTimeStamp($endDate);

			if ($startDateTs > 0 && $endDateTs > 0)
			{
				if ($endDateTs < $startDateTs)
				{
					throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_PLAN_DATES'));
				}
				if ($endDateTs - $startDateTs > \CTasks::MAX_INT)
				{
					throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_DURATION'));
				}
			}
		}

		if ($this->taskId)
		{
			return $this;
		}

		if (!isset($this->fields['CREATED_DATE']))
		{
			$this->fields['CREATED_DATE'] = \Bitrix\Tasks\UI::formatDateTime(Util\User::getTime());
		}

		if (
			isset($this->fields['DEADLINE'])
			&& (string) $this->fields['DEADLINE'] != ''
			&& isset($this->fields['MATCH_WORK_TIME'])
			&& $this->fields['MATCH_WORK_TIME'] == 'Y'
		)
		{
			$this->fields['DEADLINE'] = $this->getDeadlineMatchWorkTime($this->fields['DEADLINE']);
		}

		return $this;
	}

	/**
	 * @return $this
	 * @throws TaskFieldValidateException
	 */
	public function prepareMembers(): self
	{
		if (!$this->taskId)
		{
			if (
				!array_key_exists('CREATED_BY', $this->fields)
				|| $this->fields['CREATED_BY'] < 1
			)
			{
				throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_CREATED_BY'));
			}

			if (!array_key_exists('RESPONSIBLE_ID', $this->fields))
			{
				throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_RESPONSIBLE_ID'));
			}
		}
		elseif (
			array_key_exists('CREATED_BY', $this->fields)
			&& $this->fields['CREATED_BY'] < 1
		)
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_CREATED_BY'));
		}


		if (array_key_exists('RESPONSIBLE_ID', $this->fields))
		{
			$newResponsibleId = (int) $this->fields['RESPONSIBLE_ID'];

			$userResult = \CUser::GetList(
				'id',
				'asc',
				['ID_EQUAL_EXACT' => $newResponsibleId],
				[
					'FIELDS' => ['ID'],
					'SELECT' => ['UF_DEPARTMENT'],
				]
			);
			$user = $userResult->Fetch();
			if (!$user)
			{
				throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_RESPONSIBLE_ID_EX'));
			}

			$currentResponsible = 0;

			if ($this->taskId)
			{
				$task = (TaskRegistry::getInstance())->getObject($this->taskId);
				if (
					$task
					&& !$task->isDeleted()
				)
				{
					$currentResponsible = $task->getResponsibleId();
				}
			}

			// new task or responsible changed
			if (
				!$this->taskId
				|| ($currentResponsible && $currentResponsible !== $newResponsibleId)
			)
			{
				$subordinateDepartments = Department::getSubordinateIds(
					($this->fields['CREATED_BY'] ?? null),
					true
				);

				$userDepartment = $user['UF_DEPARTMENT'];
				$userDepartment = (is_array($userDepartment) ? $userDepartment : [$userDepartment]);

				$isSubordinate = (count(array_intersect($subordinateDepartments, $userDepartment)) > 0);

				if (
					!array_key_exists('STATUS', $this->fields)
					|| !$this->fields['STATUS']
				)
				{
					$this->fields['STATUS'] = \CTasks::STATE_PENDING;
				}

				if (!$isSubordinate)
				{
					$this->fields['ADD_IN_REPORT'] = 'N';
				}

				$this->fields['DECLINE_REASON'] = false;
			}
		}

		$this->castMembers('ACCOMPLICES');
		$this->castMembers('AUDITORS');

		return $this;
	}

	/**
	 * @return $this
	 * @throws TaskFieldValidateException
	 */
	public function prepareDependencies(): self
	{
		if (
			$this->taskId
			&& is_array($this->fields['DEPENDS_ON'] ?? null)
			&& in_array($this->taskId, $this->fields['DEPENDS_ON'])
		)
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_DEPENDS_ON_SELF'));
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareOutlook(): self
	{
		if (!$this->taskId)
		{
			$this->fields['OUTLOOK_VERSION'] = 1;
		}
		else
		{
			$this->fields['OUTLOOK_VERSION'] = ($this->taskData['OUTLOOK_VERSION'] ?: 1) + 1;
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareTags(): self
	{
		if (
			$this->taskId
			&& !array_key_exists('TAGS', $this->fields)
		)
		{
			return $this;
		}

		if (empty($this->fields['TAGS']))
		{
			$this->fields['TAGS'] = [];
			return $this;
		}

		if (
			!$this->taskId
			&& !isset($this->fields['TAGS'])
		)
		{
			$this->fields['TAGS'] = [];
		}

		if (is_string($this->fields['TAGS']))
		{
			$this->fields['TAGS'] = explode(',', $this->fields['TAGS']);
		}

		if (!is_array($this->fields['TAGS']))
		{
			$this->fields['TAGS'] = [$this->fields['TAGS']];
		}

		return $this;
	}

	/**
	 * @return array
	 */
	public function getFields(): array
	{
		return $this->fields;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getFieldsToDb(): array
	{
		$fields = $this->fields;

		$tableFields = TaskTable::getEntity()->getFields();

		foreach ($fields as $fieldName => $value)
		{
			if (!array_key_exists($fieldName, $tableFields))
			{
				unset($fields[$fieldName]);
				continue;
			}

			if (preg_match('/^UF_/', $fieldName))
			{
				unset($fields[$fieldName]);
				continue;
			}

			if (
				$tableFields[$fieldName] instanceof DatetimeField
				&& !empty($value)
			)
			{
				$fields[$fieldName] = \Bitrix\Main\Type\DateTime::createFromUserTime($value);
			}
		}

		return $fields;
	}

	/**
	 * @return bool
	 */
	public function isDatesChanged(): bool
	{
		if (!$this->taskData)
		{
			return false;
		}

		if (
			!array_key_exists('START_DATE_PLAN', $this->fields)
			&& !array_key_exists('END_DATE_PLAN', $this->fields)
		)
		{
			return false;
		}

		if ((string)$this->taskData['START_DATE_PLAN'] !== (string)$this->fields['START_DATE_PLAN'])
		{
			return true;
		}

		if ((string)$this->taskData['END_DATE_PLAN'] !== (string)$this->fields['END_DATE_PLAN'])
		{
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isParentChanged(): bool
	{
		if (!$this->taskData)
		{
			return false;
		}

		if (!array_key_exists('PARENT_ID', $this->fields))
		{
			return false;
		}
		$this->fields['PARENT_ID'] = (int) $this->fields['PARENT_ID'];
		$this->taskData['PARENT_ID'] = (int) $this->taskData['PARENT_ID'];

		if ($this->fields['PARENT_ID'] === $this->taskData['PARENT_ID'])
		{
			return false;
		}

		/**
		 * Occurs when user does not know anything about main task but is trying to change its sub task.
		 * This method returns true in that case, so we should not change parent.
		 */
		try
		{
			if (Util\User::isSuper($this->userId))
			{
				return true;
			}

			if (!$this->fields['PARENT_ID'] && $this->taskData['PARENT_ID'])
			{
				try
				{
					$parentTask = new \CTaskItem($this->taskData['PARENT_ID'], $this->userId);
					$parentTask->getData(false, ['select' => ['ID'], 'bSkipExtraData' => true]);
				}
					/** @noinspection PhpDeprecationInspection */
				catch (\TasksException | \CTaskAssertException $e)
				{
					/** @noinspection PhpDeprecationInspection */
					if ($e->getCode() == \TasksException::TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE)
					{
						return false;
					}
				}
			}

			return true;
		}
		catch (\Exception $exception)
		{
			return true;
		}
	}

	/**
	 * @return bool
	 */
	public function isFollowDates(): bool
	{
		if (
			!array_key_exists('SE_PARAMETER', $this->fields)
			|| !is_array($this->fields['SE_PARAMETER'])
		)
		{
			return false;
		}

		foreach ($this->fields['SE_PARAMETER'] as $parameter)
		{
			if (
				(int)$parameter['CODE'] === ParameterTable::PARAM_SUBTASKS_TIME
				&& $parameter['VALUE'] === 'Y'
			)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param int $value
	 * @param string $type
	 * @return int
	 */
	private function convertDurationToSeconds(int $value, string $type): int
	{
		if($type === \CTasks::TIME_UNIT_TYPE_HOUR)
		{
			// hours to seconds
			return $value * 3600;
		}
		elseif($type === \CTasks::TIME_UNIT_TYPE_DAY || $type === '')
		{
			// days to seconds
			return $value * 86400;
		}

		return $value;
	}

	/**
	 * @return void
	 * @throws TaskFieldValidateException
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function checkDatesInProject()
	{
		$groupId = 0;

		if (array_key_exists('GROUP_ID', $this->fields) && (int) $this->fields['GROUP_ID'] > 0)
		{
			$groupId = (int) $this->fields['GROUP_ID'];
		}
		elseif ($this->taskId)
		{
			$task = (TaskRegistry::getInstance())->getObject($this->taskId);
			if (
				$task
				&& !$task->isDeleted()
			)
			{
				$groupId = $task->getGroupId();
			}
		}

		if (!$groupId)
		{
			return;
		}

		if (
			Loader::includeModule('socialnetwork')
			&& ($group = \CSocNetGroup::getById($groupId))
			&& ($group['PROJECT'] === 'Y')
			&& ($group['PROJECT_DATE_START'] || $group['PROJECT_DATE_FINISH'])
		)
		{
			$projectStartDate = DateTime::createFrom($group['PROJECT_DATE_START']);
			$projectFinishDate = DateTime::createFrom($group['PROJECT_DATE_FINISH']);

			if ($projectFinishDate)
			{
				$projectFinishDate->addSecond(86399);
			}

			$deadline = null;
			$endDatePlan = null;
			$startDatePlan = null;

			if (isset($this->fields['DEADLINE']) && $this->fields['DEADLINE'])
			{
				$deadline = DateTime::createFrom($this->fields['DEADLINE']);
			}
			if (isset($this->fields['END_DATE_PLAN']) && $this->fields['END_DATE_PLAN'])
			{
				$endDatePlan = DateTime::createFrom($this->fields['END_DATE_PLAN']);
			}
			if (isset($this->fields['START_DATE_PLAN']) && $this->fields['START_DATE_PLAN'])
			{
				$startDatePlan = DateTime::createFrom($this->fields['START_DATE_PLAN']);
			}

			if ($deadline && !$deadline->checkInRange($projectStartDate, $projectFinishDate))
			{
				throw new TaskFieldValidateException(Loc::getMessage('TASKS_DEADLINE_OUT_OF_PROJECT_RANGE'));
			}

			if ($endDatePlan && !$endDatePlan->checkInRange($projectStartDate, $projectFinishDate))
			{
				throw new TaskFieldValidateException(Loc::getMessage('TASKS_PLAN_DATE_END_OUT_OF_PROJECT_RANGE'));
			}

			if ($startDatePlan && !$startDatePlan->checkInRange($projectStartDate, $projectFinishDate))
			{
				throw new TaskFieldValidateException(Loc::getMessage('TASKS_PLAN_DATE_START_OUT_OF_PROJECT_RANGE'));
			}
		}
	}

	/**
	 * @param string $fieldName
	 * @return void
	 */
	private function castMembers(string $fieldName)
	{
		if (
			!array_key_exists($fieldName, $this->fields)
			&& $this->taskId
		)
		{
			return;
		}

		if (
			!array_key_exists($fieldName, $this->fields)
			|| !is_array($this->fields[$fieldName])
		)
		{
			$this->fields[$fieldName] = [];
		}

		$members = array_map(function($memberId) {
			return (int) $memberId;
		}, $this->fields[$fieldName]);

		$this->fields[$fieldName] = array_unique($members);
	}

	/**
	 * Check if deadline is matching work time.
	 * Returns closest work time if not.
	 *
	 * @param $deadline
	 *
	 * @return DateTime
	 */
	private function getDeadlineMatchWorkTime($deadline)
	{
		$resultDeadline = DateTime::createFromUserTimeGmt($deadline);

		$calendar = new Util\Calendar();
		if (!$calendar->isWorkTime($resultDeadline))
		{
			$resultDeadline = $calendar->getClosestWorkTime($resultDeadline);
		}

		$resultDeadline = $resultDeadline->convertToLocalTime()->getTimestamp();
		$resultDeadline = DateTime::createFromTimestamp($resultDeadline - Util\User::getTimeZoneOffsetCurrentUser());

		return $resultDeadline;
	}

	/**
	 * @return void
	 */
	private function setTaskId()
	{
		if (
			$this->taskData
			&& array_key_exists('ID', $this->taskData)
		)
		{
			$this->taskId = (int) $this->taskData['ID'];
		}
	}
}