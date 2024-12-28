<?php

namespace Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible\RemoveService;

use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible\RemoveService\Trait\RemoveResponsibleTrait;

class QueueRemoveResponsibleService extends AbstractRemoveResponsibleService
{
	use RemoveResponsibleTrait;

	protected function getResponsibleRole(): Role
	{
		return Role::QUEUE_ASSIGNEE;
	}

	protected function onMigrateToManualDistributor(int $flowId): void
	{
		if (FlowFeature::isOn())
		{
			$this->notificationService->onSwitchToManualDistribution($flowId);
		}
	}
}