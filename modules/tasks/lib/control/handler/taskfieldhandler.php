<?php

namespace Bitrix\Tasks\Control\Handler;

use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\Text\Emoji;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Control\Task\Exception\FlowTaskException;
use Bitrix\Tasks\Flow\Control\Task\Field\FlowFieldHandler;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Integration\Intranet\Department;
use Bitrix\Tasks\Integration\Extranet;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Integration\SocialNetwork\Collab\Provider\CollabDefaultProvider;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Internals\Helper\Task\Dependence;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\Mark;
use Bitrix\Tasks\Internals\Task\ParameterTable;
use Bitrix\Tasks\Internals\Task\Priority;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;
use Bitrix\Tasks\Internals\Task\RegularParametersObject;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\Task\Template\ReplicateParamsCorrector;
use Bitrix\Tasks\Internals\Task\TimeUnitType;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Replication\Task\Regularity\Exception\RegularityException;
use Bitrix\Tasks\Replication\Task\Regularity\Time\Service\DeadlineRegularityService;
use Bitrix\Tasks\Replication\Repository\TaskRepository;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\User;
use CTimeZone;
use Throwable;

class TaskFieldHandler
{
	private $taskId;
	private array $skipTimeZoneFields = [];

	public function __construct(private int $userId, private array $fields = [], private ?array $taskData = null)
	{
		$this->setTaskId();
	}

	public function skipTimeZoneFields(string ...$fields): static
	{
		$this->skipTimeZoneFields = $fields;
		return $this;
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
	 * @throws TaskFieldValidateException
	 */
	public function prepareFlow(): self
	{
		if ($this->skipModifyByFlow())
		{
			return $this;
		}

		$flowId = (int)($this->fields['FLOW_ID'] ?? $this->taskData['FLOW_ID']);
		$handler = new FlowFieldHandler($flowId, $this->userId);

		try
		{
			$handler->modify($this->fields, $this->taskData);
		}
		catch (FlowTaskException|FlowNotFoundException $e)
		{
			throw new TaskFieldValidateException($e->getMessage());
		}

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
			'limit' => 1,
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

		$this->prepareCollab();

		if (
			!Util\User::isSuper($this->userId)
			&& Extranet\User::isExtranet($this->userId)
			&& isset($this->fields['GROUP_ID'])
			&& !isset($this->taskData['GROUP_ID'])
			&& (int) $this->fields['GROUP_ID'] === 0
		)
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_GROUP'));
		}

		if (
			!Util\User::isSuper($this->userId)
			&& Extranet\User::isExtranet($this->userId)
			&& isset($this->taskData['GROUP_ID'])
			&& (int) $this->taskData['GROUP_ID'] !== 0
			&& isset($this->fields['GROUP_ID'])
			&& (int) $this->fields['GROUP_ID'] === 0
		)
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_GROUP'));
		}

		if (
			$this->taskId
			&& isset($this->fields['GROUP_ID'])
			&& $this->fields['GROUP_ID'] !== (int) $this->taskData['GROUP_ID']
		)
		{
			if ($this->fields['GROUP_ID'])
			{
				$this->fields['STAGE_ID'] = 0;
			}

			if (
				isset($this->taskData['FLOW_ID'])
				&& !isset($this->fields['FLOW_ID'])
			)
			{
				$this->fields['FLOW_ID'] = 0;
			}
		}

		return $this;
	}

	private function prepareCollab(): void
	{
		$isCollaber = Extranet\User::isCollaber($this->userId);
		if (!$isCollaber)
		{
			return;
		}

		$isGroupAlreadyFilled = isset($this->taskData['GROUP_ID']) && (int)$this->taskData['GROUP_ID'] !== 0;
		$isGroupUpdateOnEmpty = isset($this->fields['GROUP_ID']) && (int)$this->fields['GROUP_ID'] === 0;
		$isGroupUpdateOnCorrect = isset($this->fields['GROUP_ID']) && (int)$this->fields['GROUP_ID'] !== 0;

		if (
			($isGroupAlreadyFilled && !$isGroupUpdateOnEmpty)
			|| $isGroupUpdateOnCorrect
		)
		{
			return;
		}

		$defaultCollab = CollabDefaultProvider::getInstance()?->getCollab($this->userId);
		$defaultCollabId = $defaultCollab?->getId();
		if ($defaultCollabId === null)
		{
			return;
		}

		if (Group::can($defaultCollabId, Group::ACTION_CREATE_TASKS, $this->userId))
		{
			$this->fields['GROUP_ID'] = $defaultCollabId;
		}
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

		if (!isset($this->fields['ACTIVITY_DATE']))
		{
			$this->fields['ACTIVITY_DATE'] = $nowDateTimeString;
		}

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

		if ($this->fields['STATUS'] === Status::NEW)
		{
			$this->fields['STATUS'] = Status::PENDING;
		}

		$validValues = [
			Status::PENDING,
			Status::IN_PROGRESS,
			Status::SUPPOSEDLY_COMPLETED,
			Status::COMPLETED,
			Status::DEFERRED,
		];

		if (!in_array($this->fields['STATUS'], $validValues, true))
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
			$this->fields['STATUS'] === Status::COMPLETED
			|| $this->fields['STATUS'] === Status::SUPPOSEDLY_COMPLETED
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
				$this->fields['STATUS'] === Status::IN_PROGRESS
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
		$validValues = array_values(Priority::getAll());

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
			$this->fields['PRIORITY'] = Priority::AVERAGE;
		}

		$this->fields['PRIORITY'] = (int) $this->fields['PRIORITY'];
		if (!in_array($this->fields['PRIORITY'], $validValues))
		{
			$this->fields['PRIORITY'] = Priority::AVERAGE;
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function prepareMark(): self
	{
		$validValues = array_values(Mark::getAll());

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
			'IS_REGULAR',
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
				|| ($this->fields[$flag] !== 'Y' && $this->fields[$flag] !== true)
			)
			{
				$this->fields[$flag] = false;
			}
			else
			{
				$this->fields[$flag] = true;
			}
		}

		return $this->prepareReplication();
	}

	/**
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

		$parentTask = TaskRegistry::getInstance()->getObject($parentId);
		if (is_null($parentTask) || $parentTask->isDeleted())
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
	 * @throws LoaderException
	 */
	public function prepareDates(): self
	{
		$this->prepareDeadLine();
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
			&& !isset($this->fields['FLOW_ID']) // skip, because the deadline has already been set
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
				throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_ASSIGNEE_ID'));
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
				throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_ASSIGNEE_EX'));
			}

			if (
				!Util\User::isSuper($this->userId)
				&& Extranet\User::isExtranet($this->userId)
				&& $newResponsibleId !== $this->userId
			)
			{
				if (isset($this->fields['GROUP_ID']))
				{
					if (
						(int) $this->fields['GROUP_ID'] !== 0
						&& isset($this->taskData['GROUP_ID'])
						&& isset($this->taskData['RESPONSIBLE_ID'])
						&& $newResponsibleId !== (int) $this->taskData['RESPONSIBLE_ID']
					)
					{
						$responsibleRoleInGroup = SocialNetwork\User::getUserRole($newResponsibleId, [$this->fields['GROUP_ID']]);

						if (!$responsibleRoleInGroup[$this->fields['GROUP_ID']])
						{
							throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_ASSIGNEE_IN_GROUP'));
						}
					}

					if (
						(int) $this->fields['GROUP_ID'] !== 0
						&& !isset($this->taskData['GROUP_ID'])
					)
					{
						$responsibleRoleInGroup = SocialNetwork\User::getUserRole($newResponsibleId, [$this->fields['GROUP_ID']]);

						if (!$responsibleRoleInGroup[$this->fields['GROUP_ID']])
						{
							throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_ASSIGNEE_IN_GROUP'));
						}
					}
				}
				else
				{
					if (
						isset($this->taskData['GROUP_ID'])
						&& (int) $this->taskData['GROUP_ID'] !== 0
						&& !isset($this->fields['GROUP_ID'])
					)
					{
						$responsibleRoleInGroup = SocialNetwork\User::getUserRole($newResponsibleId, [$this->taskData['GROUP_ID']]);

						if (!$responsibleRoleInGroup[$this->taskData['GROUP_ID']])
						{
							throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_ASSIGNEE_IN_GROUP'));
						}
					}
				}
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
					$this->fields['STATUS'] = Status::PENDING;
				}

				if (!$isSubordinate)
				{
					$this->fields['ADD_IN_REPORT'] = 'N';
				}

				$this->fields['DECLINE_REASON'] = false;
			}
		}

		if (
			!Util\User::isSuper($this->userId)
			&& !array_key_exists('RESPONSIBLE_ID', $this->fields)
			&& Extranet\User::isExtranet($this->userId)
			&& array_key_exists('GROUP_ID', $this->fields)
			&& (int) $this->fields['GROUP_ID'] !== 0
			&& array_key_exists('RESPONSIBLE_ID', $this->taskData)
		)
		{
			$responsibleRoleInGroup = SocialNetwork\User::getUserRole($this->taskData['RESPONSIBLE_ID'], [$this->fields['GROUP_ID']]);

			if (!$responsibleRoleInGroup[$this->fields['GROUP_ID']])
			{
				throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_ASSIGNEE_IN_GROUP'));
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
				$this->isTimeZoneSkip($fieldName) && CTimeZone::Disable();

				try
				{
					$fields[$fieldName] = \Bitrix\Main\Type\DateTime::createFromUserTime($value);
				}
				catch (Throwable)
				{
					throw new ObjectException('Incorrect date/time');
				}
				finally
				{
					$this->isTimeZoneSkip($fieldName) && CTimeZone::Enable();
				}
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
				is_array($parameter)
				&& (int)$parameter['CODE'] === ParameterTable::PARAM_SUBTASKS_TIME
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
		if($type === TimeUnitType::HOUR)
		{
			// hours to seconds
			return $value * 3600;
		}
		elseif($type === TimeUnitType::DAY || $type === '')
		{
			// days to seconds
			return $value * 86400;
		}

		return $value;
	}

	/**
	 * @return void
	 * @throws TaskFieldValidateException
	 * @throws LoaderException
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

	/**
	 * @throws TaskFieldValidateException
	 */
	private function prepareDeadLine(): void
	{
		if ($this->isNewTask() || $this->isRegularTask())
		{
			if (is_null($this->fields['IS_REGULAR'] ?? null))
			{
				return;
			}

			$regularParams = $this->fields['REGULAR_PARAMS'] ?? null;
			if (is_null($regularParams))
			{
				return;
			}

			$regularity = RegularParametersObject::createFromParams($regularParams);

			$taskFields = $this->fields;
			if ($this->isNewTask())
			{
				$taskFields['ID'] = 0;
			}

			$task = TaskObject::wakeUpObject($taskFields);
			$task->setRegular($regularity);

			$repository = new TaskRepository(0);
			$repository->inject($task);

			$deadlineService = new DeadlineRegularityService($repository);
			if (($deadlineService->getDeadlineOffsetInDays()) <= 0)
			{
				return;
			}

			try
			{
				$this->fields['DEADLINE'] = $deadlineService->getRecalculatedDeadline()->toString();
			}
			catch (RegularityException $exception)
			{
				throw new TaskFieldValidateException($exception->getMessage());
			}
		}
	}

	private function prepareReplication(): self
	{
		$isRegular = $this->fields['IS_REGULAR'] ?? false;
		$isReplication = $this->fields['REPLICATE'] ?? false;
		if ($isRegular && $isReplication)
		{
			$this->fields['REPLICATE'] = false;
		}

		return $this;
	}

	public function prepareRegularParams(): static
	{
		$isRegular = $this->fields['IS_REGULAR'] ?? false;
		if (!$isRegular)
		{
			return $this;
		}

		$regularParams = $this->fields['REGULAR_PARAMS'] ?? null;
		if (is_null($regularParams))
		{
			return $this;
		}

		$userTime = $regularParams['TIME'];
		$userOffset = User::getTimeZoneOffset($this->userId);
		$userStartDate = MakeTimeStamp($regularParams['START_DATE']);
		$userEndDate = MakeTimeStamp($regularParams['END_DATE']);

		$this->fields['REGULAR_PARAMS']['TIME'] = ReplicateParamsCorrector::correctTime($userTime, $userOffset);
		$this->fields['REGULAR_PARAMS']['START_DATE'] = ReplicateParamsCorrector::correctStartDate($userTime, $userStartDate, $userOffset);
		$this->fields['REGULAR_PARAMS']['END_DATE'] = ReplicateParamsCorrector::correctEndDate($userTime, $userEndDate, $userOffset);

		return $this;
	}

	private function isNewTask(): bool
	{
		return empty($this->taskData);
	}

	private function isRegularTask(): bool
	{
		return ($this->taskData['IS_REGULAR'] ?? null) === 'Y'
			&& isset($this->fields['REGULAR_PARAMS']);
	}

	private function isTimeZoneSkip(string $field): bool
	{
		return in_array($field, $this->skipTimeZoneFields, true);
	}

	private function isExistingTask(): bool
	{
		return isset($this->taskId) && $this->taskId > 0;
	}

	protected function skipModifyByFlow(): bool
	{
		if (!FlowFeature::isFeatureEnabled() || !FlowFeature::isOn())
		{
			return true;
		}

		if (isset($this->fields['FLOW_ID']) && (int)$this->fields['FLOW_ID'] === 0)
		{
			return true;
		}

		return empty($this->taskData['FLOW_ID']) && empty($this->fields['FLOW_ID']);
	}
}
