<?php

namespace Bitrix\Tasks\Flow\Provider;

use Bitrix\Tasks\Flow\Distribution\FlowDistributionServicesFactory;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Provider\Exception\FlowNotFoundException;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;

class FlowMemberFacade
{
	private FlowProvider $flowProvider;

	public function __construct()
	{
		$this->flowProvider = new FlowProvider();
	}

	/**
	 * @throws FlowNotFoundException
	 */
	public function getTeamCount(array $flowIdList): array
	{
		FlowRegistry::getInstance()->load($flowIdList,  ['DISTRIBUTION_TYPE', 'GROUP_ID']);

		$flows = new FlowCollection();
		foreach ($flowIdList as $flowId)
		{
			$flows->add($this->getFlow($flowId));
		}

		$teamCount = [];
		foreach (FlowDistributionType::cases() as $distributionType)
		{
			$teamCount += (new FlowDistributionServicesFactory($distributionType))
				->getMemberProvider()
				->getTeamCount($flows)
			;
		}

		return $teamCount;
	}

	/**
	 * @throws FlowNotFoundException
	 * @throws ProviderException
	 * @return string[]
	 */
	public function getTaskCreatorAccessCodes(int $flowId, ?int $offset = null, ?int $limit = null): array
	{
		$distributionType = $this->getDistributionTypeByFlowId($flowId);

		return (new FlowDistributionServicesFactory($distributionType))
			->getMemberProvider()
			->getTaskCreatorAccessCodes($flowId, $offset, $limit)
		;
	}

	/**
	 * @throws FlowNotFoundException
	 * @return string[]
	 */
	public function getTeamAccessCodes(int $flowId, ?int $offset = null, ?int $limit = null): array
	{
		$distributionType = $this->getDistributionTypeByFlowId($flowId);

		return (new FlowDistributionServicesFactory($distributionType))
			->getMemberProvider()
			->getTeamAccessCodes($flowId, $offset, $limit)
		;
	}

	/**
	 * @throws FlowNotFoundException
	 * @return string[]
	 */
	public function getResponsibleAccessCodes(int $flowId): array
	{
		$distributionType = $this->getDistributionTypeByFlowId($flowId);

		return (new FlowDistributionServicesFactory($distributionType))
			->getMemberProvider()
			->getResponsibleAccessCodes($flowId)
		;
	}

	/**
	 * @throws FlowNotFoundException
	 */
	private function getDistributionTypeByFlowId(int $flowId): FlowDistributionType
	{
		return $this
			->getFlow($flowId)
			->getDistributionType()
		;
	}

	/**
	 * @throws FlowNotFoundException
	 */
	private function getFlow(int $flowId): Flow
	{
		return $this
			->flowProvider
			->getFlow($flowId, ['DISTRIBUTION_TYPE', 'GROUP_ID'])
		;
	}
}