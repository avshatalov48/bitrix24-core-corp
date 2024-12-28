<?php

namespace Bitrix\Tasks\Flow\Efficiency;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Event;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Exception\CommandNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Control\Exception\FlowNotUpdatedException;
use Bitrix\Tasks\Flow\Control\FlowService;
use Bitrix\Tasks\Flow\Efficiency\Command\EfficiencyCommand;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\InvalidCommandException;
use Psr\Container\NotFoundExceptionInterface;

class EfficiencyService
{
	protected EfficiencyCommand $command;

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws FlowNotUpdatedException
	 * @throws ObjectNotFoundException
	 * @throws CommandNotFoundException
	 * @throws SqlQueryException
	 * @throws FlowNotFoundException
	 * @throws InvalidCommandException
	 */
	public function update(EfficiencyCommand $command): Flow
	{
		$this->command = $command;
		$this->command->validateAdd();

		$flow = $this->updateEfficiency();

		$this->fireEvent($flow);

		return $flow;
	}

	/**
	 * @throws NotFoundExceptionInterface
	 * @throws FlowNotUpdatedException
	 * @throws ObjectNotFoundException
	 * @throws CommandNotFoundException
	 * @throws SqlQueryException
	 * @throws FlowNotFoundException
	 * @throws InvalidCommandException
	 */
	protected function updateEfficiency(): Flow
	{
		$flowCommand = (new UpdateCommand())
			->setId($this->command->flowId)
			->setEfficiency($this->command->newEfficiency)
			->disablePush();

		$service = ServiceLocator::getInstance()->get('tasks.flow.service');
		return $service->update($flowCommand);
	}

	protected function fireEvent(Flow $flow): void
	{
		$event = new Event('tasks', 'onFlowEfficiencyChanged', [
			'flow' => $flow,
			'efficiencyBefore' => $this->command->oldEfficiency,
		]);

		$event->send();
	}
}