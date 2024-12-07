<?php

namespace Bitrix\Tasks\Flow\Control\Observer\ResponsibleQueue;

use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\Observer\Trait\AddUsersToGroupTrait;
use Bitrix\Tasks\Flow\Control\Observer\UpdateObserverInterface;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Responsible\ResponsibleQueue\ResponsibleQueueService;

final class UpdateObserver implements UpdateObserverInterface
{
	use AddUsersToGroupTrait;

	protected ResponsibleQueueService $responsibleQueueService;
	public function __construct()
	{
		$this->responsibleQueueService = ResponsibleQueueService::getInstance();
	}

	public function update(UpdateCommand $command, FlowEntity $flowEntity, FlowEntity $flowEntityBeforeUpdate): void
	{
		$isDistributionTypeChanged = $flowEntity->getDistributionType() !== $flowEntityBeforeUpdate->getDistributionType();

		if ($isDistributionTypeChanged && $flowEntityBeforeUpdate->getDistributionType() === Flow::DISTRIBUTION_TYPE_QUEUE)
		{
			$this->responsibleQueueService->delete($flowEntity->getId());
		}
		elseif ($flowEntity->getDistributionType() === Flow::DISTRIBUTION_TYPE_QUEUE && $command->responsibleQueue)
		{
			$this->responsibleQueueService->save($flowEntity->getId(), $command->responsibleQueue);
			$this->addUsersToGroup($flowEntity->getGroupId(), $command->responsibleQueue);
		}
	}
}