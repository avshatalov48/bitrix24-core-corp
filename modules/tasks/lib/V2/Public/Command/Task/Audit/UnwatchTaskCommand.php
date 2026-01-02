<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Audit;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Exception;

class UnwatchTaskCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $taskId,
		#[PositiveNumber]
		public readonly int $userId,
		#[PositiveNumber]
		public readonly int $auditorId,
	)
	{

	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		$memberRepository = Container::getInstance()->getTaskMemberRepository();
		$updateTaskService = Container::getInstance()->getUpdateTaskService();

		$handler = new UnwatchTaskHandler(
			$memberRepository,
			$updateTaskService,
		);

		try
		{
			$handler($this);
		}
		catch (Exception $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}

		return $result;
	}
}
