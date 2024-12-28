<?php

namespace Bitrix\Tasks\Flow\Provider\Member;

use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Internal\FlowMemberTable;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Bitrix\Tasks\Flow\Provider\Member\Trait\FlowMemberTrait;

abstract class AbstractFlowMemberProvider
{
	use FlowMemberTrait;

	/**
	 * @throws ProviderException
	 */
	public function getTaskCreatorAccessCodes(int $flowId, ?int $offset = null, ?int $limit = null): array
	{
		try
		{
			$query = FlowMemberTable::query()
				->setSelect(['ID', 'ACCESS_CODE'])
				->where('FLOW_ID', $flowId)
				->where('ROLE', Role::TASK_CREATOR->value)
				->setOffset($offset)
				->setLimit($limit)
			;

			return $query->exec()->fetchCollection()->getAccessCodeList();
		}
		catch (SystemException $e)
		{
			throw new ProviderException($e->getMessage());
		}
	}

	/**
	 * @return string[]
	 */
	abstract public function getTeamAccessCodes(int $flowId, ?int $offset = null, ?int $limit = null): array;

	/**
	 * Those responsible for progress in completing tasks in the flow.
	 * @return string[]
	 */
	abstract public function getResponsibleAccessCodes(int $flowId): array;

	/**
	 * @return array [
	 * 		flowId => usersCount
	 * ]
	 */
	abstract public function getTeamCount(FlowCollection $flows): array;

	abstract public function getResponsibleRole(): Role;
	abstract public function getDistributionType(): FlowDistributionType;
}