<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Tasks;

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('DeleteDocumentActivity');

class CBPTasksDeleteTaskActivity extends CBPDeleteDocumentActivity
{
	public function Execute()
	{
		if (!CModule::IncludeModule('tasks'))
		{
			CBPActivityExecutionStatus::Closed;
		}

		$documentType = $this->GetDocumentType()[2];
		$documentId = $this->GetDocumentId();
		$taskId = $documentId[2];

		$canDelete = false;

		if (
			Tasks\Integration\Bizproc\Document\Task::isProjectTask($documentType)
			|| Tasks\Integration\Bizproc\Document\Task::isScrumProjectTask($documentType)
		)
		{
			$canDelete = true;
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

					$canDelete = (isset($allowedActions['ACTION_REMOVE']) && $allowedActions['ACTION_REMOVE'] === true);
				}
			}
		}

		if (!$canDelete)
		{
			$this->WriteToTrackingService(GetMessage('TASKS_DTA_NO_PERMISSIONS'), 0, CBPTrackingType::Error);
		}
		else
		{
			$documentService = $this->workflow->GetService("DocumentService");
			$documentService->DeleteDocument($documentId);
		}

		return CBPActivityExecutionStatus::Closed;
	}
}