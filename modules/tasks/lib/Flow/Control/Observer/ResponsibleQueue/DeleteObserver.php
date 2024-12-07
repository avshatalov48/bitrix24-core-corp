<?php

namespace Bitrix\Tasks\Flow\Control\Observer\ResponsibleQueue;

use Bitrix\Tasks\Flow\Control\Observer\DeleteObserverInterface;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use Bitrix\Tasks\Flow\Responsible\ResponsibleQueue\ResponsibleQueueService;

final class DeleteObserver implements DeleteObserverInterface
{
	protected ResponsibleQueueService $responsibleQueueService;
	public function __construct()
	{
		$this->responsibleQueueService = ResponsibleQueueService::getInstance();
	}

	public function update(FlowEntity $flowEntity): void
	{
		if ($flowEntity->getDistributionType() === Flow::DISTRIBUTION_TYPE_QUEUE)
		{
			$this->responsibleQueueService->delete($flowEntity->getId());
		}
	}
}