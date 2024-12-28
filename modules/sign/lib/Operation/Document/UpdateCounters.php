<?php

namespace Bitrix\Sign\Operation\Document;

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Operation\UpdateUserCounter;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\CounterType;

final class UpdateCounters implements Operation
{
	private readonly MemberService $memberService;

	public function __construct(private readonly CounterType $counterType, private readonly Document $document)
	{
		$this->memberService = Container::instance()->getMemberService();
	}

	public function launch(): Main\Result
	{
		$result = new Result();
		if ($this->document->id === null)
		{
			return $result->addError(new Error('Document id is empty.'));
		}

		$userIds = $this->memberService->getUserIdsByDocument($this->document);

		foreach ($userIds as $userId)
		{
			$updateUserCountersResult = (new UpdateUserCounter(
				$userId,
				$this->counterType,
			))->launch();

			if (!$updateUserCountersResult->isSuccess())
			{
				return $updateUserCountersResult;
			}
		}

		return $result;
	}
}