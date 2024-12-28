<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Sign\Contract\Operation;
use Bitrix\Sign\Service\B2e\MyDocumentsGrid\DataService;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\CounterService;
use Bitrix\Sign\Service\PullService;
use Bitrix\Sign\Type\CounterType;

final class UpdateUserCounter implements Operation
{
	private readonly CounterService $counterService;
	private readonly PullService $pullService;
	private readonly DataService $myDocumentService;

	public function __construct(
		private readonly int $userId,
		private readonly CounterType $counterType,
	)
	{
		$this->counterService = Container::instance()->getCounterService();
		$this->pullService = Container::instance()->getPullService();
		$this->myDocumentService = Container::instance()->getMyDocumentService();
	}

	public function launch(): Main\Result
	{
		$result = new Result();
		if ($this->userId < 1)
		{
			return $result->addError(new Error('Member id is not valid.'));
		}

		$count = $this->getCount($this->counterType, $this->userId);
		if (!$this->counterService->set($this->counterType, $count, $this->userId))
		{
			return $result->addError(new Error('Failed to set counter.'));
		}

		$pullEventName = $this->counterService->getPullEventName($this->counterType);

		if (!$this->pullService->sendCounterEvent($this->userId, $pullEventName, $count))
		{
			return $result->addError(new Error('Failed to send counter event.'));
		}

		return $result;
	}

	private function getCount(CounterType $counterType, int $userId): int
	{
		if ($userId < 1)
		{
			return 0;
		}

		return match ($counterType)
		{
			CounterType::SIGN_B2E_MY_DOCUMENTS => $this->myDocumentService->getTotalCountNeedAction($userId),
		};
	}
}
