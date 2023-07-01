<?php

namespace Bitrix\Tasks\Integration\Bizproc;

use Bitrix\Main;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Internals\Task\EO_Member_Collection;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;

class Listener
{
	public static function onTaskAdd($id, array $fields)
	{
		if (TaskLimit::isLimitExceeded() || !self::loadBizproc())
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
			$fields['STATUS'] = \CTasks::STATE_PENDING;
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

		$members = self::extractTaskMembers($fields);

		foreach ($members as $memberId)
		{
			$planDocumentType = Document\Task::resolvePlanTaskType($memberId);
			Automation\Factory::runOnAdd($planDocumentType, $id, $fields);

			$personalDocumentType = Document\Task::resolvePersonalTaskType($memberId);
			Automation\Factory::runOnAdd($personalDocumentType, $id, $fields);
		}
	}

	public static function onTaskUpdate($id, array $fields, array $previousFields)
	{
		if (TaskLimit::isLimitExceeded() || !self::loadBizproc())
		{
			return false;
		}

		$projectId = $fields['GROUP_ID'] ?? $previousFields['GROUP_ID'];
		$statusChanged = (isset($fields['STATUS']) && (string)$fields['STATUS'] !== (string)$previousFields['STATUS']);
		$changedFields = self::compareFields($fields, $previousFields);

		//Stop automation on previous project if project was changed
		if (
			isset($fields['GROUP_ID'])
			&& (int)$fields['GROUP_ID'] !== (int)$previousFields['GROUP_ID']
			&& $previousFields['GROUP_ID'] > 0
		)
		{
			$projectTaskType = self::resolveProjectTaskType($previousFields['GROUP_ID']);
			Automation\Factory::stopAutomation($projectTaskType, $id);
		}

		//Check triggers for project tasks
		$projectTriggerApplied = ($statusChanged && static::fireStatusTriggerOnProject($id, $projectId, $fields));
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
			$projectDocumentType = self::resolveProjectTaskType($projectId);
			Automation\Factory::runOnStatusChanged($projectDocumentType, $id, $fields);
		}

		//Run plan & personal automation
		$membersDiff = self::getMembersDiff($fields, $previousFields);

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

			$runAutomation = !($statusChanged && self::fireStatusTrigger($planDocumentType, $id, $fields));
			if ($runAutomation)
			{
				$runAutomation = !(
					$changedFields
					&& self::fireFieldChangedTrigger($planDocumentType, $id, $changedFields)
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
					self::fireStatusTrigger($planDocumentType, $id, $fields);

					//Run personal
					$personalDocumentType = Document\Task::resolvePersonalTaskType($memberId);
					Automation\Factory::runOnStatusChanged($personalDocumentType, $id, $fields);
				}
				if ($changedFields)
				{
					self::fireFieldChangedTrigger($planDocumentType, $id, $changedFields);
				}
			}
		}
	}

	public static function onPlanTaskStageUpdate($memberId, $taskId, $stageId)
	{
		if (TaskLimit::isLimitExceeded() || !self::loadBizproc())
		{
			return false;
		}

		$planDocumentType = Document\Task::resolvePlanTaskType($memberId);
		//run automation
		Automation\Factory::runOnStatusChanged($planDocumentType, $taskId);
	}

	public static function onTaskDelete($id)
	{
		if (!self::loadBizproc())
		{
			return false;
		}

		$errors = [];
		$documentId = Document\Task::resolveDocumentId($id);
		\CBPDocument::OnDocumentDelete($documentId, $errors);

		return true;
	}

	public static function onTaskExpired($id, array $fields)
	{
		if (TaskLimit::isLimitExceeded() || !self::loadBizproc())
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
		$members = self::extractTaskMembers($fields);
		foreach ($members as $memberId)
		{
			$planDocumentType = Document\Task::resolvePlanTaskType($memberId);
			Automation\Trigger\Expired::execute($planDocumentType, $id, $fields);
		}
	}

	public static function onTaskExpiredSoon($id, array $fields)
	{
		if (TaskLimit::isLimitExceeded() || !self::loadBizproc())
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
		$members = self::extractTaskMembers($fields);
		foreach ($members as $memberId)
		{
			$planDocumentType = Document\Task::resolvePlanTaskType($memberId);
			Automation\Trigger\ExpiredSoon::execute($planDocumentType, $id, $fields);
		}
	}

	public static function onTaskFieldChanged($id, array $fields, array $previousFields): Main\Result
	{
		$result = new Main\Result();

		if (!self::loadBizproc())
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
		$changedFields = self::compareFields($fields, $previousFields);

		//Run project trigger
		if ($projectId > 0)
		{
			$documentType = self::resolveProjectTaskType($projectId);
			Automation\Trigger\TasksFieldChangedTrigger::execute($documentType, $id, ['CHANGED_FIELDS' => $changedFields]);
		}

		//Run plan trigger
		$members = self::extractTaskMembers(array_merge($previousFields, $fields));
		foreach ($members as $memberId)
		{
			$planDocumentType = Document\Task::resolvePlanTaskType($memberId);
			Automation\Trigger\TasksFieldChangedTrigger::execute($planDocumentType, $id, ['CHANGED_FIELDS' => $changedFields]);
		}

		return $result;
	}

	private static function fireStatusTriggerOnProject($taskId, $projectId, $fields): bool
	{
		$documentType = self::resolveProjectTaskType($projectId);

		if ($documentType)
		{
			return self::fireStatusTrigger($documentType, $taskId, $fields);
		}

		return false;
	}

	private static function fireStatusTrigger($documentType, $taskId, $fields): bool
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

	private static function fireFieldChangedTriggerOnProject($taskId, $projectId, $fields): bool
	{
		$documentType = self::resolveProjectTaskType($projectId);

		return self::fireFieldChangedTrigger($documentType, $taskId, $fields);
	}

	private static function fireFieldChangedTrigger($documentType, $taskId, $fields): bool
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

	private static function resolveProjectTaskType($projectId): string
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

	private static function loadBizproc()
	{
		return Main\Loader::includeModule('bizproc');
	}

	private static function extractTaskMembers(array $fields)
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

	private static function getMembersDiff(array $fields, array $previousFields)
	{
		$previousMembers = self::extractTaskMembers($previousFields);
		$currentMembers = self::extractTaskMembers(array_merge($previousFields, $fields));

		$plus = array_diff($currentMembers, $previousMembers);
		$minus = array_diff($previousMembers, $currentMembers);

		return (object)['plus' => $plus, 'minus' => $minus, 'current' => $currentMembers];
	}

	private static function compareFields(array $actual, array $previous): array
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