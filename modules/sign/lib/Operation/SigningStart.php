<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Integration\CRM\Model\EventData;
use Bitrix\Sign\Item\Api\Document\Signing\StartRequest;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\Crm\EventHandlerService;
use Bitrix\Sign\Service\Sign\LegalLogService;
use Bitrix\Sign\Type;

class SigningStart implements Contract\Operation
{
	private readonly MemberRepository $memberRepository;
	private readonly Logger $logger;

	public function __construct(
		private string $uid,
		private ?DocumentRepository $documentRepository = null,
		private ?EventHandlerService $eventHandlerService = null,
		private ?LegalLogService $legalLogService = null,
		?MemberRepository $memberRepository = null,
		?Logger $logger = null
	)
	{
		$this->documentRepository ??= Container::instance()->getDocumentRepository();
		$this->eventHandlerService ??= Container::instance()->getEventHandlerService();
		$this->legalLogService ??= Container::instance()->getLegalLogService();
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
		$this->logger = $logger ?? Logger::getInstance();
	}

	public function launch(): Main\Result
	{
		$result = new Main\Result();
		$document = $this->documentRepository->getByUid($this->uid);
		if (!$document)
		{
			return $result->addError(new Main\Error('Document not found'));
		}

		$signingStartResponse = Container::instance()->getApiDocumentSigningService()
			->start(
				new StartRequest($this->uid)
			)
		;

		if (!$signingStartResponse->isSuccess())
		{
			return $result->addErrors($signingStartResponse->getErrors());
		}

		$result = (new ChangeDocumentStatus($document, Type\DocumentStatus::SIGNING))->launch();
		if ($result->isSuccess())
		{
			$this->sendEvents($document);
			$this->legalLogService->registerDocumentStart($document);
			$reminderStartResult = (new Operation\Member\Reminder\Start($document))->launch();
			if (!$reminderStartResult->isSuccess())
			{
				$message = "Failed to start reminder for document: {{documentId}}. Result errors: "
					. implode('|| ', $result->getErrorMessages())
				;
				$this->logger->warning($message, ['documentId' => $document->id],);
			}
		}

		return $result;
	}

	private function sendEvents(Document $document): void
	{
		$eventData = new EventData();
		$eventData->setEventType(EventData::TYPE_ON_STARTED)
			->setDocumentItem($document);

		try
		{
			$this->eventHandlerService->createTimelineEvent($eventData);
		}
		catch (ArgumentException|Main\ArgumentOutOfRangeException $e)
		{
		}
	}
}
