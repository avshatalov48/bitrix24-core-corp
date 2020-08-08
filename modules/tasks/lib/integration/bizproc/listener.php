<?
namespace Bitrix\Tasks\Integration\Bizproc;

use Bitrix\Main;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;

class Listener
{
	public static function onTaskAdd($id, array $fields)
	{
		if (TaskLimit::isLimitExceeded())
		{
			return false;
		}

		//fix creation from template
		if (!isset($fields['STATUS']))
		{
			$fields['STATUS'] = \CTasks::STATE_PENDING;
		}

		//Run project automation

		if (isset($fields['GROUP_ID']) && $fields['GROUP_ID'] > 0)
		{
			$projectDocumentType = Document\Task::resolveProjectTaskType($fields['GROUP_ID']);
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
		if (TaskLimit::isLimitExceeded())
		{
			return false;
		}

		$projectId = isset($fields['GROUP_ID']) ? $fields['GROUP_ID'] : $previousFields['GROUP_ID'];
		$statusChanged = (isset($fields['STATUS']) && (string)$fields['STATUS'] !== (string)$previousFields['STATUS']);

		//Stop automation on previous project if project was changed
		if (
			isset($fields['GROUP_ID']) &&
			(int)$fields['GROUP_ID'] !== (int)$previousFields['GROUP_ID'] &&
			$previousFields['GROUP_ID'] > 0
		)
		{
			Automation\Factory::stopAutomation(Document\Task::resolveProjectTaskType($previousFields['GROUP_ID']), $id);
		}

		//Check triggers for project tasks
		$projectTriggerApplied = ($statusChanged ? static::fireStatusTriggerOnProject($id, $projectId, $fields) : false);

		//Run project automation
		if (
			$projectTriggerApplied !== true &&
			isset($fields['STAGE_ID']) &&
			(
				(int)$fields['STAGE_ID'] === 0 ||
				(int)$fields['STAGE_ID'] !== (int)$previousFields['STAGE_ID']
			)
		)
		{
			$projectDocumentType = Document\Task::resolveProjectTaskType($projectId);
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
			if (!$statusChanged || $statusChanged && !self::fireStatusTriggerOnPlan($planDocumentType, $id, $fields))
			{
				Automation\Factory::runOnAdd($planDocumentType, $id, $fields);
			}

			//Run personal
			$personalDocumentType = Document\Task::resolvePersonalTaskType($memberId);
			Automation\Factory::runOnAdd($personalDocumentType, $id, $fields);
		}

		if ($statusChanged)
		{
			foreach ($membersDiff->current as $memberId)
			{
				//Run plan trigger
				$planDocumentType = Document\Task::resolvePlanTaskType($memberId);
				self::fireStatusTriggerOnPlan($planDocumentType, $id, $fields);

				//Run personal
				$personalDocumentType = Document\Task::resolvePersonalTaskType($memberId);
				Automation\Factory::runOnStatusChanged($personalDocumentType, $id, $fields);
			}
		}
	}

	public static function onPlanTaskStageUpdate($memberId, $taskId, $stageId)
	{
		if (TaskLimit::isLimitExceeded())
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

		$documentId = Document\Task::resolveDocumentId($id);
		\CBPDocument::OnDocumentDelete($documentId, $errors);

		return true;
	}

	public static function onTaskExpired($id, array $fields)
	{
		if (TaskLimit::isLimitExceeded())
		{
			return false;
		}

		//Run project trigger
		if ($fields['GROUP_ID'] > 0)
		{
			$projectDocumentType = Document\Task::resolveProjectTaskType($fields['GROUP_ID']);
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
		if (TaskLimit::isLimitExceeded())
		{
			return false;
		}

		//Run project trigger
		if ($fields['GROUP_ID'] > 0)
		{
			$projectDocumentType = Document\Task::resolveProjectTaskType($fields['GROUP_ID']);
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

	private static function fireStatusTriggerOnProject($taskId, $projectId, $fields)
	{
		$documentType = Document\Task::resolveProjectTaskType($projectId);

		if ($documentType)
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
		}

		return false;
	}

	private static function fireStatusTriggerOnPlan($documentType, $taskId, $fields)
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

		return array_map('intval', array_unique($users));
	}

	private static function getMembersDiff(array $fields, array $previousFields)
	{
		$previousMembers = self::extractTaskMembers($previousFields);
		$currentMembers = self::extractTaskMembers(array_merge($previousFields, $fields));

		$plus = array_diff($currentMembers, $previousMembers);
		$minus = array_diff($previousMembers, $currentMembers);

		return (object) ['plus' => $plus, 'minus' => $minus, 'current' => $currentMembers];
	}
}