<?php

namespace Bitrix\Tasks\Flow\Provider\Member\Trait;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\FlowCollection;
use Bitrix\Tasks\Flow\Integration\HumanResources\AccessCodeConverter;
use Bitrix\Tasks\Flow\Internal\FlowMemberTable;

trait FlowTeamCountTrait
{
	/**
	 * @throws ObjectPropertyException
	 * @throws LoaderException
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public function getTeamCount(FlowCollection $flows): array
	{
		$flowCollection = $flows->filter($this->getDistributionType());

		if ($flowCollection->isEmpty())
		{
			return [];
		}

		$memberAccessCodes = FlowMemberTable::query()
			->setSelect([
				'FLOW_ID',
				'ACCESS_CODE',
			])
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