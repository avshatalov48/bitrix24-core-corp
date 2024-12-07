<?php

namespace Bitrix\Crm\Service\Sign;

use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\MemberService as SignMemberService;

class MemberService
{
	private ?SignMemberService $signMemberService = null;

	public function __construct()
	{
		if (self::isAvailable())
		{
			$this->signMemberService = Container::instance()->getMemberService();
		}
	}

	public static function isAvailable(): bool
	{
		return \Bitrix\Main\Loader::includeModule('crm')
			&& \Bitrix\Main\Loader::includeModule('sign')
			&& Storage::instance()->isAvailable();
	}

	public function getMembersForSignDocument(int $documentId): ?MemberCollection
	{
		if (
			self::isAvailable()
			&& method_exists(SignMemberService::class, 'listByDocumentId')
		)
		{
			return $this->signMemberService?->listByDocumentId($documentId);
		}

		return null;
	}

	public function getSignMemberRepresentedName(Member $member): ?string
	{
		if (
			$this->signMemberService
			&& self::isAvailable()
			&& method_exists(SignMemberService::class, 'getMemberRepresentedName')
		)
		{
			return $this->signMemberService->getMemberRepresentedName($member);
		}

		return $member->name;
	}
}
