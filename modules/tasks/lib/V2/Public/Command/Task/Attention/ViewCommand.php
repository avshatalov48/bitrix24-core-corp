<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Attention;

use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;

class ViewCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int  $taskId,
		#[PositiveNumber]
		public readonly int  $userId,
		public readonly ?int $viewedTs = null,
		public readonly bool $isRealView = false,
		public readonly bool $sendPush = true,
		public readonly bool $updateTopicLastVisit = true,
	)
	{

	}

	protected function executeInternal(): Result
	{
		$viewService = Container::getInstance()->getViewService();

		$handler = new ViewHandler($viewService);

		$handler($this);

		return new Result();
	}
}
