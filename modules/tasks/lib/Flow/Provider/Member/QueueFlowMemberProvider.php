<?php

namespace Bitrix\Tasks\Flow\Provider\Member;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Internal\FlowMemberTable;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;

class QueueFlowMemberProvider extends AbstractFlowMemberProvider
{
	public function getResponsibleRole(): Role
	{
		return Role::QUEUE_ASSIGNEE;
	}

	public function getDistributionType(): FlowDistributionType
	{
		return FlowDistributionType::QUEUE;
	}

	/**
	 * @throws ProviderException
	 * @return string[]
	 * Return [those selected in the flow team selector to receive tasks]
	 */
	public function getResponsibleAccessCodes(int $flowId): array
	{
		return $this->getTeamAccessCodes($flowId);
	}

	/**
	 * @throws ProviderException
	 * @return string[]
	 * Return [those selected in the flow team selector to receive tasks]
	 */
	public function getTeamAccessCodes(int $flowId, ?int $offset = null, ?int $limit = null): array
	{
		return $this->getMemberAccessCodesByRole($this->getResponsibleRole(), $flowId, $offset, $limit);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getTeamCount(FlowCollection $flows): array
	{
		$flowCollection = $flows->filter($this->getDistributionType());

		if ($flowCollection->isEmpty())
		{
			return [];
		}

		$counts = FlowMemberTable::query()
			->setSelect(['FLOW_ID', Query::expr('TOTAL_COUNT')->countDistinct('ID')])
			->whereIn('FLOW_ID', $flowCollection->getIdList())
			->where('ROLE', $this->getResponsibleRole()->value)
			->where('USER.ACTIVE', 'Y')
			->addGroup('FLOW_ID')
			->exec()
			->fetchAll();

		$teams = [];
		foreach ($counts as $count)
		{
			$teams[(int)$count['FLOW_ID']] = (int)$count['TOTAL_COUNT'];
		}

		return $teams;
	}
}