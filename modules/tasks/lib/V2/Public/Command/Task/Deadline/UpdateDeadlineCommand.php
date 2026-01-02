<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Deadline;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\Rule\Min;
use Bitrix\Main\Validation\Rule\PositiveNumber;
use Bitrix\Tasks\Deadline\Policy\DeadlinePolicy;
use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Internal\Result\Result;
use Bitrix\Tasks\V2\Public\Command\AbstractCommand;
use Exception;

class UpdateDeadlineCommand extends AbstractCommand
{
	public function __construct(
		#[PositiveNumber]
		public readonly int $taskId,
		#[Min(0)]
		public readonly int $deadlineTs,
		public readonly UpdateConfig $updateConfig,
		public readonly ?string $reason = null,
	)
	{

	}

	protected function executeInternal(): Result
	{
		$result = new Result();

		$consistencyResolver = Container::getInstance()->getConsistencyResolver();
		$updateService = Container::getInstance()->getUpdateService();
		$deadLineUserOption = Container::getInstance()
			->getDeadlineUserOptionRepository()
			->getByUserId($this->updateConfig->getUserId())
		;
		$deadLineLogRepository = Container::getInstance()->getDeadlineLogRepository();
		$deadLinePolicy = new DeadlinePolicy(
			canChangeDeadline: $deadLineUserOption->canChangeDeadline,
			dateTime: $deadLineUserOption->maxDeadlineChangeDate,
			maxDeadlineChanges: $deadLineUserOption->maxDeadlineChanges,
			requireDeadlineChangeReason: $deadLineUserOption->requireDeadlineChangeReason
		);

		$updateDeadlineHandler = new UpdateDeadlineHandler(
			$consistencyResolver,
			$updateService,
			$deadLinePolicy,
			$deadLineLogRepository,
		);

		try
		{
			$task = $updateDeadlineHandler($this);

			return $result->setObject($task);
		}
		catch (Exception $e)
		{
			return $result->addError(Error::createFromThrowable($e));
		}
	}
}
