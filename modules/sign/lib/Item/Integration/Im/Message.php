<?php

namespace Bitrix\Sign\Item\Integration\Im;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Service\Sign\MemberService;

abstract class Message implements \Bitrix\Sign\Contract\Chat\Message
{
	protected ?Document $document = null;
	protected ?Member $member = null;
	protected ?string $link = null;

	protected DocumentService $documentService;
	protected MemberService $memberService;

	protected ?string $lang = null;

	protected function __construct(
		private readonly int $fromUser,
		private readonly int $toUser,
		?DocumentService $documentService = null,
		?MemberService $memberService = null,
	)
	{
		$this->documentService = $documentService ?? Container::instance()->getDocumentService();
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
	}

	public function getUserFrom(): int
	{
		return $this->fromUser;
	}

	public function getUserTo(): int
	{
		return $this->toUser;
	}

	public function getHelpId(): ?int
	{
		return null;
	}

	public function getDocument(): ?Document
	{
		return $this->document;
	}

	public function getMember(): ?Member
	{
		return $this->member;
	}

	public function getLink(): ?string
	{
		return $this->link;
	}

	public function setLang(?string $lang): self
	{
		$this->lang = $lang;

		return $this;
	}

	protected function getDocumentName(Document $document): string
	{
		return $this->documentService->getComposedTitleByDocument($document);
	}

	protected function getMemberName(Member $member): ?string
	{
		return $this->memberService->getMemberRepresentedName($member) ?? $member->name;
	}

	protected function getUserName(int $userId): ?string
	{
		return $this->memberService->getUserRepresentedName($userId);
	}

	protected function getLocalizedFallbackMessage(string $id, array $replace = null, ?string $lang = null): ?string
	{
		$lang = $lang ?? $this->lang;

		return Loc::getMessage($id, $replace, $lang);
	}
}
