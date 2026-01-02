<?php

namespace Bitrix\Sign\Operation\Signers;

use Bitrix\Main;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Repository\SignersList\SignersListRepository;
use Bitrix\Sign\Result\Result;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\SignersListService;

class DeleteList implements Contract\Operation
{
	private readonly SignersListService $signersListService;

	public function __construct(
		private readonly Item\SignersList $list,
		?SignersListService $signersListService = null,
	)
	{
		$container = Container::instance();
		$this->signersListService = $signersListService ?? $container->getSignersListService();
	}

	public function launch(): Main\Result
	{
		$invariantCheckResult = $this->check();
		if (!$invariantCheckResult->isSuccess())
		{
			return $invariantCheckResult;
		}

		return $this->signersListService->deleteListById($this->list->id);
	}

	private function check(): Main\Result
	{
		if ($this->list->id === null)
		{
			return Result::createByErrorData(message: 'List not found');
		}

		return new Main\Result();
	}
}
