<?php

namespace Bitrix\BizprocMobile\Controller\Action\Workflow;

use Bitrix\Bizproc\Workflow\Entity\WorkflowStateTable;
use Bitrix\Bizproc\Workflow\Task\TaskTable;
use Bitrix\Bizproc\Workflow\WorkflowState;
use Bitrix\BizprocMobile\EntityEditor\Converter;
use Bitrix\BizprocMobile\EntityEditor\ParametersProvider;
use Bitrix\BizprocMobile\UI\CommentCounterView;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\UI\EntityEditor\FormWrapper;

class LoadDetailsAction extends Action
{
	private \CBPDocumentService $documentService;

	public function run(string $workflowId)
	{
		$currentUserId = (int)($this->getCurrentUser()?->getId());
		if ($workflowId === '' || $currentUserId <= 0)
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_ACTION_LOAD_DETAILS')));

			return null;
		}

		$select = ['ID', 'MODULE_ID', 'ENTITY', 'DOCUMENT_ID','WORKFLOW_TEMPLATE_ID', 'TEMPLATE'];
		$state = WorkflowStateTable::getByPrimary($workflowId, ['select' => $select])->fetchObject();
		if (!$state)
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_ACTION_LOAD_DETAILS')));

			return null;
		}

		$canView = $this->canUserViewWorkflow($currentUserId, $state);
		$commentCounter = new CommentCounterView($workflowId, $currentUserId);

		if ($state->getModuleId() === 'lists' && $state->getEntity() === 'BizprocDocument')
		{
			return [
				'workflow' => null,
				'editor' => $canView ? $this->getWorkflowEditor($state) : null,
				'taskCount' => $this->getTasksCount($state->getId(), $currentUserId),
				'commentCounter' => $commentCounter,
				'isLiveFeedProcess' => true,
				'documentId' => $state->getDocumentId(),
				'canViewWorkflow' => $canView,
			];
		}

		$complexDocumentId = $state->getComplexDocumentId();
		$template = $state->getTemplate();

		return [
			'workflow' => [
				'title' => $template ? $template->getName() : '',
				'description' => $template ? $template->getDescription() : '',
				'documentTitle' => $this->getDocumentName($complexDocumentId) ?? '',
			],
			'editor' => $canView ? $this->getWorkflowEditor($state) : null,
			'taskCount' => $this->getTasksCount($state->getId(), $currentUserId),
			'commentCounter' => $commentCounter,
			'isLiveFeedProcess' => false,
			'documentId' => $state->getDocumentId(),
			'canViewWorkflow' => $canView,
		];
	}

	private function getDocumentName(array $complexDocumentId)
	{
		return $this->getDocumentService()->getDocumentName($complexDocumentId);
	}

	private function getTasksCount(string $workflowId, int $userId): int
	{
		return TaskTable::getCount([
			'=WORKFLOW_ID' => $workflowId,
			'=TASK_USERS.USER_ID' => $userId,
			'=TASK_USERS.STATUS' => \CBPTaskUserStatus::Waiting,
		]);
	}

	private function getWorkflowEditor(WorkflowState $state): ?array
	{
		try
		{
			$rootActivity = \CBPWorkflowPersister::getPersister()->loadWorkflow($state->getId(), true);
		}
		catch (\Exception $exception)
		{
			return null;
		}

		$complexDocumentType = $this->getDocumentType($state->getComplexDocumentId());
		if (!$complexDocumentType)
		{
			return null;
		}

		$readOnlyData = $rootActivity->getReadOnlyData();
		if ($readOnlyData && isset($readOnlyData['Template']) && is_array($readOnlyData['Template']))
		{
			$parameters = $readOnlyData['Template'];
			$properties = [];
			foreach ($parameters as $id => $value)
			{
				$property = $rootActivity->getPropertyType($id);
				if ($property)
				{
					$properties[$id] = $property;
				}
			}

			if ($properties)
			{
				$converter =
					(new Converter($properties, $state->getComplexDocumentId(), $parameters))
						->setContext(Converter::CONTEXT_PARAMETERS, ['templateId' => $state->getWorkflowTemplateId()])
						->setDocumentType($complexDocumentType)
				;
				$provider = new ParametersProvider(
					$converter->toMobile()->getConvertedProperties(),
					$state->getWorkflowTemplateId(),
					$this->getSignedDocument($complexDocumentType, $state->getDocumentId())
				);
				$provider->setIsReadOnly(true);
				$provider->setUseSectionBorder(true);

				return (new FormWrapper($provider))->getResult();
			}
		}

		return null;
	}

	private function getSignedDocument(array $complexDocumentType, string $documentId): string
	{
		return \CBPDocument::signParameters([$complexDocumentType, $documentId]);
	}

	private function getDocumentType(array $complexDocumentId)
	{
		try
		{
			$complexDocumentType = $this->getDocumentService()->getDocumentType($complexDocumentId);
		}
		catch (\Exception $exception)
		{
			$complexDocumentType = null;
		}

		return $complexDocumentType;
	}

	private function getDocumentService(): \CBPDocumentService
	{
		if (!isset($this->documentService))
		{
			$this->documentService = \CBPRuntime::getRuntime()->getDocumentService();
		}

		return $this->documentService;
	}

	private function canUserViewWorkflow(int $currentUserId, WorkflowState $state): bool
	{
		$complexDocumentId = $state->getComplexDocumentId();

		return \CBPDocument::canUserOperateDocument(
			\CBPCanUserOperateOperation::ViewWorkflow,
			$currentUserId,
			$complexDocumentId,
			[
				'WorkflowId' => $state->getId(),
				'WorkflowTemplateId' => $state->getWorkflowTemplateId(),
			]
		);
	}
}
