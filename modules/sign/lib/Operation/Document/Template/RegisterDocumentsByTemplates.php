<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Item\Document\TemplateCollection;
use Bitrix\Sign\Result\CreateDocumentResult;
use Bitrix\Sign\Result\Operation\Document\Template\CreateDocumentsResult;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Item\Document\Template\TemplateCreatedDocumentCollection;
use Bitrix\Sign\Item\Document\Template\TemplateCreatedDocument;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Type\Document\InitiatedByType;

class RegisterDocumentsByTemplates implements Operation
{
	private readonly DocumentService $documentService;
	public function __construct(
		private readonly TemplateCollection $templates,
		private readonly int $sendFromUserId,
		private readonly InitiatedByType $onlyInitiatedByType,
		private readonly bool $excludeRejected = true,
	) {
		$this->documentService = Container::instance()->getDocumentService();
	}

	public function launch(): Main\Result|CreateDocumentsResult
	{
		if ($this->templates->isEmpty())
		{
			return Result::createByErrorMessage('No templates');
		}

		$createdDocumentCollection = new TemplateCreatedDocumentCollection();
		foreach ($this->templates as $template)
		{
			$result = $this->registerDocument($template);
			if ($result instanceof CreateDocumentResult)
			{
				$createdDocumentCollection->add(new TemplateCreatedDocument($template, $result->document));
			}
			elseif (!$result->isSuccess())
			{
				return $this->rollbackDocumentsWithResult($result, $createdDocumentCollection);
			}
		}

		return new CreateDocumentsResult($createdDocumentCollection);
	}

	private function registerDocument(Template $template): Main\Result|CreateDocumentResult
	{
		return (new RegisterDocumentByTemplate(
			template: $template,
			sendFromUserId: $this->sendFromUserId,
			onlyInitiatedByType: $this->onlyInitiatedByType,
			excludeRejected: $this->excludeRejected,
		))
			->launch()
		;
	}

	private function rollbackDocumentsWithResult(
		Main\Result $errorResult,
		TemplateCreatedDocumentCollection $createdDocumentCollection,
	): Main\Result
	{
		foreach ($createdDocumentCollection as $createdDocument)
		{
			$result = $this->documentService->rollbackDocument($createdDocument->document->id);
			$errorResult->addErrors($result->getErrors());
		}

		return $errorResult;
	}
}