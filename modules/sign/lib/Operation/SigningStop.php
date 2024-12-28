<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Sign\Item\Api\Document\Signing\StopRequest;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type;
use Bitrix\Sign\Contract;

use Bitrix\Main;

class SigningStop implements Contract\Operation
{
	public function __construct(
		private string $uid,
		private ?int $userId = null,
		private ?DocumentRepository $documentRepository = null
	)
	{
		$this->documentRepository ??= Container::instance()->getDocumentRepository();
	}

	public function launch(): Main\Result
	{
		$result = new Main\Result();
		$document = $this->documentRepository->getByUid($this->uid);
		if (!$document)
		{
			return $result->addError(new Main\Error('Document not found'));
		}
		$previousStoppedBy = $document->stoppedById;
		$document->stoppedById = $this->userId;
		$updateResult = $this->documentRepository->update($document);
		if (!$updateResult->isSuccess())
		{
			return $result->addError(new Main\Error('Can not update document'));
		}

		$signingStopResponse = Container::instance()->getApiDocumentSigningService()
			->stop(
				new StopRequest($this->uid)
			)
		;

		if (!$signingStopResponse->isSuccess())
		{
			$document->stoppedById = $previousStoppedBy;
			$this->documentRepository->update($document);

			return $result->addErrors($signingStopResponse->getErrors());
		}

		$changeStatusResult = (new ChangeDocumentStatus($document, Type\DocumentStatus::STOPPED))->launch();
		if (!$changeStatusResult->isSuccess())
		{
			return $changeStatusResult;
		}

		return $result;
	}
}
