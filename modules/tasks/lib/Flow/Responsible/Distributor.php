<?php

namespace Bitrix\Tasks\Flow\Responsible;

use Bitrix\Tasks\Flow\Distribution\FlowDistributionServicesFactory;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Responsible\Distributor\DistributorStrategyInterface;

class Distributor
{
	public function generateResponsible(Flow $flow): Responsible
	{
		$strategy = $this->getDistributorStrategy($flow);
		$responsibleId = $strategy->distribute($flow);

		return new Responsible($responsibleId, $flow->getId());
	}

	private function getDistributorStrategy(Flow $flow): DistributorStrategyInterface
	{
		$flowType = $flow->getDistributionType();

		return (new FlowDistributionServicesFactory($flowType))->getDistributorStrategy();
	}
}
