<?php

namespace Bitrix\Tasks\Flow\Provider;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionServicesFactory;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Internal\FlowMemberTable;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;

class MembersProvider
{
	private FlowProvider $flowProvider;

	public function __construct()
	{
		$this->init();
	}

	public function getTeamCount(FlowCollection $flows): array
	{
		$teamCount = [];
		foreach (Flow::getDistributionTypesList() as $distributionType)
		{
			$teamCount += $this->getTeamCountByFlowType($distributionType, $flows);
		}

		return $teamCount;
	}

	private function getTeamCountByFlowType(string $flowType, FlowCollection $flows): array
	{
		$flowDistributionServicesFactory = new FlowDistributionServicesFactory($flowType);

		return $flowDistributionServicesFactory->getFlowTeamProvider()->getTeamCount($flows);
	}

	/**
	 * @throws ProviderException
	 */
	public function getAssignees(int $flowId, ?int $offset = null, ?int $limit = null): array
	{
		$flow = $this->flowProvider->getFlow($flowId);
		$flowType = $flow->getDistributionType();
		$flowDistributionServicesFactory = new FlowDistributionServicesFactory($flowType);

		return $flowDistributionServicesFactory->getFlowTeamProvider()->getTeamMembers($flow, $offset, $limit);
	}

	/**
	 * @throws ProviderException
	 */
	public function getTaskCreators(int $flowId, ?int $offset = null, ?int $limit = null): array
	{
		try
		{
			$query = FlowMemberTable::query()
				->setSelect(['ID', 'ACCESS_CODE'])
				->where('FLOW_ID', $flowId)
				->where('ROLE', Role::TASK_CREATOR->value)
				->setOffset($offset)
				->setLimit($limit);

			return $query->exec()->fetchCollection()->getAccessCodeList();
		}
		catch (SystemException $e)
		{
			throw new ProviderException($e->getMessage());
		}
	}

	private function init(): void
	{
		$this->flowProvider = new FlowProvider();
	}
}