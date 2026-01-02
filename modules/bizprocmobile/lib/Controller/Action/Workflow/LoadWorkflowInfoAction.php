<?php

namespace Bitrix\BizprocMobile\Controller\Action\Workflow;

use Bitrix\Bizproc\Api\Request\WorkflowAccessService\CanViewTimelineRequest;
use Bitrix\Bizproc\Workflow\Task\TaskTable;
use Bitrix\Bizproc\Api\Service\WorkflowAccessService;
use Bitrix\Bizproc\WorkflowStateTable;
use Bitrix\BizprocMobile\UI\WorkflowUserDetailView;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class LoadWorkflowInfoAction extends Action
{
	public function run(?string $workflowId = null, ?int $taskId = null, ?int $userId = null)
	{
		if (!$workflowId)
		{
			$workflowId = $this->getWorkflowIdFromTask((int)$taskId);

			if (!$workflowId)
			{
				$this->addError(new Error(
					Loc::getMessage('M_BP_LIB_CONTROLLER_ACTION_LOAD_WORKFLOW_INFO') ?? ''
				));

				return null;
			}
		}

		if (!$this->checkRights($workflowId, (int)$userId))
		{
			return null;
		}

		$workflowState =
			WorkflowStateTable::query()
				->setSelect(['ID', 'MODULE_ID', 'ENTITY'])
				->where('ID', $workflowId)
				->exec()
				->fetchObject()
		;
		if (!$workflowState)
		{
			$this->addError(new Error(
				Loc::getMessage('M_BP_LIB_CONTROLLER_ACTION_LOAD_WORKFLOW_INFO') ?? ''
			));

			return null;
		}

		$userId = $userId > 0 ? $userId : (int)($this->getCurrentUser()?->getId());

		$workflowView = new WorkflowUserDetailView($workflowState, $userId);
		$workflowView->setTaskId((int)$taskId);

		$taskId = $workflowView->getExtractedTaskId();
		if ($taskId)
		{
			return ['taskId' => $taskId, 'workflowId' => $workflowId];
		}

		return ['workflowId' => $workflowId];
	}

	private function checkRights(string $workflowId, int $userId): bool
	{
		$currentUserId = (int)($this->getCurrentUser()?->getId());
		$isAdmin = (new \CBPWorkflowTemplateUser(\CBPWorkflowTemplateUser::CurrentUser))->isAdmin();

		if ($isAdmin)
		{
			return true;
		}

		$userId = $userId > 0 ? $userId : $currentUserId;

		$accessService = new WorkflowAccessService();
		$canViewProcess = $accessService->canViewTimeline(
			new CanViewTimelineRequest(workflowId: $workflowId, userId: $userId)
		);

		if (!$canViewProcess->isSuccess())
		{
			$this->addErrors($canViewProcess->getErrors());

			return false;
		}

		if ($currentUserId !== $userId && !\CBPHelper::checkUserSubordination($currentUserId, $userId))
		{
			$this->addError($accessService::getViewAccessDeniedError());

			return false;
		}

		return true;
	}

	private function getWorkflowIdFromTask(int $taskId): ?string
	{
		if ($taskId <= 0)
		{
			return null;
		}

		$row = TaskTable::query()
			->where('ID', $taskId)
			->setSelect(['WORKFLOW_ID'])
			->fetch()
		;

		return $row['WORKFLOW_ID'] ?? null;
	}
}
