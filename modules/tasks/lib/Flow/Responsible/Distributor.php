<?php

namespace Bitrix\Tasks\Flow\Responsible;

use Bitrix\Tasks\Flow\Distribution\FlowDistributionServicesFactory;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Responsible\Distributor\DistributorStrategyInterface;

class Distributor
{
	public function generateResponsible(Flow $flow, array $fields, array $taskData): Responsible
	{
		$strategy = $this->getDistributorStrategy($flow);
		$responsibleId = $strategy->distribute($flow, $fields, $taskData);

		return new Responsible($responsibleId, $flow->getId());
	}

	private function getDistributorStrategy(Flow $flow): DistributorStrategyInterface
	{
		return (new FlowDistributionServicesFactory($flow->getDistributionType()))
			->getDistributorStrategy()
		;
	}
}