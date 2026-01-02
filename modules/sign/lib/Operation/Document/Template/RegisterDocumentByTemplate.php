<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Result\CreateDocumentResult;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\Template\Status;
use Bitrix\Sign\Type\Template\Visibility;

class RegisterDocumentByTemplate implements Operation
{
	private readonly DocumentService $documentService;
	private readonly DocumentRepository $documentRepository;

	public function __construct(
		private readonly Template $template,
		private readonly int $sendFromUserId,
		private readonly InitiatedByType $onlyInitiatedByType,
		private readonly bool $excludeRejected = true,
	)
	{
		$this->documentService = Container::instance()->getDocumentService();
		$this->documentRepository = Container::instance()->getDocumentRepository();
	}

	public function launch(): CreateDocumentResult|Main\Result
	{
		if ($this->template->id === null)
		{
			return Result::createByErrorData(message: 'Template is not saved');
		}

		if ($this->template->status !== Status::COMPLETED)
		{
			return Result::createByErrorData(message: 'Template is not completed');
		}

		if ($this->template->visibility === Visibility::INVISIBLE)
		{
			return Result::createByErrorData(message: 'Template is not visible');
		}

		if ($this->sendFromUserId < 1)
		{
			return Result::createByErrorData(message: 'Send from user is not set');
		}

		$templateDocument = $this->documentRepository->getByTemplateId($this->template->id);
		if ($templateDocument === null)
		{
			return Result::createByErrorData(message: "Document with template id {$this->template->id} not found");
		}

		if ($templateDocument->initiatedByType !== $this->onlyInitiatedByType)
		{
			return Result::createByErrorData(
				message: "Only initiated by type {$this->onlyInitiatedByType->value} document templates allowed",
			);
		}

		$createResult = (new \Bitrix\Sign\Operation\Document\Copy(
			document: $templateDocument,
			createdByUserId: $this->sendFromUserId,
			excludeRejected: $this->excludeRejected,
		))->launch();
		if (!$createResult instanceof CreateDocumentResult)
		{
			return $createResult;
		}

		$newDocument = $createResult->document;
		if ($newDocument->id === null)
		{
			return Result::createByErrorData(message: 'Document is not created.');
		}

		$newDocument->title = $this->template->title;
		$result = $this->documentRepository->update($newDocument);
		if (!$result->isSuccess())
		{
			$rollbackResult = $this->documentService->rollbackDocument($newDocument->id);
			$result->addErrors($rollbackResult->getErrors());

			return $result;
		}

		return $createResult;
	}
}