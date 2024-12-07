<?php

namespace Bitrix\Tasks\Flow\Provider\FlowTeam;

use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Internal\FlowMemberTable;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;

class QueueFlowTeamProvider implements FlowTeamProviderInterface
{
	/**
	 * @throws ProviderException
	 */
	public function getTeamMembers(Flow $flow, ?int $offset = null, ?int $limit = null): array
	{
		$flowId = $flow->getId();

		try
		{
			$query = FlowMemberTable::query()
				->setSelect(['ID', 'ACCESS_CODE'])
				->where('FLOW_ID', $flowId)
				->where('ROLE', Role::QUEUE_ASSIGNEE->value)
				->setOffset($offset)
				->setLimit($limit);

			$accessCodes = $query->exec()->fetchCollection()->getAccessCodeList();
		}
		catch (SystemException $e)
		{
			throw new ProviderException($e->getMessage());
		}

		$userIds = [];
		foreach ($accessCodes as $accessCode)
		{
			$access = new AccessCode($accessCode);
			$userIds[] = $access->getEntityId();
		}

		return $userIds;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getTeamCount(FlowCollection $flows): array
	{
		$flows = $flows->getQueueFlows();

		if ($flows->isEmpty())
		{
			return [];
		}

		$counts = FlowMemberTable::query()
			->setSelect(['FLOW_ID',  Query::expr('TOTAL_COUNT')->countDistinct('ID')])
			->whereIn('FLOW_ID', $flows->getIdList())
			->where('ROLE', Role::QUEUE_ASSIGNEE->value)
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