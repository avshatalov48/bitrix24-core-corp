<?php

namespace Bitrix\Sign\Operation\DocumentChat;

use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Repository\DocumentChatRepository;
use Bitrix\Sign\Service\Sign\DocumentChat\ChatTypeConverterService;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\Im\GroupChatService;
use Bitrix\Sign\Service\Sign\MemberService;

final class AddMemberByDocument implements Contract\Operation
{
	private GroupChatService $groupChatService;
	private MemberService $memberService;
	private ChatTypeConverterService $chatTypeConverterService;
	private DocumentChatRepository $documentChatRepository;

	public function __construct(
		private readonly Member   $member,
		private readonly Document $document,
		?DocumentChatRepository   $documentChatRepository = null,
		?GroupChatService         $groupChatService = null,
		?MemberService            $memberService = null,
		?ChatTypeConverterService $chatTypeConverterService = null,
	)
	{
		$this->documentChatRepository = $documentChatRepository ?? Container::instance()->getDocumentChatRepository();
		$this->groupChatService = $groupChatService ?? Container::instance()->getGroupChatService();
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
		$this->chatTypeConverterService = $chatTypeConverterService ?? Container::instance()->getChatTypeConverterService();
	}

	public function launch(): Main\Result
	{
		$chatType = $this
			->chatTypeConverterService
			->convertMemberStatusesToDocumentChatType($this->member->status)
		;
		if ($chatType === null)
		{
			return new Main\Result();
		}

		$groupChat = $this
			->documentChatRepository
			->getByDocumentIdAndType($this->document->id, $chatType)
		;
		if ($groupChat === null)
		{
			return new Main\Result();
		}

		$userId = $this->memberService->getUserIdForMember($this->member, $this->document);

		return $this->groupChatService->addUsersByChatId($groupChat->chatId, [$userId]);
	}
}