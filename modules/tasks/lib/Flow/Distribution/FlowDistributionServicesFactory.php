<?php

namespace Bitrix\Tasks\Flow\Distribution;

use Bitrix\Tasks\Flow\Provider\Member\AbstractFlowMemberProvider;
use Bitrix\Tasks\Flow\Provider\Member\HimselfFlowMemberProvider;
use Bitrix\Tasks\Flow\Provider\Member\ImmutableFlowMemberProvider;
use Bitrix\Tasks\Flow\Provider\Member\ManuallyFlowMemberProvider;
use Bitrix\Tasks\Flow\Provider\Member\QueueFlowMemberProvider;
use Bitrix\Tasks\Flow\Responsible\Decorator\AppendFlowTeamMemberDecorator;
use Bitrix\Tasks\Flow\Responsible\Distributor\DistributorStrategyInterface;
use Bitrix\Tasks\Flow\Responsible\Distributor\HimselfDistributorStrategy;
use Bitrix\Tasks\Flow\Responsible\Distributor\ManualDistributorStrategy;
use Bitrix\Tasks\Flow\Responsible\Distributor\QueueDistributorStrategy;
use Bitrix\Tasks\Flow\Responsible\Distributor\ImmutableDistributorStrategy;
use Bitrix\Tasks\Flow\Provider\Field\FlowFieldProviderInterface;
use Bitrix\Tasks\Flow\Provider\Field\BaseFlowFieldProvider;
use Bitrix\Tasks\Flow\Provider\Field\ImmutableFlowFieldProvider;

class FlowDistributionServicesFactory
{
	private FlowDistributionType $distributionType;

	public function __construct(FlowDistributionType $distributionType)
	{
		$this->distributionType = $distributionType;
	}
	
	public function getDistributorStrategy(): DistributorStrategyInterface
	{
		return match ($this->distributionType)
		{
			FlowDistributionType::MANUALLY => new ManualDistributorStrategy(),
			FlowDistributionType::QUEUE => new QueueDistributorStrategy(),
			FlowDistributionType::HIMSELF => new HimselfDistributorStrategy(),
			FlowDistributionType::IMMUTABLE => new AppendFlowTeamMemberDecorator(
				new ImmutableDistributorStrategy(),
			),
		};
	}

	public function getMemberProvider(): AbstractFlowMemberProvider
	{
		return match ($this->distributionType)
		{
			FlowDistributionType::MANUALLY => new ManuallyFlowMemberProvider(),
			FlowDistributionType::QUEUE => new QueueFlowMemberProvider(),
			FlowDistributionType::HIMSELF => new HimselfFlowMemberProvider(),
			FlowDistributionType::IMMUTABLE => new ImmutableFlowMemberProvider(),
		};
	}

	public function getFieldsProvider(): FlowFieldProviderInterface
	{
		return match ($this->distributionType)
		{
			FlowDistributionType::MANUALLY => new BaseFlowFieldProvider(),
			FlowDistributionType::QUEUE => new BaseFlowFieldProvider(),
			FlowDistributionType::HIMSELF => new BaseFlowFieldProvider(),
			FlowDistributionType::IMMUTABLE => new ImmutableFlowFieldProvider(),
		};
	}
}