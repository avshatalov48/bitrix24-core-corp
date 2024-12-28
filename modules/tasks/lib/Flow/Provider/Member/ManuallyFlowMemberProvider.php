<?php

namespace Bitrix\Tasks\Flow\Provider\Member;

use Bitrix\Main\Access\AccessCode;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;

class ManuallyFlowMemberProvider extends AbstractFlowMemberProvider
{
	public function getResponsibleRole(): Role
	{
		return Role::MANUAL_DISTRIBUTOR;
	}

	public function getDistributionType(): FlowDistributionType
	{
		return FlowDistributionType::MANUALLY;
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 * @throws LoaderException
	 */
	public function getTeamCount(FlowCollection $flows): array
	{
		$flows = $flows->filter($this->getDistributionType());

		if ($flows->isEmpty() || !Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		$counts = UserToGroupTable::query()
			->setSelect(['GROUP_ID', Query::expr('TOTAL_COUNT')->countDistinct('USER_ID')])
			->whereIn('GROUP_ID', array_unique($flows->getGroupIdList()))
			->where('USER.ACTIVE', 'Y')
			->setGroup(['GROUP_ID'])
			->exec()
			->fetchAll();

		$flowCounts = [];

		foreach ($counts as $count)
		{
			$totalCountWithoutManualDistributorId = (int)$count['TOTAL_COUNT'] - 1;
			$flowCounts[(int)$count['GROUP_ID']] = max($totalCountWithoutManualDistributorId, 0);
		}

		$teams = [];

		foreach ($flows as $flow)
		{
			if (!isset($flowCounts[$flow->getGroupId()]))
			{
				continue;
			}

			$teams[$flow->getId()] = (int)$flowCounts[$flow->getGroupId()];
		}

		return $teams;
	}

	/**
	 * @throws ProviderException
	 * @return string[]
	 * Return ["U${manualDistributorId}"]
	 */
	public function getResponsibleAccessCodes(int $flowId): array
	{
		return $this->getMemberAccessCodesByRole($this->getResponsibleRole(), $flowId);
	}

	/**
	 * @throws ProviderException
	 * @throws LoaderException
	 * @return string[]
	 * Return [group members without manualDistributor]
	 */
	public function getTeamAccessCodes(int $flowId, ?int $offset = null, ?int $limit = null): array
	{
		$flow = FlowRegistry::getInstance()->get($flowId);
		if ($flow === null)
		{
			return [];
		}

		$manualDistributorAccessCode = new AccessCode($this->getResponsibleAccessCodes($flowId)[0]);
		$manualDistributorId = $manualDistributorAccessCode->getEntityId();

		return $this->getGroupMembersAccessCodes($flow->getGroupId(), $offset, $limit, [$manualDistributorId]);
	}

	/**
	 * @return string[]
	 * @throws ProviderException
	 * @throws LoaderException
	 */
	private function getGroupMembersAccessCodes(
		int $groupId,
		?int $offset = null,
		?int $limit = null,
		array $excludeUserIds = []
	): array
	{
		$memberIdList = $this->getGroupMemberIds($groupId, $offset, $limit, $excludeUserIds);

		return array_map(static fn($memberId) => "U{$memberId}", $memberIdList);
	}

	/**
	 * @return int[]
	 * @throws LoaderException
	 * @throws ProviderException
	 */
	private function getGroupMemberIds(
		int $groupId,
		?int $offset = null,
		?int $limit = null,
		array $excludeUserIds = []
	): array
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		try
		{
			$query = UserToGroupTable::query()
				->setSelect(['ID', 'USER_ID'])
				->where('GROUP_ID', $groupId)
				->where('USER.ACTIVE', 'Y')
				->whereNotIn('USER_ID', $excludeUserIds)
				->setOffset($offset)
				->setLimit($limit)
			;

			return $query->exec()->fetchCollection()->getUserIdList();
		}
		catch (SystemException $e)
		{
			throw new ProviderException($e->getMessage());
		}
	}
}