<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Main;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Api\Member\WebStatusRequest;
use Bitrix\Sign\Result;
use Bitrix\Sign\Service;
use Bitrix\Sign\Type\Member\SignWebStatus;
use Bitrix\Sign\Type\MemberStatus;

class GetMemberWebStatus implements Operation
{
	private Service\Api\Member\WebStatusService $memberWebStatusService;

	public function __construct(
		private readonly string $memberUid,
		private readonly string $documentUid
	)
	{
		$this->memberWebStatusService = Service\Container::instance()->getServiceMemberWebStatus();
	}

	public function launch(): Result\Operation\MemberWebStatusResult|Main\Result
	{
		$request = new WebStatusRequest(memberUid: $this->memberUid, documentUid: $this->documentUid);
		$webStatus = $this->memberWebStatusService->get($request)->status;
		if ($webStatus === null)
		{
			return (new Main\Result())->addError(new Main\Error('Do not get status from service'));
		}

		$status = $this->convertWebStatus($webStatus);

		if ($status === null)
		{
			return (new Main\Result())->addError(new Main\Error('Unexpected status'));
		}

		return new Result\Operation\MemberWebStatusResult($status);
	}

	private function convertWebStatus(string $status): ?string
	{
		return match ($status)
		{
			SignWebStatus::WAIT => MemberStatus::WAIT,
			SignWebStatus::READY => MemberStatus::READY,
			SignWebStatus::COMPLETING => MemberStatus::PROCESSING,
			SignWebStatus::DONE => MemberStatus::DONE,
			SignWebStatus::STOPPED => MemberStatus::STOPPED,
			SignWebStatus::REFUSED => MemberStatus::REFUSED,
			SignWebStatus::STOPPABLE_READY => MemberStatus::STOPPABLE_READY,
			default => null,
		};
	}
}