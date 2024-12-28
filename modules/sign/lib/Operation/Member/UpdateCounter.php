<?php

namespace Bitrix\Sign\Operation\Member;

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Operation\UpdateUserCounter;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\CounterType;

final class UpdateCounter implements Operation
{
	private readonly MemberService $memberService;

	public function __construct(
		private readonly CounterType $counterType,
		private readonly Member $member,
		private readonly ?Document $document = null,
	)
	{
		$this->memberService = Container::instance()->getMemberService();
	}

	public function launch(): Main\Result
	{
		$result = new Result();
		if ($this->member->id === null)
		{
			return $result->addError(new Error('Member id is empty.'));
		}

		$userId = (int)$this->memberService->getUserIdForMember($this->member, $this->document);
		if ($userId < 1)
		{
			return $result->addError(new Error('Member user id is not valid.'));
		}

		return (new UpdateUserCounter(
			$userId,
			$this->counterType,
		))->launch();
	}
}
