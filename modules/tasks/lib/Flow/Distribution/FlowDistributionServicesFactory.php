<?php

namespace Bitrix\Tasks\Flow\Distribution;

use Bitrix\Tasks\Flow\Provider\FlowTeam\FlowTeamProviderInterface;
use Bitrix\Tasks\Flow\Provider\FlowTeam\ManuallyFlowTeamProvider;
use Bitrix\Tasks\Flow\Provider\FlowTeam\NullFlowTeamProvider;
use Bitrix\Tasks\Flow\Provider\FlowTeam\QueueFlowTeamProvider;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Responsible\Distributor\DistributorStrategyInterface;
use Bitrix\Tasks\Flow\Responsible\Distributor\ManualDistributorStrategy;
use Bitrix\Tasks\Flow\Responsible\Distributor\NullDistributorStrategy;
use Bitrix\Tasks\Flow\Responsible\Distributor\QueueDistributorStrategy;

class FlowDistributionServicesFactory
{
	private string $flowType;

	public function __construct(string $flowType)
	{
		$this->flowType = $flowType;
	}

	public function getDistributorStrategy(): DistributorStrategyInterface
	{
		return match ($this->flowType)
		{
			Flow::DISTRIBUTION_TYPE_MANUALLY => new ManualDistributorStrategy(),
			Flow::DISTRIBUTION_TYPE_QUEUE => new QueueDistributorStrategy(),
			default => new NullDistributorStrategy(),
		};
	}

	public function getFlowTeamProvider(): FlowTeamProviderInterface
	{
		return match ($this->flowType)
		{
			Flow::DISTRIBUTION_TYPE_MANUALLY => new ManuallyFlowTeamProvider(),
			Flow::DISTRIBUTION_TYPE_QUEUE => new QueueFlowTeamProvider(),
			default => new NullFlowTeamProvider(),
		};
	}
}