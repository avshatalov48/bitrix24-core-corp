<?php

namespace Bitrix\Sign\Service\Integration\SignMobile;

use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Item;

final class MemberService
{
	private readonly MemberRepository $memberRepository;

	public function __construct(
		?MemberRepository $memberRepository = null,
	)
	{
		$this->memberRepository = $memberRepository ?? Container::instance()->getMemberRepository();
	}

	public function getB2eSigningMemberDocumentList(int $userId, int $limit = 30): Item\Integration\SignMobile\MemberDocumentCollection
	{
		return $this->memberRepository->listB2eSigningDocumentsByUserId($userId, $limit);
	}

	public function getB2eSignedMemberDocumentList(int $userId, int $limit = 30): Item\Integration\SignMobile\MemberDocumentCollection
	{
		return $this->memberRepository->listB2eSignedDocumentsByUserId($userId, $limit);
	}

	public function getB2eReviewMemberDocumentList(int $userId, int $limit = 30): Item\Integration\SignMobile\MemberDocumentCollection
	{
		return $this->memberRepository->listB2eReviewDocumentsByUserId($userId, $limit);
	}
}
