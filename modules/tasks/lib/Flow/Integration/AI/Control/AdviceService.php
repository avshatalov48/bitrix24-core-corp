<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Control;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Tasks\Flow\Integration\AI\Control\Command\DeleteCommand;
use Bitrix\Tasks\Flow\Integration\AI\Control\Command\ReplaceAdviceCommand;
use Bitrix\Tasks\Flow\Internal\FlowCopilotAdviceTable;
use Bitrix\Tasks\Internals\Log\LogFacade;

class AdviceService
{
	protected const LOCK = 15;

	protected Connection $connection;

	public function __construct()
	{
		$this->init();
	}

	public function replace(ReplaceAdviceCommand $command): void
	{
		$command->validateAdd();

		$this->update($command);
	}

	public function delete(DeleteCommand $command): void
	{
		$command->validateDelete();

		FlowCopilotAdviceTable::delete($command->flowId);
	}

	public function onFlowDeleted(Event $event): EventResult
	{
		$flowId = (int)$event->getParameter('flow');
		if ($flowId <= 0)
		{
			return new EventResult(EventResult::ERROR);
		}

		FlowCopilotAdviceTable::delete($flowId);

		return new EventResult(EventResult::SUCCESS);
	}

	protected function update(ReplaceAdviceCommand $command): void
	{
		try
		{
			if ($this->lock($command->flowId))
			{
				$deleteCommand = (new DeleteCommand())->setFlowId($command->flowId);
				$this->delete($deleteCommand);

				$result = FlowCopilotAdviceTable::add([
					'FLOW_ID' => $command->flowId,
					'ADVICE' => $command->advice,
				]);

				if (!$result->isSuccess())
				{
					LogFacade::logErrors($result->getErrorCollection());
				}
			}
		}
		catch (\Throwable $t)
		{
			LogFacade::logThrowable($t);
		}
		finally
		{
			$this->unlock($command->flowId);
		}
	}

	protected function lock(int $flowId): bool
	{
		return $this->connection->lock('tasks_flow_copilot_advice_' . $flowId, static::LOCK);
	}

	protected function unlock(int $flowId): void
	{
		$this->connection->unlock('tasks_flow_copilot_advice_' . $flowId);
	}

	protected function init(): void
	{
		$this->connection = Application::getConnection();
	}
}
