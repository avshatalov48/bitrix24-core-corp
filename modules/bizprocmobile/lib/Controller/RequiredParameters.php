<?php

namespace Bitrix\BizprocMobile\Controller;

use Bitrix\BizprocMobile\EntityEditor\RequiredParametersProvider;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\UI\EntityEditor\FormWrapper;

class RequiredParameters extends \Bitrix\Main\Engine\Controller
{
	public function loadAction(string $signedDocument): ?array
	{
		$unsignedDocument = $this->getUnsignedDocument($signedDocument);
		if (!$unsignedDocument)
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_REQUIRED_PARAMETERS_INCORRECT_SIGNED_DOCUMENT')));

			return null;
		}

		[$complexDocumentType, $complexDocumentId] = $unsignedDocument;

		if (!$this->canUserOperateDocumentType($complexDocumentType))
		{
			$this->addError(new Error(Loc::getMessage('M_BP_LIB_CONTROLLER_REQUIRED_PARAMETERS_ACCESS_DENIED')));

			return null;
		}

		$provider = new RequiredParametersProvider(
			$signedDocument,
			$complexDocumentId ? \CBPDocumentEventType::Edit : \CBPDocumentEventType::Create,
		);

		return [
			'editorConfig' => $provider->getEntityFields() ? (new FormWrapper($provider))->getResult() : [],
		];
	}

	private function getUnsignedDocument(string $signedDocument): ?array
	{
		$unsignedDocument = \CBPDocument::unSignParameters($signedDocument);
		if ($unsignedDocument)
		{
			try
			{
				$complexDocumentType = \CBPHelper::parseDocumentId($unsignedDocument[0]);
				$complexDocumentId = null;
				if (isset($unsignedDocument[1]) && is_scalar($unsignedDocument[1]))
				{
					$complexDocumentId = \CBPHelper::parseDocumentId(
						[$complexDocumentType[0], $complexDocumentType[1], $unsignedDocument[1]]
					);
				}

				return [$complexDocumentType, $complexDocumentId];
			}
			catch (\CBPArgumentNullException $exception)
			{}
		}

		return null;
	}

	private function canUserOperateDocumentType(array $complexDocumentType): bool
	{
		$currentUserId = (int)($this->getCurrentUser()?->getId());
		if ($currentUserId <= 0)
		{
			return false;
		}

		return \CBPDocument::canUserOperateDocumentType(
			\CBPCanUserOperateOperation::StartWorkflow,
			$currentUserId,
			$complexDocumentType,
		);
	}
}
