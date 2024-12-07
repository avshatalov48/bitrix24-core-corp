<?php

namespace Bitrix\Tasks\Flow\Responsible\Distributor;

use Bitrix\Tasks\Flow\Control\Command\UpdateCommand;
use Bitrix\Tasks\Flow\Control\FlowService;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Notification\NotificationService;
use Bitrix\Tasks\Flow\Option\OptionDictionary;
use Bitrix\Tasks\Flow\Option\OptionService;
use Bitrix\Tasks\Util\User;

class ManualDistributorStrategy implements DistributorStrategyInterface
{
	public function distribute(Flow $flow): int
	{
		$distributorOption =
			OptionService::getInstance()
				->getOption($flow->getId(), OptionDictionary::MANUAL_DISTRIBUTOR_ID->value)
		;

		$manualDistributorId = $distributorOption->getValue();

		if ($manualDistributorId <= 0 || $this->isUserAbsent($manualDistributorId))
		{
			$manualDistributorId = $flow->getOwnerId();
			$this->setFlowOwnerAsManualDistributor($flow);
		}

		return $manualDistributorId;
	}

	private function isUserAbsent(int $userId): bool
	{
		return !empty(User::isAbsence([$userId]));
	}

	private function setFlowOwnerAsManualDistributor(Flow $flow): void
	{
		$command =
			(new UpdateCommand())
				->setId($flow->getId())
				->setDistributionType(Flow::DISTRIBUTION_TYPE_MANUALLY)
				->setManualDistributorId($flow->getOwnerId())
		;
		(new FlowService())->update($command);
		(new NotificationService())->onForcedManualDistributorAbsentChange($flow->getId());
	}
}