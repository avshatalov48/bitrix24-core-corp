<?php

namespace Bitrix\Sign\Operation\DocumentChat;

use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Repository\DocumentChatRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\Im\GroupChatService;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\Integration\Im\DocumentChatType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;

final class AddMembersByStoppedDocument implements Contract\Operation
{
	private GroupChatService $groupChatService;
	private MemberService $memberService;
	private DocumentChatRepository $documentChatRepository;
	private MemberRepository $memberRepository;

	public function __construct(
		public Document         $document,
		?DocumentChatRepository $documentChatRepository = null,
		?GroupChatService       $groupChatService = null,
		?MemberService          $memberService = null,
		?MemberRepository       $memberRepository = null,
	)
	{
		$this->documentChatRepository = $documentChatRepository ?? Container::instance()->getDocumentChatRepository();
		$this->groupChatService = $groupChatService ?? Container::instance()->getGroupChatService();
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
	}

	public function launch(): Main\Result
	{
		$groupChat = $this
			->documentChatRepository
			->getByDocumentIdAndType($this->document->id, DocumentChatType::STOPPED)
		;
		if ($groupChat === null)
		{
			return new Main\Result();
		}

		$members = $this
			->memberRepository
			->listByDocumentIdAndRoleAndStatus(
				documentId: $this->document->id,
				role: Role::SIGNER,
				statuses: MemberStatus::getStatusesNotDone())
		;
		$userIds = $this->memberService->getUserIdsForMembers($members, $this->document);

		return $this->groupChatService->addUsersByChatId($groupChat->chatId, $userIds);
	}
}