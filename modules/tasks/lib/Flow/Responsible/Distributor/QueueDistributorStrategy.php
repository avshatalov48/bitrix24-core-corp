<?php

namespace Bitrix\Tasks\Flow\Responsible\Distributor;

use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\FlowService;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Notification\NotificationService;
use Bitrix\Tasks\Flow\Option\OptionDictionary;
use Bitrix\Tasks\Flow\Option\OptionService;
use Bitrix\Tasks\Flow\Provider\OptionProvider;
use Bitrix\Tasks\Flow\Responsible\ResponsibleQueue\ResponsibleQueueService;

class QueueDistributorStrategy implements DistributorStrategyInterface
{
	public function distribute(Flow $flow): int
	{
		$nextResponsibleId = ResponsibleQueueService::getInstance()->getNextResponsibleId($flow);

		if ($nextResponsibleId > 0)
		{
			$optionName = OptionDictionary::RESPONSIBLE_QUEUE_LATEST_ID->value;
			OptionService::getInstance()->save($flow->getId(), $optionName, (string)$nextResponsibleId);
		}
		else
		{
			$nextResponsibleId = $flow->getOwnerId();
			$this->switchToManualDistribution($flow);
		}

		return $nextResponsibleId;
	}

	private function switchToManualDistribution(Flow $flow): void
	{
		$command =
			(new UpdateCommand())
				->setId($flow->getId())
				->setDistributionType(Flow::DISTRIBUTION_TYPE_MANUALLY)
				->setManualDistributorId($flow->getOwnerId())
		;
		(new FlowService())->update($command);
		(new NotificationService())->onSwitchToManualDistributionAbsent($flow->getId());
	}
}