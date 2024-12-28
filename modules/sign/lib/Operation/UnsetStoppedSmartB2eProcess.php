<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Main;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Type\DocumentScenario;
use Bitrix\Sign\Type\DocumentStatus;

final class UnsetStoppedSmartB2eProcess implements Contract\Operation
{
	private readonly DocumentService $documentService;

	public function __construct(private readonly Item\Document $document)
	{
		$this->documentService = Container::instance()->getDocumentService();
	}

	public function launch(): Main\Result
	{
		$result = new Main\Result();
		if ($this->document->id === null)
		{
			return $result->addError(new Main\Error('Empty document ID.'));
		}

		if (!DocumentScenario::isB2EScenario($this->document->scenario ?? ''))
		{
			return $result->addError(new Main\Error('Only B2E document scenarios are supported.'));
		}

		if ($this->document->status !== DocumentStatus::STOPPED)
		{
			return $result->addError(new Main\Error('Invalid document status.'));
		}

		if (!$this->document->isInitiatedByEmployee())
		{
			return $result->addError(new Main\Error('Not initiated by user.'));
		}

		$smartDocument = $this->documentService->getDocumentEntity($this->document);
		if ($smartDocument === null)
		{
			return $result->addError(new Main\Error('Smart document not found.'));
		}

		$smartDocumentDeleteResult = $smartDocument->delete();
		if (!$smartDocumentDeleteResult->isSuccess())
		{
			return $smartDocumentDeleteResult;
		}

		return $this->documentService->unsetEntityId($this->document);
	}
}
