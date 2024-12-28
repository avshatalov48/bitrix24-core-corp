<?php

namespace Bitrix\Tasks\Flow\Provider\Member;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Distribution\FlowDistributionType;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Internal\FlowMemberTable;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;

class HimselfFlowMemberProvider extends AbstractFlowMemberProvider
{
	public function getResponsibleRole(): Role
	{
		return Role::HIMSELF_ASSIGNED;
	}

	public function getDistributionType(): FlowDistributionType
	{
		return FlowDistributionType::HIMSELF;
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
	 * @throws LoaderException
	 */
	public function getTeamCount(FlowCollection $flows): array
	{
		$flowCollection = $flows->filter($this->getDistributionType());

		if ($flowCollection->isEmpty())
		{
			return [];
		}

		$memberAccessCodes = FlowMemberTable::query()
			->setSelect(['FLOW_ID', 'ACCESS_CODE'])
			->whereIn('FLOW_ID', $flowCollection->getIdList())
			->where('ROLE', $this->getResponsibleRole()->value)
			->exec()
			->fetchAll()
		;

		$accessCodesByFlow = [];
		foreach ($memberAccessCodes as $accessCode)
		{
			$accessCodesByFlow[(int)$accessCode['FLOW_ID']][] = $accessCode['ACCESS_CODE'];
		}

		$teamCountList = [];
		foreach ($accessCodesByFlow as $flowId => $accessCodeList)
		{
			$userIdsList = (new AccessCodeConverter(...$accessCodeList))
				->getUserIds()
			;

			$teamCountList[$flowId] = count($userIdsList);
		}

		return $teamCountList;
	}
}