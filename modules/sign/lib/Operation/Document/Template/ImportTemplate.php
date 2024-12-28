<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Blank\Export\PortableBlank;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Operation\Document\ImportBlank;
use Bitrix\Sign\Result\Operation\Document\ImportBlankResult;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\BlankService;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Type\BlankScenario;
use Bitrix\Sign\Type\Document\EntityType;

class ImportTemplate implements Operation
{
	private readonly DocumentService $documentService;
	private readonly BlankService $blankService;

	public function __construct(
		private readonly PortableBlank $blank,
		?DocumentService $documentService = null,
		?BlankService $blankService = null,
	)
	{
		$container = Container::instance();
		$this->documentService = $documentService ?? $container->getDocumentService();
		$this->blankService = $blankService ?? $container->getSignBlankService();
	}

	public function launch(): Main\Result
	{
		if (!$this->blank->isForTemplate)
		{
			return Result::createByErrorMessage('Blank not for template');
		}

		$result = (new ImportBlank($this->blank))->launch();
		if (!$result instanceof ImportBlankResult)
		{
			return $result;
		}

		$blankId = $result->blankId;

		$result = $this->registerAndUpload($blankId);
		if (!$result->isSuccess())
		{
			$this->blankService->rollbackById($blankId);
		}

		return $result;
	}

	private function registerAndUpload(int $blankId): Main\Result
	{
		$entityType = $this->blank->scenario === BlankScenario::B2E ? EntityType::SMART_B2E : EntityType::SMART;

		$result = $this->documentService->register(
			blankId: $blankId,
			title: $this->blank->title,
			entityId: 0,
			entityType: $entityType,
			asTemplate: $this->blank->isForTemplate,
			initiatedByType: $this->blank->initiatedByType,
		);

		if (!$result->isSuccess())
		{
			$createdDocumentId = (int)($result->getData()['documentId'] ?? null);
			if ($createdDocumentId)
			{
				$this->documentService->rollbackDocument($createdDocumentId);
			}

			return $result;
		}

		$document = $result->getData()['document'] ?? null;
		if (!$document instanceof Document)
		{
			return Result::createByErrorMessage('Unexpected create template result');
		}

		$result = $this->documentService->upload($document->uid);
		if (!$result->isSuccess())
		{
			$this->documentService->rollbackDocument($document->id);
		}

		return $result;
	}
}