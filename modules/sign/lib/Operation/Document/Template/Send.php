<?php

namespace Bitrix\Sign\Operation\Document\Template;

use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Document\Template;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Operation\Result\ConfigureResult;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Result\CreateDocumentResult;
use Bitrix\Sign\Result\Operation\Document\Template\SendResult;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Member\Role;

final class Send implements Contract\Operation
{
	private const TRIES_TO_CONFIGURE_AND_START_SIGNING = 10;

	private readonly DocumentService $documentService;
	private readonly DocumentRepository $documentRepository;
	private readonly MemberRepository $memberRepository;
	private readonly MemberService $memberService;

	public function __construct(
		private readonly Template $template,
		private readonly int $sendFromUserId,
		?DocumentService $documentService = null,
	)
	{
		$this->documentService = $documentService ?? Container::instance()->getDocumentService();
		$this->documentRepository = Container::instance()->getDocumentRepository();
		$this->memberRepository = Container::instance()->getMemberRepository();
		$this->memberService = Container::instance()->getMemberService();
	}

	public function launch(): Main\Result|SendResult
	{
		if ($this->template->id === null)
		{
			return Result::createByErrorData(message: 'Template is not saved');
		}
		$document = $this->documentRepository->getByTemplateId($this->template->id);
		if ($document?->initiatedByType !== InitiatedByType::EMPLOYEE)
		{
			return Result::createByErrorData(message: 'Cant send document by template');
		}

		$result = (new Operation\Document\Copy($document, $this->sendFromUserId))->launch();
		if (!$result instanceof CreateDocumentResult)
		{
			return $result;
		}
		$newDocument = $result->document;
		$newDocument->title = $this->template->title;
		$result = $this->documentRepository->update($newDocument);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$result = $this->updateMembers($newDocument);
		if (!$result->isSuccess())
		{
			return $result;
		}
		$result = $this->configureAndStart($newDocument);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$employeeMember = $this->memberRepository->getByDocumentIdWithRole($newDocument->id, Role::SIGNER);

		return new SendResult($document, $employeeMember);
	}

	private function updateMembers(Document $document): Main\Result
	{
		$members = $this->memberRepository->listByDocumentIdExcludeRoles($document->id, Role::SIGNER, Role::EDITOR);
		$members->add(
			new Member(
				party: 1,
				entityType: EntityType::USER,
				entityId: $this->sendFromUserId,
				role: Role::SIGNER,
			)
		);
		foreach ($members as $member)
		{
			$member->id = null;
		}
		$result = $this->memberService->setupB2eMembers($document->uid, $members, $document->representativeId);
		if (!$result->isSuccess())
		{
			return $result;
		}

		$document->parties = $members->count();

		return $this->documentRepository->update($document);
	}

	private function configureAndStart(Document $newDocument): Main\Result
	{
		for ($i = 0; $i < self::TRIES_TO_CONFIGURE_AND_START_SIGNING; $i++)
		{
			$result = $this->documentService->configureAndStart($newDocument->uid);
			if (!$result->isSuccess())
			{
				return $result;
			}

			if ($result instanceof ConfigureResult && $result->completed)
			{
				return new Main\Result();
			}
		}

		$tries = self::TRIES_TO_CONFIGURE_AND_START_SIGNING;

		return Result::createByErrorData(message: "Signing to started after `$tries` tries");
	}
}