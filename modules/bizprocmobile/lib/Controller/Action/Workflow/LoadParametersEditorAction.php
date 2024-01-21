<?php

namespace Bitrix\BizprocMobile\Controller\Action\Workflow;

use Bitrix\BizprocMobile\EntityEditor\Converter;
use Bitrix\BizprocMobile\EntityEditor\ParametersProvider;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\UI\EntityEditor\FormWrapper;

class LoadParametersEditorAction extends Action
{
	public function run(string $signedDocument, int $templateId): ?array
	{
		if (!\CBPRuntime::isFeatureEnabled())
		{
			$this->addError(new Error(
				Loc::getMessage('M_BP_LIB_CONTROLLER_ACTION_LOAD_PARAMETERS_EDITOR_ERROR_BIZPROC_FEATURE_DISABLED')
			));

			return null;
		}

		if ($templateId <= 0)
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_ACTION_LOAD_PARAMETERS_EDITOR_ERROR')));

			return null;
		}

		$unsignedDocument = \CBPDocument::unSignParameters($signedDocument);
		if (!$this->isCorrectUnsignedDocument($unsignedDocument))
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_ACTION_LOAD_PARAMETERS_EDITOR_ERROR')));

			return null;
		}

		$complexDocumentType = \CBPHelper::parseDocumentId($unsignedDocument[0]);
		$complexDocumentId = \CBPHelper::parseDocumentId(
			[$complexDocumentType[0], $complexDocumentType[1], $unsignedDocument[1]]
		);

		if (!$this->canUserOperateDocument($complexDocumentId, $templateId))
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_ACTION_LOAD_PARAMETERS_EDITOR_ERROR_ACCESS_DENIED')));

			return null;
		}

		$template = $this->loadTemplate($templateId);
		if (!$template)
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_ACTION_LOAD_PARAMETERS_EDITOR_ERROR')));

			return null;
		}

		if (!$this->isCorrectTemplate($template, $complexDocumentType))
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_ACTION_LOAD_PARAMETERS_EDITOR_ERROR')));

			return null;
		}

		$parameters = $template['PARAMETERS'];
		if (!is_array($parameters))
		{
			$parameters = [];
		}

		$converter = new Converter($parameters, $complexDocumentId);
		$converter
			->setDocumentType($complexDocumentType)
			->setContext(
				Converter::CONTEXT_PARAMETERS,
				['templateId' => $templateId, 'signedDocument' => $signedDocument]
			)
		;
		$provider = new ParametersProvider(
			$converter->toMobile()->getConvertedProperties(),
			$templateId,
			$signedDocument
		);

		return ['editorConfig' => (new FormWrapper($provider))->getResult()];
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

	private function canUserOperateDocument(array $complexDocumentId, int $templateId): bool
	{
		$currentUserId = (int)$this->getCurrentUser()->getId();

		return \CBPDocument::canUserOperateDocument(
			\CBPCanUserOperateOperation::StartWorkflow,
			$currentUserId,
			$complexDocumentId,
			[
				'UserGroups' => \CUser::GetUserGroup($currentUserId),
				'DocumentStates' => \CBPDocument::getActiveStates($complexDocumentId),
				'WorkflowTemplateId' => $templateId,
			]
		);
	}

	private function loadTemplate(int $templateId): bool|array
	{
		$select = ['ID', 'MODULE_ID', 'ENTITY', 'DOCUMENT_TYPE', 'AUTO_EXECUTE', 'PARAMETERS', 'ACTIVE', 'IS_SYSTEM'];
		$filter = ['ID' => $templateId];

		return \CBPWorkflowTemplateLoader::getList([], $filter, false, false, $select)->fetch();
	}

	private function isCorrectTemplate(array $template, array $complexDocumentType): bool
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
}
