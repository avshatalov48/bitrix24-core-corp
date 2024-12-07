<?php

namespace Bitrix\Sign\Controllers\V1\B2e\Document;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Engine\Controller;
use Bitrix\Sign\Operation\SyncMemberStatus;
use Bitrix\Sign\Result\Operation\MemberWebStatusResult;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type;

class Member extends Controller
{
	public function callStatusAction(int $memberId): array
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			return [];
		}

		$container = Service\Container::instance();
		$memberRepository = $container->getMemberRepository();
		$member = $memberRepository->getById($memberId);
		$currentUserId = CurrentUser::get()->getId();
		if ($currentUserId === null)
		{
			return [];
		}

		$document = $container->getDocumentRepository()->getById($member->documentId);
		if (!Service\Container::instance()->getMemberService()->isUserLinksWithMember($member, $document, $currentUserId))
		{
			return [];
		}

		$result = (new SyncMemberStatus($member, $document))->launch();
		if (!$result->isSuccess())
		{
			return [];
		}

		/** @var MemberWebStatusResult $result */
		$status = $result->status;

		return compact('status');
	}
}