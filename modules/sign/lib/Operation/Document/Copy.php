<?php

namespace Bitrix\Sign\Operation\Document;

use Bitrix\Main;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Helper\CloneHelper;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Result\CreateDocumentResult;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Service\Sign\MemberService;

final class Copy implements Contract\Operation
{
	private readonly DocumentService $documentService;
	private readonly DocumentRepository $documentRepository;
	private readonly MemberRepository $memberRepository;
	private readonly MemberService $memberService;

	public function __construct(
		private readonly Item\Document $document,
		private readonly int $createdByUserId,
		private readonly ?int $templateId = null,
		?DocumentService $documentService = null,
		?DocumentRepository $documentRepository = null,
		?MemberRepository $memberRepository = null,
		?MemberService $memberService = null,
	)
	{
		$container = Container::instance();

		$this->documentService = $documentService ?? $container->getDocumentService();
		$this->documentRepository = $documentRepository ?? $container->getDocumentRepository();
		$this->memberRepository = $memberRepository ?? $container->getMemberRepository();
		$this->memberService = $memberService ?? $container->getMemberService();
	}

	public function launch(): Main\Result|CreateDocumentResult
	{
		if ($this->document->id === null)
		{
			return Result::createByErrorData(message: 'Document is not saved');
		}
		$result = $this->registerAndUploadDocument();
		if (!$result instanceof CreateDocumentResult)
		{
			return $result;
		}
		$newDocument = $result->document;

		$this->updateDocumentProperties($this->document, $newDocument);
		$result = $this->documentRepository->update($newDocument);
		if (!$result->isSuccess() || $newDocument->representativeId === null)
		{
			return $result;
		}

		$members = $this->memberRepository->listByDocumentId($this->document->id);
		$result = $this->memberService->setupB2eMembers($newDocument->uid, $members, $newDocument->representativeId);
		if (!$result->isSuccess())
		{
			return $result;
		}

		return new CreateDocumentResult($newDocument);
	}

	public function updateDocumentProperties(Document $oldDocument, Document $newDocument): void
	{
		$newDocument->createdFromDocumentId = $this->document->id;
		$newDocument->templateId = null;
		$newDocument->createdById = $this->createdByUserId;
		$newDocument->stoppedById = null;
		CloneHelper::copyPropertiesIfPossible($oldDocument, $newDocument);
	}

	private function registerAndUploadDocument(): CreateDocumentResult|Main\Result
	{
		$createdById = (int)CurrentUser::get()->getId();
		$result = $this->documentService->register(
			$this->document->blankId,
			$this->document->title,
			entityType: $this->document->entityType,
			asTemplate: false,
			initiatedByType: $this->document->initiatedByType,
			createdById: $createdById,
			templateId: $this->templateId,
		);
		if (!$result->isSuccess())
		{
			return $result;
		}
		$newDocument = $result->getData()['document'] ?? null;
		if (!$newDocument instanceof Document)
		{
			return Result::createByErrorData(message: 'Cant create new document by template');
		}

		$result = $this->documentService->upload($newDocument->uid);
		if (!$result->isSuccess())
		{
			return $result;
		}
		$newDocument = $result->getData()['document'] ?? null;
		if (!$newDocument instanceof Document)
		{
			return Result::createByErrorData(message: 'Cant create new document by template');
		}

		return new CreateDocumentResult($newDocument);
	}
}