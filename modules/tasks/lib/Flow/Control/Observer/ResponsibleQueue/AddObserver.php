<?php

namespace Bitrix\Tasks\Flow\Control\Observer\ResponsibleQueue;

use Bitrix\Tasks\Flow\Control\Command\AddCommand;
use Bitrix\Tasks\Flow\Control\Observer\AddObserverInterface;
use Bitrix\Tasks\Flow\Control\Observer\Trait\AddUsersToGroupTrait;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Responsible\ResponsibleQueue\ResponsibleQueueService;

final class AddObserver implements AddObserverInterface
{
	use AddUsersToGroupTrait;

	protected ResponsibleQueueService $responsibleQueueService;
	public function __construct()
	{
		$this->responsibleQueueService = ResponsibleQueueService::getInstance();
	}

	public function update(AddCommand $command, FlowEntity $flowEntity): void
	{
		if (
			$flowEntity->getDistributionType() === Flow::DISTRIBUTION_TYPE_QUEUE
			&& !empty($command->responsibleQueue)
		)
		{
			$this->responsibleQueueService->save($flowEntity->getId(), $command->responsibleQueue);
			$this->addUsersToGroup($flowEntity->getGroupId(), $command->responsibleQueue);
		}
	}
}