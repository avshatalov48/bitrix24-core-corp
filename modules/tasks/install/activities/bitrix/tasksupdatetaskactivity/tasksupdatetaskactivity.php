<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Tasks;

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('SetFieldActivity');

class CBPTasksUpdateTaskActivity extends CBPSetFieldActivity
{
	public function Execute()
	{
		if (!CModule::IncludeModule('tasks'))
		{
			CBPActivityExecutionStatus::Closed;
		}

		$fieldValue = $this->FieldValue;

		if (!is_array($fieldValue) || count($fieldValue) <= 0)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$documentType = $this->GetDocumentType()[2];
		$documentId = $this->GetDocumentId();
		$taskId = $documentId[2];

		$canUpdate = false;

		if (
			Tasks\Integration\Bizproc\Document\Task::isProjectTask($documentType)
			|| Tasks\Integration\Bizproc\Document\Task::isScrumProjectTask($documentType)
		)
		{
			$canUpdate = true;
		}
		else
		{
			if (Tasks\Integration\Bizproc\Document\Task::isPlanTask($documentType))
			{
				$ownerId = Tasks\Integration\Bizproc\Document\Task::resolvePlanId($documentType);
			}
			else
			{
				$ownerId = Tasks\Integration\Bizproc\Document\Task::resolvePersonId($documentType);
			}

			if ($ownerId > 0)
			{
				$res = \CTasks::GetByID($taskId, false);
				$taskFields = $res ? $res->fetch() : null;

				if ($taskFields)
				{
					$allowedActions = \CTaskItem::getAllowedActionsArray($ownerId, $taskFields, true);

					$canUpdate = (isset($allowedActions['ACTION_EDIT']) && $allowedActions['ACTION_EDIT'] === true);

					if (
						!$canUpdate &&
						isset($allowedActions['ACTION_CHANGE_DEADLINE']) &&
						$allowedActions['ACTION_CHANGE_DEADLINE'] === true &&
						count($fieldValue) === 1 &&
						array_key_exists('DEADLINE', $fieldValue)
					)
					{
						$canUpdate = true;
					}

				}
			}
		}

		if (!$canUpdate)
		{
			$this->WriteToTrackingService(GetMessage('TASKS_UTA_NO_PERMISSIONS'), 0, CBPTrackingType::Error);
			$this->ErrorMessage = GetMessage('TASKS_UTA_NO_PERMISSIONS');
		}
		else
		{
			if (method_exists($this, 'prepareFieldsValues'))
			{
				$fieldValue = $this->prepareFieldsValues($documentId, $this->GetDocumentType(), $fieldValue);
			}

			$documentService = $this->workflow->GetService("DocumentService");

			try
			{
				$documentService->UpdateDocument($documentId, $fieldValue, $this->ModifiedBy);
			}
			catch (Exception $e)
			{
				$this->writeToTrackingService($e->getMessage(), 0, CBPTrackingType::Error);
				$this->ErrorMessage = $e->getMessage();
			}
		}

		return CBPActivityExecutionStatus::Closed;
	}
}
