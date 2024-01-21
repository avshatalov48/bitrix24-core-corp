<?php

namespace Bitrix\BizprocMobile\Controller;

use Bitrix\Bizproc\Api\Data\WorkflowStateService\WorkflowStateFilter;
use Bitrix\Bizproc\Api\Request\WorkflowStateService\GetTimelineRequest;
use Bitrix\Bizproc\Api\Service\WorkflowStateService;
use Bitrix\BizprocMobile\EntityEditor\Converter;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Provider\UserRepository;
use Bitrix\Mobile\UI\StatefulList\BaseController;

class Workflow extends BaseController
{
	public function configureActions(): array
	{
		return [
			'loadList' => [
				'class' => Action\Workflow\LoadListAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'loadTemplates' => [
				'class' => Action\Workflow\LoadTemplatesAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
			'loadParametersEditor' => [
				'class' => Action\Workflow\LoadParametersEditorAction::class,
				'+prefilters' => [
					new ActionFilter\CloseSession(),
				],
			],
		];
	}

	public function getTimelineAction(string $workflowId)
	{
		$workflowStateService = new WorkflowStateService();

		$request = new GetTimelineRequest(workflowId: $workflowId, userId: CurrentUser::get()->getId());
		$response = $workflowStateService->getTimeline($request);

		if (!$response->isSuccess())
		{
			$this->addErrors($response->getErrors());

			return null;
		}

		$timeline = $response->getTimeline();

		$userIds = [$timeline->getWorkflowState()->getStartedBy()];
		foreach ($timeline->getTasks() as $task)
		{
			$userIds = array_merge($userIds, $task->getTaskUserIds());
		}

		$data = $timeline->jsonSerialize();
		$data['users'] = UserRepository::getByIds($userIds);

		return $data;
	}

	public function startAction(string $signedDocument, int $templateId, array $parameters = []): ?array
	{
		if (!\CBPRuntime::isFeatureEnabled())
		{
			$this->addError(new Error(
				Loc::getMessage('M_BP_LIB_CONTROLLER_WORKFLOW_ERROR_BIZPROC_FEATURE_DISABLED')
			));

			return null;
		}

		if ($templateId <= 0)
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_WORKFLOW_ERROR')));

			return null;
		}

		$unsignedDocument = $this->unSignDocument($signedDocument);
		if ($unsignedDocument === null)
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_WORKFLOW_ERROR')));

			return null;
		}

		$complexDocumentType = \CBPHelper::parseDocumentId($unsignedDocument[0]);
		$complexDocumentId = \CBPHelper::parseDocumentId(
			[$complexDocumentType[0], $complexDocumentType[1], $unsignedDocument[1]]
		);

		if (!$this->canUserStartWorkflow($complexDocumentId, $templateId))
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_WORKFLOW_ERROR_ACCESS_DENIED')));

			return null;
		}

		$template = $this->loadTemplate($templateId);
		if (!$template)
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_WORKFLOW_ERROR')));

			return null;
		}

		if (!$this->isCorrectTemplateToStart($template, $complexDocumentType))
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_WORKFLOW_ERROR')));

			return null;
		}

		$templateParameters = $template['PARAMETERS'];
		if (!is_array($templateParameters))
		{
			$templateParameters = [];
		}

		$convertedParameters = $parameters;
		$pendingFiles = null;
		if ($parameters)
		{
			$converter = new Converter($templateParameters, $complexDocumentId, $parameters);
			$converter
				->setDocumentType($complexDocumentType)
				->setContext(
					Converter::CONTEXT_PARAMETERS,
					['signedDocument' => $signedDocument, 'templateId' => $templateId]
				)
			;

			$convertedParameters = $converter->toWeb()->getConvertedValues();
			$pendingFiles = $converter->getPendingFiles();

			$errors = [];
			$convertedParameters = \CBPWorkflowTemplateLoader::checkWorkflowParameters(
				$templateParameters,
				$convertedParameters,
				$complexDocumentType,
				$errors
			);

			if ($errors)
			{
				foreach ($errors as $error)
				{
					$this->addError(new Error($error['message']));
				}

				return null;
			}
		}

		$convertedParameters[\CBPDocument::PARAM_TAGRET_USER] =  'user_' . (int)$this->getCurrentUser()->getId();
		// $convertedParameters[\CBPDocument::PARAM_DOCUMENT_EVENT_TYPE] = \CBPDocumentEventType::Manual;

		$errors = [];
		$workflowId = \CBPDocument::startWorkflow($templateId, $complexDocumentId, $convertedParameters, $errors);

		if ($errors)
		{
			foreach ($errors as $error)
			{
				$this->addError(new Error($error['message']));
			}

			return null;
		}

		if ($pendingFiles)
		{
			foreach ($pendingFiles as $pendingFile)
			{
				$pendingFile->makePersistent();
			}
		}

		return [
			'workflowId' => $workflowId,
		];
	}

	private function unSignDocument(string $signedDocument): ?array
	{
		$unsignedDocument = \CBPDocument::unSignParameters($signedDocument);
		if (count($unsignedDocument) === 2)
		{
			try
			{
				$complexDocumentType = \CBPHelper::parseDocumentId($unsignedDocument[0]);
				\CBPHelper::parseDocumentId([$complexDocumentType[0], $complexDocumentType[1], $unsignedDocument[1]]);

				return $unsignedDocument;
			}
			catch (\CBPArgumentNullException $e)
			{}
		}

		return null;
	}

	private function canUserStartWorkflow(array $complexDocumentId, int $templateId): bool
	{
		$currentUserId = (int)$this->getCurrentUser()->getId();

		if (!\CBPDocument::canUserOperateDocument(
			\CBPCanUserOperateOperation::StartWorkflow,
			$currentUserId,
			$complexDocumentId,
			[
				'UserGroups' => \CUser::GetUserGroup($currentUserId),
				'DocumentStates' => \CBPDocument::getActiveStates($complexDocumentId),
				'WorkflowTemplateId' => $templateId,
			]
		))
		{
			return false;
		}

		return true;
	}

	private function loadTemplate(int $templateId): bool|array
	{
		$filter = ['ID' => $templateId];
		$select = ['ID', 'MODULE_ID', 'ENTITY', 'DOCUMENT_TYPE', 'AUTO_EXECUTE', 'PARAMETERS', 'ACTIVE', 'IS_SYSTEM'];

		return \CBPWorkflowTemplateLoader::getList([], $filter, false, false, $select)->fetch();
	}

	private function isCorrectTemplateToStart(array $template, array $complexDocumentType): bool
	{
		if (
			\CBPHelper::isEqualDocument($complexDocumentType, $template['DOCUMENT_TYPE'])
			&& (int)$template['AUTO_EXECUTE'] < \CBPDocumentEventType::Automation
			&& $template['IS_SYSTEM'] === 'N'
			&& $template['ACTIVE'] === 'Y'
		)
		{
			return true;
		}

		return false;
	}

	public function getFilterPresetsAction()
	{
		return [
			'presets' => WorkflowStateFilter::getPresetList(),
			'counters' => [],
		];
	}

	protected function getDefaultPreFilters(): array
	{
		return [
			new ActionFilter\Authentication(),
			new ActionFilter\Csrf(),
			new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
			new ActionFilter\Scope(ActionFilter\Scope::NOT_REST),
		];
	}
}
