<?php

namespace Bitrix\Tasks\Flow\Provider;

use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionServicesFactory;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Internal\Entity\FlowMemberCollection;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Internal\FlowMemberTable;
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

	public function getMemberAccessCodesByRole(int $flowId, Role $role): array
	{
		$flow = FlowRegistry::getInstance()->get($flowId);
		if (!$flow)
		{
			return [];
		}

		return match ($role)
		{
			Role::MANUAL_DISTRIBUTOR,
			Role::QUEUE_ASSIGNEE,
			Role::HIMSELF_ASSIGNED => $this->getResponsibleAccessCodes($flowId),
			Role::TASK_CREATOR => $this->getTaskCreatorAccessCodes($flowId),
			Role::OWNER => ["U{$flow->getOwnerId()}"],
			Role::CREATOR => ["U{$flow->getCreatorId()}"],
			default => [],
		};
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

	public function getRelationsByAccessCode(string $accessCode, int $limit = 80): FlowMemberCollection
	{
		if (!AccessCode::isValid($accessCode))
		{
			return new FlowMemberCollection();
		}

		$query = FlowMemberTable::query()
			->setSelect(['FLOW_ID', 'ROLE'])
			->where($this->getAccessCodeFilter($accessCode))
			->setLimit($limit);

		return $query->fetchCollection();
	}

	private function getAccessCodeFilter(string $accessCode): ConditionTree
	{
		$filter = Query::filter();

		$isDepartmentAccessCode = str_starts_with($accessCode, 'D');
		if (!$isDepartmentAccessCode)
		{
			$filter->where('ACCESS_CODE', $accessCode);

			return $filter;
		}

		$departmentId = (new AccessCode($accessCode))->getEntityId();

		$filter
			->logic('OR')
			->where('ACCESS_CODE', "D{$departmentId}")
			->where('ACCESS_CODE', "DR{$departmentId}")
		;

		return $filter;
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