<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Migration\Strategy\Trait;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Migration\Strategy\Result\StrategyResult;

trait SaveFlowMembersTrait
{
	protected function saveFlowMembers(int $flowId, array $members, Role $role): StrategyResult
	{
		$result = new StrategyResult();

		$flow = FlowRegistry::getInstance()->get($flowId);
		if (!$flow)
		{
			$result->addError(new Error("Flow {$flowId} not found."));
		}

		$command = $this->createFlowUpdateCommand($flowId, $members, $role);
		if (!$command)
		{
			$result->addError(new Error('Failed to create a flow update command.'));
		}

		$flowService = ServiceLocator::getInstance()->get('tasks.flow.service');
		$flowService->update($command);

		return $result->setFlowChanged();
	}

	protected function createFlowUpdateCommand(int $flowId, array $members, Role $role): ?UpdateCommand
	{
		if (empty($members))
		{
			return null;
		}

		$command = (new UpdateCommand())->setId($flowId);

		return match ($role)
		{
			Role::MANUAL_DISTRIBUTOR,
			Role::HIMSELF_ASSIGNED,
			Role::QUEUE_ASSIGNEE => $command->setResponsibleList($members),
			Role::TASK_CREATOR => $command->setTaskCreators($members),
			Role::OWNER => $command->setOwnerId($members[0]),
			default => null,
		};
	}
}
