<?php

namespace Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible\RemoveService;

use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Responsible\Agent\RemoveUserFromFlowResponsible\RemoveService\Trait\RemoveResponsibleTrait;

class HimselfRemoveResponsibleService extends AbstractRemoveResponsibleService
{
	use RemoveResponsibleTrait;

	protected function getResponsibleRole(): Role
	{
		return Role::HIMSELF_ASSIGNED;
	}

	protected function onMigrateToManualDistributor(int $flowId): void
	{
		if (FlowFeature::isOn())
		{
			$this->notificationService->onSwitchToManualDistribution($flowId);
		}
	}
}