<?php

namespace Bitrix\Sign\Operation\Member;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Result\Operation\Member\GetSignedB2eFileUrlForDownloadResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\EntityFileCode;
use Bitrix\Sign\Type\EntityType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;

class GetSignedB2eFileUrlForDownload implements Operation
{
	private readonly MemberService $memberService;

	public function __construct(
		private readonly Member $member,
		private readonly Document $document,
		?MemberService $memberService = null,
	)
	{
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
	}

	public function launch(): Main\Result|GetSignedB2eFileUrlForDownloadResult
	{
		$result = $this->tryToGetByAssigneeIfNeed();
		if ($result instanceof GetSignedB2eFileUrlForDownloadResult)
		{
			return $result;
		}

		return $this->tryToGetUrlByMemberId($this->member->id);
	}

	private function tryToGetByAssigneeIfNeed(): Main\Result|GetSignedB2eFileUrlForDownloadResult
	{
		if (
			$this->document->isInitiatedByEmployee()
			&& $this->document->status === DocumentStatus::DONE
			&& $this->member->role === Role::SIGNER
		)
		{
			$assignee = $this->memberService->getAssignee($this->document);
			if ($assignee && $assignee->status === MemberStatus::DONE)
			{
				return $this->tryToGetUrlByMemberId($assignee->id);
			}
		}

		return new Main\Result();
	}

	private function tryToGetUrlByMemberId(int $memberId): Main\Result|GetSignedB2eFileUrlForDownloadResult
	{
		$operation = new \Bitrix\Sign\Operation\GetSignedB2eFileUrl(
			EntityType::MEMBER,
			$memberId,
			EntityFileCode::SIGNED,
		);
		$result = $operation->launch();
		if ($result->isSuccess() && $operation->ready)
		{
			$data = $result->getData();

			return new GetSignedB2eFileUrlForDownloadResult(
				url: $data['url'] ?? '',
				ext: $data['ext'] ?? '',
			);
		}

		return $result;
	}

}