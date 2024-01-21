<?php

namespace Bitrix\BizprocMobile\Controller\Action\Workflow;

use Bitrix\Bizproc\Api\Request\WorkflowStateService\GetAverageWorkflowDurationRequest;
use Bitrix\Bizproc\Api\Service\WorkflowStateService;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

class LoadTemplatesAction extends Action
{
	public function run(string $signedDocument)
	{
		if (!\CBPRuntime::isFeatureEnabled())
		{
			return ['templates' => []];
		}

		$unsignedDocument = \CBPDocument::unSignParameters($signedDocument);
		if (!$this->isCorrectUnsignedDocument($unsignedDocument))
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_ACTION_LOAD_TEMPLATES_ERROR')));

			return null;
		}

		$complexDocumentType = \CBPHelper::parseDocumentId($unsignedDocument[0]);
		$complexDocumentId = \CBPHelper::parseDocumentId(
			[$complexDocumentType[0], $complexDocumentType[1], $unsignedDocument[1]]
		);

		$currentUserId = (int)$this->getCurrentUser()->getId();
		$templates = \CBPDocument::getTemplatesForStart(
			$currentUserId,
			$complexDocumentType,
			$complexDocumentId,
			[
				'UserGroups' => \CUser::GetUserGroup($currentUserId),
				'DocumentStates' => \CBPDocument::getActiveStates($complexDocumentId),
			]
		);

		$modifiedTemplates = [];
		$workflowStateService = new WorkflowStateService();
		foreach ($templates as $template)
		{
			$averageTimeResponse = $workflowStateService->getAverageWorkflowDuration(
				new GetAverageWorkflowDurationRequest((int)$template['id'])
			);
			if ($averageTimeResponse->isSuccess())
			{
				$template['time'] = $averageTimeResponse->getAverageDuration();
			}

			$modifiedTemplates[] = $template;
		}

		return ['templates' => $modifiedTemplates];
	}

	private function isCorrectUnsignedDocument(array $unsignedDocument): bool
	{
		if (count($unsignedDocument) === 2)
		{
			try
			{
				$complexDocumentType = \CBPHelper::parseDocumentId($unsignedDocument[0]);
				\CBPHelper::parseDocumentId([$complexDocumentType[0], $complexDocumentType[1], $unsignedDocument[1]]);

				return true;
			}
			catch (\CBPArgumentNullException $e)
			{}
		}

		return false;
	}
}
