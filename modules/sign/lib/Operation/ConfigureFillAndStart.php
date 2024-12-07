<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Operation\Result\ConfigureResult;
use Bitrix\Sign\Operation\Result\FillFieldsResult;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\DocumentStatus;

class ConfigureFillAndStart implements Operation
{
	private readonly Document $document;
	private readonly DocumentRepository $documentRepository;

	public function __construct(
		private readonly string $uid,
		?DocumentRepository $documentRepository = null,
	)
	{
		$this->documentRepository = $documentRepository ?? Container::instance()->getDocumentRepository();
	}

	public function launch(): Main\Result|ConfigureResult
	{
		$document = $this->documentRepository->getByUid($this->uid);
		if ($document === null)
		{
			return (new Main\Result())
				->addError(new Main\Error("Document with id `$this->uid` doesnt exist"))
			;
		}
		$this->document = $document;

		if ($this->document->status === DocumentStatus::SIGNING)
		{
			return new ConfigureResult(true);
		}

		if ($this->document->status !== DocumentStatus::READY)
		{
			return $this->configure();
		}

		$fillResult = (new \Bitrix\Sign\Operation\SigningService\FillFields($this->document))->launch();
		if ($fillResult instanceof FillFieldsResult)
		{
			return $this->startOrProgress($fillResult);
		}

		return $fillResult;
	}

	private function configure(): Main\Result|ConfigureResult
	{
		$result = (new ConfigureDocument($this->document->uid))->launch();
		if (!$result->isSuccess())
		{
			return $result;
		}

		return new ConfigureResult(false);
	}

	private function startOrProgress(FillFieldsResult $fillResult): Main\Result|ConfigureResult
	{
		if (!$fillResult->completed)
		{
			return new ConfigureResult(false);
		}

		if (!$this->getSigningStartLock())
		{
			return new ConfigureResult(false);
		}

		$startResult = (new SigningStart($this->document->uid))->launch();
		$this->releaseSigningStartLock();
		if ($startResult->isSuccess())
		{
			return new ConfigureResult(true);
		}

		return $startResult;
	}

	private function getSigningStartLock(): bool
	{
		return Main\Application::getConnection()->lock($this->getLockName());
	}

	private function releaseSigningStartLock(): bool
	{
		return Main\Application::getConnection()->unlock($this->getLockName());
	}

	private function getLockName(): string
	{
		return "sign_signing_start_{$this->document->uid}";
	}
}
