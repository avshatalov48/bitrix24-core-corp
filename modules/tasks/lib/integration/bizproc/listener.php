<?php

namespace Bitrix\Tasks\Integration\Bizproc;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\Internals\Task\EO_Member_Collection;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;

/**
 * @method static onTaskAdd($id, array $fields)
 * @method static onTaskUpdate($id, array $fields, array $previousFields)
 * @method static onPlanTaskStageUpdate($memberId, $taskId, $stageId)
 * @method static onTaskDeleteExecute($id)
 * @method static onTaskExpired($id, array $fields)
 * @method static onTaskExpiredSoon($id, array $fields)
 * @method static onTaskFieldChanged($id, array $fields, array $previousFields)
 */

class Listener
{
	public const EVENT_TASK_EXPIRED = EventDictionary::EVENT_TASK_EXPIRED;
	public const EVENT_TASK_EXPIRED_SOON = EventDictionary::EVENT_TASK_EXPIRED_SOON;

	private const USE_BACKGROUND_KEY = 'tasks_bizproc_background';

	public function __construct()
	{

	}

	/**
	 * @param string $name
	 * @param array $args
	 * @return false|mixed|void
	 */
	public static function __callStatic(string $name, array $args = [])
	{
		$listener = new self();

		$methodName = $name.'Execute';

		if (!is_callable([$listener, $methodName]))
		{
			return false;
		}

		if (!$listener->useBackground())
		{
			return call_user_func_array([$listener, $methodName], $args);
		}

		$application = Application::getInstance();
		$application && $application->addBackgroundJob(
			[$listener, $methodName],
			$args
		);
	}

	public function onTaskAddExecute($id, array $fields)
	{
		if (TaskLimit::isLimitExceeded() || !$this->loadBizproc())
		{
			return false;
		}

		//fix meta statuses
		if (!empty($fields['REAL_STATUS']))
		{
			$fields['STATUS'] = $fields['REAL_STATUS'];
		}

		//fix creation from template
		if (empty($fields['STATUS']))
		{
			$fields['STATUS'] = Status::PENDING;
		}

		//Run project automation
		if (isset($fields['GROUP_ID']) && $fields['GROUP_ID'] > 0)
		{
			$group = Workgroup::getById($fields['GROUP_ID']);
			$isScrumTaskUpdated = ($group && $group->isScrumProject());

			if ($isScrumTaskUpdated)
			{
				$projectDocumentType = Document\Task::resolveScrumProjectTaskType($fields['GROUP_ID']);
			}
			else
			{
				$projectDocumentType = Document\Task::resolveProjectTaskType($fields['GROUP_ID']);
			}

			//run automation
			Automation\Factory::runOnAdd($projectDocumentType, $id, $fields);
		}

		//Run plan & personal automation

		$members = $this->extractTaskMembers($fields);

		foreach ($members as $memberId)
		{
			$planDocumentType = Document\Task::resolvePlanTaskType($memberId);
			Automation\Factory::runOnAdd($planDocumentType, $id, $fields);

			$personalDocumentType = Document\Task::resolvePersonalTaskType($memberId);
			Automation\Factory::runOnAdd($personalDocumentType, $id, $fields);
		}
	}

	public function onTaskUpdateExecute($id, array $fields, array $previousFields)
	{
		if (TaskLimit::isLimitExceeded() || !$this->loadBizproc())
		{
			return false;
		}

		$projectId = $fields['GROUP_ID'] ?? $previousFields['GROUP_ID'];
		$statusChanged = (isset($fields['STATUS']) && (string)$fields['STATUS'] !== (string)$previousFields['STATUS']);
		$changedFields = $this->compareFields($fields, $previousFields);

		//Stop automation on previous project if project was changed
		if (
			isset($fields['GROUP_ID'])
			&& (int)$fields['GROUP_ID'] !== (int)$previousFields['GROUP_ID']
			&& $previousFields['GROUP_ID'] > 0
		)
		{
			$projectTaskType = $this->resolveProjectTaskType($previousFields['GROUP_ID']);
			Automation\Factory::stopAutomation($projectTaskType, $id);
		}

		//Check triggers for project tasks
		$projectTriggerApplied = ($statusChanged && $this->fireStatusTriggerOnProject($id, $projectId, $fields));
		if ($projectTriggerApplied === false)
		{
			$projectTriggerApplied = (
				$changedFields
				&& static::fireFieldChangedTriggerOnProject($id, $projectId, $changedFields)
			);
		}

		//Run project automation
		$stageChanged = (
			isset($fields['STAGE_ID'])
			&& (
				(int)$fields['STAGE_ID'] === 0
				|| (int)$fields['STAGE_ID'] !== (int)$previousFields['STAGE_ID']
			)
		);
		if ($projectTriggerApplied !== true && $stageChanged)
		{
			$projectDocumentType = $this->resolveProjectTaskType($projectId);
			Automation\Factory::runOnStatusChanged($projectDocumentType, $id, $fields);
		}

		//Run plan & personal automation
		$membersDiff = $this->getMembersDiff($fields, $previousFields);

		//Stop automation for users who left the task
		foreach ($membersDiff->minus as $memberId)
		{
			$planDocumentType = Document\Task::resolvePlanTaskType($memberId);
			Automation\Factory::stopAutomation($planDocumentType, $id);

			$personalDocumentType = Document\Task::resolvePersonalTaskType($memberId);
			Automation\Factory::stopAutomation($personalDocumentType, $id);
		}

		foreach ($membersDiff->plus as $memberId)
		{
			//Run plan
			$planDocumentType = Document\Task::resolvePlanTaskType($memberId);

			$runAutomation = !($statusChanged && $this->fireStatusTrigger($planDocumentType, $id, $fields));
			if ($runAutomation)
			{
				$runAutomation = !(
					$changedFields
					&& $this->fireFieldChangedTrigger($planDocumentType, $id, $changedFields)
				);
			}

			if ($runAutomation)
			{
				Automation\Factory::runOnAdd($planDocumentType, $id, $fields);
			}

			//Run personal
			$personalDocumentType = Document\Task::resolvePersonalTaskType($memberId);
			Automation\Factory::runOnAdd($personalDocumentType, $id, $fields);
		}

		if ($changedFields || $statusChanged)
		{
			foreach ($membersDiff->current as $memberId)
			{
				//Run plan trigger
				$planDocumentType = Document\Task::resolvePlanTaskType($memberId);
				if ($statusChanged)
				{
					$this->fireStatusTrigger($planDocumentType, $id, $fields);

					//Run personal
					$personalDocumentType = Document\Task::resolvePersonalTaskType($memberId);
					Automation\Factory::runOnStatusChanged($personalDocumentType, $id, $fields);
				}
				if ($changedFields)
				{
					$this->fireFieldChangedTrigger($planDocumentType, $id, $changedFields);
				}
			}
		}
	}

	public function onPlanTaskStageUpdateExecute($memberId, $taskId, $stageId)
	{
		if (TaskLimit::isLimitExceeded() || !$this->loadBizproc())
		{
			return false;
		}

		$planDocumentType = Document\Task::resolvePlanTaskType($memberId);
		//run automation
		Automation\Factory::runOnStatusChanged($planDocumentType, $taskId);
	}

	public function onTaskDeleteExecute($id)
	{
		if (!$this->loadBizproc())
		{
			return false;
		}

		$errors = [];
		$documentId = Document\Task::resolveDocumentId($id);
		\CBPDocument::OnDocumentDelete($documentId, $errors);

		return true;
	}

	public function onTaskExpiredExecute($id, array $fields)
	{
		if (TaskLimit::isLimitExceeded() || !$this->loadBizproc())
		{
			return false;
		}

		//Run project trigger
		if ($fields['GROUP_ID'] > 0)
		{
			$group = Workgroup::getById($fields['GROUP_ID']);
			$isScrumTask = ($group && $group->isScrumProject());

			if ($isScrumTask)
			{
				$projectDocumentType = Document\Task::resolveScrumProjectTaskType($fields['GROUP_ID']);
			}
			else
			{
				$projectDocumentType = Document\Task::resolveProjectTaskType($fields['GROUP_ID']);
			}

			Automation\Trigger\Expired::execute($projectDocumentType, $id, $fields);
		}

		//Run plan trigger
		$members = $this->extractTaskMembers($fields);
		foreach ($members as $memberId)
		{
			$planDocumentType = Document\Task::resolvePlanTaskType($memberId);
			Automation\Trigger\Expired::execute($planDocumentType, $id, $fields);
		}
	}

	public function onTaskExpiredSoonExecute($id, array $fields)
	{
		if (TaskLimit::isLimitExceeded() || !$this->loadBizproc())
		{
			return false;
		}

		//Run project trigger
		if ($fields['GROUP_ID'] > 0)
		{
			$group = Workgroup::getById($fields['GROUP_ID']);
			$isScrumTask = ($group && $group->isScrumProject());

			if ($isScrumTask)
			{
				$projectDocumentType = Document\Task::resolveScrumProjectTaskType($fields['GROUP_ID']);
			}
			else
			{
				$projectDocumentType = Document\Task::resolveProjectTaskType($fields['GROUP_ID']);
			}

			Automation\Trigger\ExpiredSoon::execute($projectDocumentType, $id, $fields);
		}

		//Run plan trigger
		$members = $this->extractTaskMembers($fields);
		foreach ($members as $memberId)
		{
			$planDocumentType = Document\Task::resolvePlanTaskType($memberId);
			Automation\Trigger\ExpiredSoon::execute($planDocumentType, $id, $fields);
		}
	}

	public function onTaskFieldChangedExecute($id, array $fields, array $previousFields): Main\Result
	{
		$result = new Main\Result();

		if (!$this->loadBizproc())
		{
			return $result->addError(new Main\Error('Unable to load bizproc module'));
		}

		if (TaskLimit::isLimitExceeded())
		{
			return (
			$result
				->addError(new Main\Error(
					Main\Localization\Loc::getMessage('TASKS_BP_LISTENER_RESUME_RESTRICTED')
				))
			);
		}

		$projectId = $fields['GROUP_ID'] ?? $previousFields['GROUP_ID'];
		$changedFields = $this->compareFields($fields, $previousFields);

		//Run project trigger
		if ($projectId > 0)
		{
			$documentType = $this->resolveProjectTaskType($projectId);
			Automation\Trigger\TasksFieldChangedTrigger::execute($documentType, $id, ['CHANGED_FIELDS' => $changedFields]);
		}

		//Run plan trigger
		$members = $this->extractTaskMembers(array_merge($previousFields, $fields));
		foreach ($members as $memberId)
		{
			$planDocumentType = Document\Task::resolvePlanTaskType($memberId);
			Automation\Trigger\TasksFieldChangedTrigger::execute($planDocumentType, $id, ['CHANGED_FIELDS' => $changedFields]);
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	private function useBackground(): bool
	{
		if (Main\Config\Option::get('tasks', self::USE_BACKGROUND_KEY, 'null') !== 'null')
		{
			return true;
		}

		return false;
	}

	private function fireStatusTriggerOnProject($taskId, $projectId, $fields): bool
	{
		$documentType = $this->resolveProjectTaskType($projectId);

		if ($documentType)
		{
			return $this->fireStatusTrigger($documentType, $taskId, $fields);
		}

		return false;
	}

	private function fireStatusTrigger($documentType, $taskId, $fields): bool
	{
		$result = Automation\Trigger\Status::execute($documentType, $taskId, $fields);
		if ($result->isSuccess())
		{
			$data = $result->getData();
			if (!empty($data['triggerApplied']))
			{
				return true;
			}
		}

		return false;
	}

	private function fireFieldChangedTriggerOnProject($taskId, $projectId, $fields): bool
	{
		$documentType = $this->resolveProjectTaskType($projectId);

		return $this->fireFieldChangedTrigger($documentType, $taskId, $fields);
	}

	private function fireFieldChangedTrigger($documentType, $taskId, $fields): bool
	{
		$result = Automation\Trigger\TasksFieldChangedTrigger::execute(
			$documentType,
			$taskId,
			['CHANGED_FIELDS' => $fields]
		);
		if ($result->isSuccess())
		{
			$data = $result->getData();
			if (!empty($data['triggerApplied']))
			{
				return true;
			}
		}

		return false;
	}

	private function resolveProjectTaskType($projectId): string
	{
		$documentType = Document\Task::resolveProjectTaskType($projectId);
		if ($projectId && Main\Loader::includeModule('socialnetwork'))
		{
			$group = Workgroup::getById($projectId);
			if ($group && $group->isScrumProject())
			{
				$documentType = Document\Task::resolveScrumProjectTaskType($projectId);
			}
		}

		return $documentType;
	}

	private function loadBizproc()
	{
		return Main\Loader::includeModule('bizproc');
	}

	private function extractTaskMembers(array $fields)
	{
		$users = [];

		if (!empty($fields['CREATED_BY']))
		{
			$users[] = $fields['CREATED_BY'];
		}

		if (!empty($fields['RESPONSIBLE_ID']))
		{
			$users[] = $fields['RESPONSIBLE_ID'];
		}

		if (!empty($fields['ACCOMPLICES']))
		{
			if (is_object($fields['ACCOMPLICES']))
			{
				$fields['ACCOMPLICES'] = $fields['ACCOMPLICES']->toArray();
			}

			$users = array_merge($users, $fields['ACCOMPLICES']);
		}

		if (!empty($fields['AUDITORS']))
		{
			if (is_object($fields['AUDITORS']))
			{
				$fields['AUDITORS'] = $fields['AUDITORS']->toArray();
			}

			$users = array_merge($users, $fields['AUDITORS']);
		}

		if (is_object($fields['MEMBER_LIST'] ?? null))
		{
			/** @var $members EO_Member_Collection */
			$members = $fields['MEMBER_LIST'];
			$users = array_merge($users, $members->getUserIdList());
		}

		return array_map('intval', array_unique($users));
	}

	private function getMembersDiff(array $fields, array $previousFields)
	{
		$previousMembers = $this->extractTaskMembers($previousFields);
		$currentMembers = $this->extractTaskMembers(array_merge($previousFields, $fields));

		$plus = array_diff($currentMembers, $previousMembers);
		$minus = array_diff($previousMembers, $currentMembers);

		return (object)['plus' => $plus, 'minus' => $minus, 'current' => $currentMembers];
	}

	private function compareFields(array $actual, array $previous): array
	{
		$diff = [];
		foreach ($actual as $key => $field)
		{
			if (!array_key_exists($key, $previous) || $previous[$key] != $field)
			{
				$diff[] = $key;
			}
		}

		return $diff;
	}
}