<?php

namespace Bitrix\Sign\Operation\DocumentChat;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Integration\Im\DocumentChat;
use Bitrix\Sign\Repository\DocumentChatRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Result\Service\Integration\Im\CreateGroupChatResult;
use Bitrix\Sign\Service\Sign\DocumentChat\ChatTypeConverterService;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\Im\GroupChatService;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\Integration\Im\DocumentChatType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;

final class CreateGroupChat implements Contract\Operation
{
	private MemberRepository $memberRepository;
	private GroupChatService $groupChatService;
	private MemberService $memberService;
	private DocumentService $documentService;
	private ChatTypeConverterService $chatTypeConverterService;
	private DocumentChatRepository $documentChatRepository;

	public function __construct(
		private readonly Document $document,
		private readonly DocumentChatType $chatType,
		private readonly int $chatOwnerId,
		?MemberRepository $memberRepository = null,
		?GroupChatService $groupChatService = null,
		?MemberService $memberService = null,
		?DocumentService $documentService = null,
		?ChatTypeConverterService $chatTypeConverterService = null,
		?DocumentChatRepository $documentChatRepository = null,
	)
	{
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
		$this->groupChatService = $groupChatService ?? Container::instance()->getGroupChatService();
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
		$this->documentService = $documentService ?? Container::instance()->getDocumentService();
		$this->chatTypeConverterService = $chatTypeConverterService ?? Container::instance()->getChatTypeConverterService();
		$this->documentChatRepository = $documentChatRepository ?? Container::instance()->getDocumentChatRepository();
	}

	public function launch(): Main\Result|CreateGroupChatResult
	{
		$documentChat = $this
			->documentChatRepository
			->getByDocumentIdAndType($this->document->id, $this->chatType)
		;

		if ($documentChat !== null)
		{
			$addAdminResult = $this
				->groupChatService
				->addAdminByChatId($documentChat->chatId, $this->chatOwnerId)
			;
			if ($addAdminResult->isSuccess())
			{
				return new CreateGroupChatResult($documentChat->chatId);
			}

			return $addAdminResult;
		}

		$createResult = $this
			->groupChatService
			->createChat($this->configureChatParams())
		;
		if (!$createResult instanceOf CreateGroupChatResult)
		{
			return $createResult;
		}

		$setDescriptionResult = $this
			->groupChatService
			->setChatDescription(
				$createResult->chatId,
				$this->getGroupChatDescription($this->chatType, $this->document)
			)
		;

		if(!$setDescriptionResult->isSuccess())
		{
			return $setDescriptionResult;
		}

		$addResult = $this
			->documentChatRepository
			->add(new DocumentChat(
				documentId: $this->document->id,
				chatId: $createResult->chatId,
				type: $this->chatType
			))
		;
		if(!$addResult->isSuccess())
		{
			return $addResult;
		}

		return new CreateGroupChatResult($createResult->chatId);
	}

	private function getGroupChatTitle(DocumentChatType $chatType, Document $document): ?string
	{
		$composedDocumentTitle = $this->documentService->getComposedTitleByDocument($document);

		return match ($chatType)
		{
			DocumentChatType::WAIT => Loc::getMessage(
				'SIGN_INTEGRATION_DOCUMENT_WAIT_GROUP_CHAT_TITLE',
				[
					'#TITLE#' => $composedDocumentTitle,
				]
			),
			DocumentChatType::READY => Loc::getMessage(
				'SIGN_INTEGRATION_DOCUMENT_READY_GROUP_CHAT_TITLE',
				[
					'#TITLE#' => $composedDocumentTitle,
				]
			),
			DocumentChatType::STOPPED => Loc::getMessage(
				'SIGN_INTEGRATION_DOCUMENT_STOPPED_GROUP_CHAT_TITLE',
				[
					'#TITLE#' => $composedDocumentTitle,
				]
			),
		};
	}

	private function getGroupChatDescription(DocumentChatType $chatType, Document $document): ?string
	{
		$composedDocumentTitle = $this->documentService->getComposedTitleByDocument($document);

		return match ($chatType)
		{
			DocumentChatType::WAIT => Loc::getMessage(
				'SIGN_INTEGRATION_DOCUMENT_WAIT_GROUP_CHAT_DESCRIPTION',
				[
					'#TITLE#' => $composedDocumentTitle,
				]
			),
			DocumentChatType::READY => Loc::getMessage(
				'SIGN_INTEGRATION_DOCUMENT_READY_GROUP_CHAT_DESCRIPTION',
				[
					'#TITLE#' => $composedDocumentTitle,
				]
			),
			DocumentChatType::STOPPED => Loc::getMessage(
				'SIGN_INTEGRATION_DOCUMENT_STOPPED_GROUP_CHAT_DESCRIPTION',
				[
					'#TITLE#' => $composedDocumentTitle,
				]
			),
		};
	}

	private function getGroupChatWelcomeMessage(DocumentChatType $chatType, Document $document): ?string
	{
		$composedDocumentTitle = $this->documentService->getComposedTitleByDocument($document);

		return match ($chatType)
		{
			DocumentChatType::WAIT => Loc::getMessage(
				'SIGN_INTEGRATION_DOCUMENT_WAIT_GROUP_CHAT_WELCOME_MESSAGE',
				[
					'#TITLE#' => $composedDocumentTitle,
				]
			),
			DocumentChatType::READY => Loc::getMessage(
				'SIGN_INTEGRATION_DOCUMENT_READY_GROUP_CHAT_WELCOME_MESSAGE',
				[
					'#TITLE#' => $composedDocumentTitle,
				]
			),
			DocumentChatType::STOPPED => Loc::getMessage(
				'SIGN_INTEGRATION_DOCUMENT_STOPPED_GROUP_CHAT_WELCOME_MESSAGE',
				[
					'#TITLE#' => $composedDocumentTitle,
				]
			),
		};
	}

	/**
	 * @return  array{USERS: int[], TITLE: ?string, DESCRIPTION: ?string}
	 */
	private function configureChatParams(): array
	{
		$statuses =
			$this->document->status === DocumentStatus::STOPPED
				? MemberStatus::getStatusesNotDone()
				: $this->chatTypeConverterService->convertDocumentChatTypeToMemberStatuses($this->chatType)
		;

		$signers =
			$this
				->memberRepository
				->listByDocumentIdAndRoleAndStatus(
					documentId: $this->document->id,
					role: Role::SIGNER,
					statuses: $statuses,
				)
		;
		$signerIds = $this->memberService->getUserIdsForMembers($signers, $this->document);
		$signerIds[] = $this->chatOwnerId;

		return [
			'USERS' => $signerIds,
			'TITLE' => $this->getGroupChatTitle($this->chatType, $this->document),
			'DESCRIPTION' => $this->getGroupChatWelcomeMessage($this->chatType, $this->document),
		];
	}
}